<?php

namespace App\Http\Controllers;

use App\Events\ChallengeAcceptedNow;
use App\Events\ChallengeCreated;
use App\Http\Controllers\System\Chess\ChessControllers;
use App\Models\Challenge;
use App\Models\ChessMatchesResults;
use App\Models\Platform;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use App\Classes\Chess\PlatformControllers\GameResolverInterface\GameResultResolver;

class ChallengeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Player/matches/ActiveMatches');
    }

    public function my_matches(): Response
    {
        $myId = Auth::id();

        $myChallenges = Challenge::with(['user', 'opponent', 'platform'])
                                 ->where(function ($query) use ($myId) {
                                     $query
                                         ->where('user_id', $myId)
                                         ->orWhere('opponent_id', $myId)
                                     ;
                                 })
                                 ->orderBy('created_at', 'desc')
                                 ->get()
        ;

        return Inertia::render('Player/matches/MyChallenges', [
            'challenges' => $myChallenges,
        ]);
    }

    public function show($id): Response
    {
        $challenge = Challenge::with(['user', 'opponent', 'platform'])->find($id);

        return Inertia::render('Player/matches/ChallengeDetails', [
            'challengeDetails' => $challenge
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function contend(Request $request): RedirectResponse
    {
        $request->validate([
                               'challenge_id' => 'required|exists:challenges,id',
                           ]);

        $user      = Auth::user();
        $challenge = Challenge::findOrFail($request->challenge_id);

        if ($challenge->user_id === $user->id) {
            throw ValidationException::withMessages([
                                                        'challenge_id' => 'You cannot accept your own challenge.'
                                                    ]);
        }

        if ($challenge->request_state !== 'pending') {
            throw ValidationException::withMessages([
                                                        'challenge_id' => 'This challenge is no longer available.'
                                                    ]);
        }

        // Balance checks...
        $lockedStake = Challenge::where('user_id', $user->id)
                                ->whereIn('challenge_status', ['pending', 'anomaly'])
                                ->sum('stake')
        ;

        $availableBalance = $user->balance - $lockedStake;

        if ($availableBalance < $challenge->stake) {
            throw ValidationException::withMessages([
                                                        'challenge_id' => 'Insufficient balance to accept this challenge.'
                                                    ]);
        }

        $requiredTokens = $challenge->tokens;

        if ($user->token_balance < $requiredTokens) {
            throw ValidationException::withMessages([
                                                        'challenge_id' => "You need at least {$requiredTokens} tokens to accept this challenge."
                                                    ]);
        }

        DB::transaction(function () use ($user, $challenge, $requiredTokens) {
            // 1) Deduct tokens from acceptor
            $user->token_balance -= $requiredTokens;
            $user->save();

            // 2) Update challenge
            $challenge->update([
                                   'opponent_id'   => $user->id,
                                   'request_state' => 'accepted',
                                   'accepted_at'   => now(),
                               ]);

            // 3) Log token use
            $this->deductTokensAndLogTransaction(
                user:      $user,
                challenge: $challenge,
                type:      'token_use',
                note:      'Tokens used to accept challenge'
            );

            // 4) Notify the acceptor
            $acceptorNotif = Request::create('/notifications', 'POST', [
                'title'       => '✅ Challenge Accepted',
                'message'     => "You accepted challenge #{$challenge->id} for KES {$challenge->stake}.",
                'type'        => 'match',
                'routeName'   => 'matches.ready',
                'routeParams' => ['id' => $challenge->id],
                'details'     => "Tokens used: {$requiredTokens}\nWaiting for game start.",
            ]);
            $acceptorNotif->setUserResolver(fn() => $user);
            app(NotificationsController::class)->store($acceptorNotif);

            // 5) Notify the original challenger (creator)
            $creator      = $challenge->user;
            $creatorNotif = Request::create('/notifications', 'POST', [
                'title'       => '📣 Your Challenge Was Accepted',
                'message'     => "@{$user->name} accepted your challenge #{$challenge->id}.",
                'type'        => 'match',
                'routeName'   => 'matches.challenge-details',
                'routeParams' => ['id' => $challenge->id],
                'details'     => "Stake: KES {$challenge->stake}\nTokens used by opponent: {$requiredTokens}",
            ]);
            $creatorNotif->setUserResolver(fn() => $creator);
            app(NotificationsController::class)->store($creatorNotif);

            // Broadcast instantly to the creator
            event(new ChallengeAcceptedNow(
                      creatorId:   $challenge->user_id,
                      challengeId: $challenge->id
                  ));

        });

        return redirect()
            ->route('matches.ready', [$challenge->id])
            ->with('success', 'Challenge accepted!')
        ;
    }

    /**
     * @throws ValidationException
     */
    public function get_results(Request $request, Challenge $challenge, ?ChessMatchesResults $match): RedirectResponse
    {
        if (in_array($challenge->challenge_status, ['won', 'loss', 'draw', 'anomaly'], true))
            return redirect()->route('challenges.show', $challenge);

        $resolver = new GameResultResolver();
        $role     = $resolver->resolve($match, $challenge);

        if ($role === 'anomaly')
            return $this->markAnomaly($challenge);

        $this->resolveMatchAndTransferStake($request, $challenge, $role);

        return redirect()->route('challenges.show', $challenge);
    }

    /**
     * @throws ValidationException
     */
    public function resolveMatchAndTransferStake(Request $request, Challenge $challenge, $winnerRole): JsonResponse
    {
        $validRoles = ['challenger', 'contender', 'draw'];
        if (!in_array($winnerRole, $validRoles)) {
            throw ValidationException::withMessages([
                                                        'winner' => 'Invalid winner role. Must be either challenger or contender.'
                                                    ]);
        }

        $challenge = Challenge::with(['user', 'opponent'])
                              ->where('id', $challenge->id)
                              ->where('request_state', 'accepted')
                              ->firstOrFail()
        ;

        $challenger = $challenge->user;
        $contender  = $challenge->opponent;

        if (!$contender) {
            throw ValidationException::withMessages([
                                                        'challenge' => 'Challenge does not yet have an opponent.'
                                                    ]);
        }

        // DRAW case: no money moves, just mark and notify
        if ($winnerRole === 'draw') {
            DB::transaction(function () use ($challenge, $challenger, $contender) {
                $challenge->challenge_status = 'draw';
                $challenge->save();

                // Notify challenger
                $drawNotif1 = Request::create('/notifications', 'POST', [
                    'title'       => '🤝 It’s a Draw',
                    'message'     => "Challenge #{$challenge->id} ended in a draw. No stakes were moved.",
                    'type'        => 'match',
                    'routeName'   => 'matches.results',
                    'routeParams' => ['id' => $challenge->id],
                    'details'     => "Your balance remains unchanged."
                ]);
                $drawNotif1->setUserResolver(fn() => $challenger);
                app(NotificationsController::class)->store($drawNotif1);

                // Notify contender
                $drawNotif2 = Request::create('/notifications', 'POST', [
                    'title'       => '🤝 It’s a Draw',
                    'message'     => "Challenge #{$challenge->id} ended in a draw. No stakes were moved.",
                    'type'        => 'match',
                    'routeName'   => 'matches.results',
                    'routeParams' => ['id' => $challenge->id],
                    'details'     => "Your balance remains unchanged."
                ]);
                $drawNotif2->setUserResolver(fn() => $contender);
                app(NotificationsController::class)->store($drawNotif2);
            });

            return response()->json([
                                        'message' => "Match resolved as draw for challenge #{$challenge->id}; no balance changes."
                                    ]);
        }

        $winner = $winnerRole === 'challenger' ? $challenger : $contender;
        $loser  = $winnerRole === 'challenger' ? $contender : $challenger;

        DB::transaction(function () use ($challenge, $winner, $loser, $winnerRole) {
            // 1) Credit winner & debit loser
            $winner->balance += $challenge->stake;
            $loser->balance  -= $challenge->stake;
            $winner->save();
            $loser->save();

            // 2) Update challenge status
            $challenge->challenge_status = $winnerRole === 'challenger' ? 'won' : 'loss';
            $challenge->save();

            // 3) Log credit transaction
            Transaction::create([
                                    'request_type'                 => 'stake_win_credit',
                                    'request_id'                   => $challenge->id,
                                    'transaction_origin'           => $loser->id,
                                    'transaction_destination'      => $winner->id,
                                    'amount'                       => $challenge->stake,
                                    'currency'                     => 'KES',
                                    'delivery_confirmation_status' => true,
                                    'transaction_stage'            => 'completed',
                                    'confirmation_status'          => true,
                                    'transaction_complete_status'  => true,
                                    'transaction_notes'            => json_encode([
                                                                                      'note'         => "Stake credited to {$winnerRole} (user_id={$winner->id}) for challenge #{$challenge->id}",
                                                                                      'challenge_id' => $challenge->id,
                                                                                      'role'         => $winnerRole,
                                                                                      'action'       => 'credit'
                                                                                  ]),
                                ]);

            // 4) Log debit transaction
            Transaction::create([
                                    'request_type'                 => 'stake_loss_debit',
                                    'request_id'                   => $challenge->id,
                                    'transaction_origin'           => $loser->id,
                                    'transaction_destination'      => $winner->id,
                                    'amount'                       => -$challenge->stake,
                                    'currency'                     => 'KES',
                                    'delivery_confirmation_status' => true,
                                    'transaction_stage'            => 'completed',
                                    'confirmation_status'          => true,
                                    'transaction_complete_status'  => true,
                                    'transaction_notes'            => json_encode([
                                                                                      'note'         => "Stake debited from loser (user_id={$loser->id}) for challenge #{$challenge->id}",
                                                                                      'challenge_id' => $challenge->id,
                                                                                      'role'         => $winnerRole === 'challenger' ? 'contender' : 'challenger',
                                                                                      'action'       => 'debit'
                                                                                  ]),
                                ]);

            // 5) Notify the winner
            $winNotif = Request::create('/notifications', 'POST', [
                'title'       => '🎉 You Won!',
                'message'     => "Congratulations—you won challenge #{$challenge->id} and earned KES {$challenge->stake}!",
                'type'        => 'match',
                'routeName'   => 'matches.results',
                'routeParams' => ['id' => $challenge->id],
                'details'     => "Stake won: KES {$challenge->stake}\nTokens risked: {$challenge->tokens}",
            ]);
            $winNotif->setUserResolver(fn() => $winner);
            app(NotificationsController::class)->store($winNotif);

            // 6) Notify the loser
            $loseNotif = Request::create('/notifications', 'POST', [
                'title'       => '😞 You Lost',
                'message'     => "Challenge #{$challenge->id} was lost. Better luck next time!",
                'type'        => 'match',
                'routeName'   => 'matches.results',
                'routeParams' => ['id' => $challenge->id],
                'details'     => "Stake lost: KES {$challenge->stake}\nTokens risked: {$challenge->tokens}",
            ]);
            $loseNotif->setUserResolver(fn() => $loser);
            app(NotificationsController::class)->store($loseNotif);
        });

        return response()->json([
                                    'message' => "Match resolved: {$winnerRole} (user_id={$winner->id}) wins challenge #{$challenge->id}."
                                ]);
    }

    public function ready(Request $request, $id): Response
    {
        $challenge = Challenge::with(['user', 'opponent', 'platform'])->find($id);

        return Inertia::render('Player/matches/MatchReady', [
            'challenge' => $challenge
        ]);
    }

    public function create_challenge(): Response
    {
        return Inertia::render('Player/matches/CreateChallenge');
    }

    public function show_results(Request $request, $id): Response
    {
        $user      = Auth::user();
        $challenge = Challenge::with(['user', 'opponent'])->findOrFail($id);

        // Restrict access to only participants
        if ($challenge->user_id !== $user->id && $challenge->opponent_id !== $user->id)
            abort(403, 'Unauthorized access to this match result.');

        if ($challenge->challenge_status == 'pending')
            (new ChessControllers())->getChallengeResult($request, $challenge);

        $challenge->refresh();

        // Determine result for logged-in user
        $status               = $challenge->challenge_status; // e.g., won, loss, draw, anomaly
        $loggedInIsChallenger = $challenge->user_id === $user->id;

        $result = match ($status) {
            'draw'                => 'draw',
            'anomaly', 'canceled' => 'canceled',
            'won'                 => $loggedInIsChallenger ? 'win' : 'loss',
            'loss'                => $loggedInIsChallenger ? 'loss' : 'win',
            default               => 'anomaly',
        };

        return Inertia::render('Player/matches/MatchResults', [
            'result'      => $result,
            'opponent'    => $loggedInIsChallenger ? $challenge->opponent?->name : $challenge->user->name,
            'tokens'      => $challenge->tokens,
            'winnings'    => (float)$challenge->stake,
            'timeControl' => $challenge->time_control,
            'newRank'     => 1200,
            'rankChange'  => 0,
        ]);
    }

    public function store_challenge(Request $request): RedirectResponse
    {
        // 1. Validate input
        $validated = $request->validate([
                                            'stake'       => 'required|numeric|min:10',
                                            'platform'    => [
                                                'required',
                                                'exists:platforms,name',
                                                // custom rule to ensure the user has a chess_com_link
                                                function ($attribute, $value, $fail) {
                                                    if (!optional(auth()->user())->chess_com_link) {
                                                        $fail('You must add your Chess.com link to your profile before choosing a platform.');
                                                    }
                                                },
                                            ],
                                            'timeControl' => 'required|string',
                                        ]);

        $user = Auth::user();

        // 2. Check available balance
        $lockedStake = Challenge::where('user_id', $user->id)
                                ->whereIn('challenge_status', ['pending', 'anomaly'])
                                ->sum('stake')
        ;

        $availableBalance = $user->balance - $lockedStake;

        if ($availableBalance < $validated['stake']) {
            throw ValidationException::withMessages([
                                                        'stake' => 'Insufficient free balance to create this challenge.'
                                                    ]);
        }

        // 3. Check token balance
        $requiredTokens = ceil($validated['stake'] / 10);
        if ($user->token_balance < $requiredTokens) {
            throw ValidationException::withMessages([
                                                        'stake' => "You need at least {$requiredTokens} tokens to create this challenge."
                                                    ]);
        }

        // 4. Resolve platform
        $platform = Platform::where('name', $validated['platform'])->firstOrFail();

        // 5. Perform DB transaction
        DB::transaction(function () use ($validated, $platform, $user, $requiredTokens) {
            // 5a. Deduct tokens
            $user->token_balance -= $requiredTokens;
            $user->save();

            // 5b. Create challenge
            $challenge = Challenge::create([
                                               'user_id'       => $user->id,
                                               'request_state' => 'pending',
                                               'stake'         => $validated['stake'],
                                               'tokens'        => $requiredTokens,
                                               'platform_id'   => $platform->id,
                                               'time_control'  => $validated['timeControl'],
                                           ]);

            // 5c. Log token transaction
            $this->deductTokensAndLogTransaction(
                user:      $user,
                challenge: $challenge,
                type:      'token_deduction',
                note:      'Tokens used to create challenge'
            );

            // 5d. Fire notification via controller
            $notifData = [
                'title'       => '✅ Challenge Created',
                'message'     => "Your challenge #{$challenge->id} for KES {$challenge->stake} has been created.",
                'type'        => 'match',
                'routeName'   => 'matches.challenge-details',
                'routeParams' => ['id' => $challenge->id],
                'details'     => "Platform: {$platform->name}\nTime Control: {$validated['timeControl']}",
            ];

            // Build a sub-request for NotificationsController
            $notifRequest = Request::create('/notifications', 'POST', $notifData);
            $notifRequest->setUserResolver(fn() => $user);

            // Call the store method directly
            app(NotificationsController::class)->store($notifRequest);

            ChallengeCreated::dispatch($challenge);

        });

        // 📣 broadcast immediately

        // 6. Redirect back with success message
        return redirect()
            ->route('matches.active')
            ->with('success', 'Challenge created!')
        ;
    }

    protected function deductTokensAndLogTransaction(User $user, Challenge $challenge, string $type, string $note): void
    {
        Transaction::create([
                                'request_type'                 => $type,
                                'request_id'                   => $challenge->id,
                                'transaction_origin'           => $user->id,
                                'transaction_destination'      => $user->id,
                                'amount'                       => $challenge->tokens * 10,
                                'currency'                     => 'KES',
                                'delivery_confirmation_status' => true,
                                'transaction_stage'            => 'confirmed',
                                'confirmation_status'          => true,
                                'transaction_complete_status'  => true,
                                'transaction_notes'            => json_encode([
                                                                                  'tokens'       => $challenge->tokens,
                                                                                  'amount'       => $challenge->tokens * 10,
                                                                                  'note'         => $note,
                                                                                  'challenge_id' => $challenge->id,
                                                                              ]),
                            ]);
    }

    public function get_active_matches(Request $request): JsonResponse
    {
        // 1) Pull out the list of online user IDs
        $onlineIds = collect($request->input('onlineUsers', []))
            ->pluck('id')
            ->toArray()
        ;

        // 2) If nobody’s online, return an empty array immediately
        if (empty($onlineIds)) {
            return response()->json([]);
        }

        // 3) Fetch only pending challenges where the creator is online
        $activeChallenges = Challenge::with(['user', 'opponent', 'platform'])
                                     ->where('request_state', 'pending')
                                     ->whereIn('user_id', $onlineIds)
                                     ->where('user_id', '!=', auth()->id())
                                     ->get()
        ;

        return response()->json($activeChallenges);
    }

    public function game_created(Challenge $challenge): JsonResponse
    {
        $challenge->challenger_ready = true;
        $challenge->save();

        return \response()->json([
                                     'status'    => 'success',
                                     'message'   => 'Challenge Joined',
                                     'challenge' => $challenge
                                 ]);
    }

    public function opponent_joined(Challenge $challenge): JsonResponse
    {
        $challenge->contender_ready = true;
        $challenge->save();

        return \response()->json([
                                     'status'    => 'success',
                                     'message'   => 'Challenge Joined',
                                     'challenge' => $challenge
                                 ]);
    }

    private function markAnomaly(Challenge $challenge): RedirectResponse
    {
        Log::error("Challenge #{$challenge->id} produced unexpected results: " . "{$challenge->challenge_status}");
        $challenge->update(['challenge_status' => 'anomaly']);
        return redirect()->back();
    }

//    private function holder()
//    {
//        // Only proceed if we haven’t already set a final status
//        if (! in_array($challenge->challenge_status, ['won','loss','draw','anomaly'])) {
//
//            $challengerLink = strtolower($challenge->user->chess_com_link);
//            $opponentLink   = strtolower($challenge->opponent->chess_com_link);
//
//            // --- 1) figure out which color actually won ---
//            if ($match->white_result === 'win' && in_array($match->black_result, ['checkmated','timeout'])) {
//                $winnerColor = 'white';
//            }
//            elseif ($match->black_result === 'win' && in_array($match->white_result, ['checkmated','timeout'])) {
//                $winnerColor = 'black';
//            }
//            elseif ($match->white_result === 'stalemate' && $match->black_result === 'stalemate') {
//                $winnerColor = 'draw';
//            }
//            else {
//                // something unexpected — mark anomaly
//                Log::error("Challenge #{$challenge->id} yielded unexpected results: "
//                           . "{$match->white_result}/{$match->black_result}");
//                $challenge->update(['challenge_status' => 'anomaly']);
//                return redirect()->back();
//            }
//
//            // --- 2) pick the actual winner username (or null on draw) ---
//            if ($winnerColor === 'white') {
//                $winnerUsername = strtolower($match->white);
//            }
//            elseif ($winnerColor === 'black') {
//                $winnerUsername = strtolower($match->black);
//            }
//            else {
//                $winnerUsername = null; // draw
//            }
//
//            // --- 3) map that back to challenger vs opponent vs draw ---
//            if ($winnerColor === 'draw') {
//                $who = 'draw';
//            }
//            elseif ($winnerUsername === $challengerLink) {
//                $who = 'challenger';
//            }
//            elseif ($winnerUsername === $opponentLink) {
//                $who = 'contender';
//            }
//            else {
//                // Neither link matches — anomaly
//                Log::error("Challenge #{$challenge->id}: winning user '{$winnerUsername}' "
//                           . "didn’t match challenger '{$challengerLink}' or opponent '{$opponentLink}'");
//                $challenge->update(['challenge_status' => 'anomaly']);
//                return redirect()->back();
//            }
//
//            // --- 4) resolve stakes and mark status ---
//            $this->resolveMatchAndTransferStake($request, $challenge, $who);
//        }
//    }

}

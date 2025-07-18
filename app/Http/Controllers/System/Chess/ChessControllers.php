<?php

namespace App\Http\Controllers\System\Chess;

use App\Classes\Chess\GameExtractor;
use App\Classes\Chess\User;
use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\ChessMatchesResults;
use App\Models\User as UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChessControllers extends Controller
{
    /**
     * Entry point to demo archives + this month's games.
     */
    public function entry(Request $request, Challenge $challenge)
    {
        $username = $challenge->user->chess_com_link;

        $user = UserModel::where('chess_com_link', $username)->firstOrFail();

        $archive = Carbon::parse($challenge->accepted_at)->format('/Y/m');


        // 1) archives in ['year'=>..., 'month'=>...] form
        $archives = User::getArchives($user);

        // 2) this month’s games
        $games = User::getMonthlyGames($user);

        return response()->json([
            'user' => User::fromApiResponse($user),
            'archives' => $archives,
            'games' => $games,
        ]);
    }

    public function getChallengeResult(Request $request, Challenge $challenge)
    {
        $archive = Carbon::parse($challenge->accepted_at)->format('/Y/m');

        $url = "https://api.chess.com/pub/player/{$challenge->user->chess_com_link}/games" . $archive;

        try {
            $raw = Http::timeout(10)->get($url)->throw()->json('games', []);

            // pase the match file to reduce load on server
            // $raw = json_decode(file_get_contents(Storage::disk('local')->path('Requests/games.json')), true);

            $gameExtractor = new GameExtractor();
            $games = $gameExtractor->extractMany($raw);
            $game = $gameExtractor->getCloseMatch($games, $challenge);

            $alreadyExists = ChessMatchesResults::where('match_link', $game['match_link'])->exists();

            $match = ChessMatchesResults::updateOrCreate(
                ['match_link' => $game['match_link']],
                [
                    'match_type'    => $game['match_type'],
                    'white'         => $game['white'],
                    'black'         => $game['black'],
                    'start_time'    => $game['start_time'],
                    'end_time'      => $game['end_time'],
                    'white_result'  => $game['white_result'],
                    'black_result'  => $game['black_result'],
                    'termination'   => $game['termination'],
                    'challenge_id'  => $game['challenge_id'],
                ]
            );

            if ($alreadyExists) {
                $challenge->challenge_status = 'anomaly';
                $challenge->save();
                Log::error('Disputed Match: '.$challenge->id);
                return json_encode(['exists' => true]);
            }

// otherwise it's a brand-new record, so keep going:
            (new ChallengeController())->get_results($request, $challenge, $match);

        } catch (\Exception $e) {
            //log response
            Log::error($e->getMessage() . ':' . $challenge->id);

            return response()->json([
                'error' => $e->getMessage(),
            ]);
        }
    }
}


//        $username = 'tevstark';
//        $username = 'Jowey254';
//        $username = 'mwaura_kimani';
//        $username = 'akingvonfans';

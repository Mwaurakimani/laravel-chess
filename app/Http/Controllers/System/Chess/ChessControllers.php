<?php

namespace App\Http\Controllers\System\Chess;

use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\ChessMatchesResults;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Services\ChessComService\ChessComServiceProvider;

class ChessControllers extends Controller
{
    protected ChessComServiceProvider $chessServiceProvider;

    public function __construct()
    {
        $this->chessServiceProvider = new ChessComServiceProvider(
//            internalMode: false,
//            matchesPath:  'Requests/games.json'
        );
    }

    public function getChallengeResult(Request $request, Challenge $challenge)
    {
        $game = $this->chessServiceProvider->fetchArchivedGames(
            $challenge->user,
            Carbon::parse($challenge->accepted_at)->format('/Y/m')
        )->getCloseMatch(
            challenge:    $challenge,
        );

        try {
            $alreadyExists = ChessMatchesResults::where('match_link', $game['match_link'])->exists();

            $match = ChessMatchesResults::updateOrCreate(
                ['match_link' => $game['match_link']],
                [
                    'match_type'   => $game['match_type'],
                    'white'        => $game['white'],
                    'black'        => $game['black'],
                    'start_time'   => $game['start_time'],
                    'end_time'     => $game['end_time'],
                    'white_result' => $game['white_result'],
                    'black_result' => $game['black_result'],
                    'termination'  => $game['termination'],
                    'challenge_id' => $game['challenge_id'],
                ]
            );

            if ($alreadyExists) {
                $challenge->challenge_status = 'anomaly';
                $challenge->save();
                Log::error('Disputed Match: ' . $challenge->id);
                return json_encode(['exists' => true]);
            }

            // otherwise it's a brand-new record, so keep going:
            (new ChallengeController())->get_results($request, $challenge, $match);

            return null;

        }
        catch (\Exception $e) {
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

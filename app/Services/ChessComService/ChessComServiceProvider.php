<?php

namespace App\Services\ChessComService;

use App\Models\User;
use App\Models\Challenge;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Classes\Chess\PlatformControllers\GameExtractor;

class ChessComServiceProvider
{
    protected string $baseUrl      = 'https://api.chess.com/pub/';
    protected bool   $internalMode = false;
    protected string $matchesPath;
    public ?array    $games        = null;
    public int       $timeWindow   = 20;

    public function __construct($internalMode = null, string $matchesPath = null)
    {
        if (isset($internalMode) && isset($matchesPath)) {
            $this->internalMode = $internalMode;
            $this->matchesPath  = $matchesPath;
        }
    }

    public function setTimeWindow(int $timeWindow): ChessComServiceProvider
    {
        $this->timeWindow = $timeWindow;
        return $this;
    }

    public function fetchArchivedGames(User $user, string $archive = null): ChessComServiceProvider
    {
        $archive = $archive ?? Carbon::now()->format('/Y/m');

        $url = $this->baseUrl . "player/{$user->chess_com_link}/games" . $archive;

        try {

            if ($this->internalMode) {

                //Load the matches from an internal JSON file
                $raw = json_decode(
                    file_get_contents(Storage::disk('local')
                                             ->path($this->matchesPath)
                    ), true);

            } else {

                //load the games form the chess.com api
                $raw = Http::timeout(10)
                           ->get($url)
                           ->throw()
                           ->json('games', [])
                ;

            }

            $this->games = (new GameExtractor())->extractMany($raw);

            return $this;

        }
        catch (\Exception $e) {
            //log response
            Log::error($e->getMessage());

            return $this;
        }
    }

    public function getCloseMatch(Challenge $challenge, $acceptedTime = null): ?array
    {
        // Build our time window
        $accepted = Carbon::parse($acceptedTime ?? $challenge->accepted_at, 'UTC');
        $cutoff   = $accepted->copy()->addMinutes($this->timeWindow);

        $chLink = strtolower($challenge->user->chess_com_link);
        $opLink = strtolower($challenge->opponent->chess_com_link);


        // Filter games by participants & window
        $candidates = array_filter(
            $this->games,
            function (array $g) use ($accepted, $cutoff, $chLink, $opLink) {
                if (empty($g['start_time']))
                    return false;

                $start = Carbon::parse($g['start_time'], 'UTC');

                // must be in our accepted window
                if (!$start->betweenIncluded($accepted, $cutoff))
                    return false;

                // players match the challenge, in either order
                $white = strtolower($g['white']);
                $black = strtolower($g['black']);
                $pair  = [$white, $black];

                return in_array($chLink, $pair, true) && in_array($opLink, $pair, true);
            });

        if (empty($candidates))
            return null;


        // pick the earliest start
        usort($candidates,
            function ($a, $b) {
                return strcmp($a['start_time'], $b['start_time']);
            });

        // grab the first, set its challenge_id and return
        $match                 = $candidates[0];
        $match['challenge_id'] = $challenge->id;

        return $match;
    }


}

<?php

namespace App\Classes\Chess;

use App\Models\Challenge;
use Carbon\Carbon;

class GameExtractor
{
    /**
     * Extract the key fields from a single Chess.com game array.
     *
     * @param  array       $game         Raw game data (incl. 'tags' & 'pgn').
     * @param  int|null    $challengeId  Optional challenge_id to bind.
     * @return array                     Normalized [
     *     match_link, match_type, white, black,
     *     start_time, end_time,
     *     white_result, black_result,
     *     termination, challenge_id
     * ]
     */
    public function extract(array $game, ?int $challengeId = null): array
    {
        $tags = $game['tags'] ?? [];
        $pgn  = $game['pgn']  ?? '';

        // --- pull from tags if present
        $date    = $tags['utcdate']     ?? $tags['date']       ?? null;     // "YYYY.MM.DD"
        $time    = $tags['utctime']     ?? $tags['start_time'] ?? null;     // "HH:MM:SS"
        $eDate   = $tags['end_date']    ?? null;
        $eTime   = $tags['end_time']    ?? null;
        $link    = $tags['link']        ?? null;
        $term    = $tags['termination'] ?? null;

        // --- fallback via regex on raw PGN
        if (! $date && preg_match('/\[Date\s+"([\d\.]+)"/', $pgn, $m)) {
            $date = $m[1];
        }
        if (! $time && preg_match('/\[StartTime\s+"([\d:]+)"/', $pgn, $m)) {
            $time = $m[1];
        }
        if (! $eDate && preg_match('/\[EndDate\s+"([\d\.]+)"/', $pgn, $m)) {
            $eDate = $m[1];
        }
        if (! $eTime && preg_match('/\[EndTime\s+"([\d:]+)"/', $pgn, $m)) {
            $eTime = $m[1];
        }
        if (! $link && preg_match('/\[Link\s+"([^"]+)"/', $pgn, $m)) {
            $link = $m[1];
        }
        if (! $term && preg_match('/\[Termination\s+"([^"]+)"/', $pgn, $m)) {
            $term = $m[1];
        }

        // --- build UTC start/end timestamps
        $start = null;
        if ($date && $time) {
            $start = Carbon::createFromFormat('Y.m.d H:i:s', "$date $time", 'UTC')
                ->toDateTimeString();
        }

        $end = null;
        if ($eDate && $eTime) {
            $end = Carbon::createFromFormat('Y.m.d H:i:s', "$eDate $eTime", 'UTC')
                ->toDateTimeString();
        }

        return [
            'match_link'   => $link,
            'match_type'   => $tags['time_control'] ?? $game['time_control'] ?? null,
            'white'        => $game['white']['username'] ?? null,
            'black'        => $game['black']['username'] ?? null,
            'start_time'   => $start,
            'end_time'     => $end,
            'white_result' => $game['white']['result'] ?? null,
            'black_result' => $game['black']['result'] ?? null,
            'termination'  => $term,
            'challenge_id' => $challengeId,
        ];
    }

    /**
     * Extract a list of games.
     *
     * @param  array      $games  Array of raw game arrays.
     * @param  int|null   $challengeId
     * @return array              Array of normalized game arrays.
     */
    public function extractMany(array $games, ?int $challengeId = null): array
    {
        return array_map(function($g) use ($challengeId) {
            return $this->extract($g, $challengeId);
        }, $games);
    }

    public function getCloseMatch(array $games, Challenge $challenge): ?array
    {
        // Build our time window
        $accepted = Carbon::parse($challenge->accepted_at, 'UTC');
        $cutoff   = $accepted->copy()->addMinutes(20);

        $chLink   = strtolower($challenge->user->chess_com_link);
        $opLink   = strtolower($challenge->opponent->chess_com_link);

        // Filter games by participants & window
        $candidates = array_filter($games, function(array $g) use ($accepted, $cutoff, $chLink, $opLink) {
            if (empty($g['start_time'])) {
                return false;
            }

            $start = Carbon::parse($g['start_time'], 'UTC');

            // must be in our 10-minute window
            if (! $start->betweenIncluded($accepted, $cutoff)) {
                return false;
            }

            // players match the challenge, in either order
            $white = strtolower($g['white']);
            $black = strtolower($g['black']);
            $pair  = [$white, $black];

            return in_array($chLink, $pair, true)
                && in_array($opLink, $pair, true);
        });

        if (empty($candidates)) {
            return null;
        }


        // pick the earliest start
        usort($candidates, function($a, $b) {
            return strcmp($a['start_time'], $b['start_time']);
        });

        // grab the first, set its challenge_id and return
        $match = $candidates[0];
        $match['challenge_id'] = $challenge->id;

        return $match;
    }
}

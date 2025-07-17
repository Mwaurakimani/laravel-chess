<?php

namespace App\Classes\Chess;

use Carbon\Carbon;
use App\Models\User as UserModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use App\Classes\Chess\Game;

/**
 * @property $chess_com_link
 */
class User
{
    protected const BASE_URL = 'https://api.chess.com/pub';

    public string|null $avatar;
    public int|null $playerId;
    public string|null $apiId;            // corresponds to "@id"
    public string|null $url;
    public string|null $name;
    public string|null $username;
    public int|null $followers;
    public string|null $countryUrl;
    public Carbon|null $lastOnline;
    public Carbon|null $joinedAt;
    public string|null $status;
    public bool|null $isStreamer;
    public bool|null $verified;
    public string|null $league;
    /** @var string[] */
    public array $streamingPlatforms;

    private function __construct(array $data)
    {
        $this->avatar = $data['avatar'] ?? null;
        $this->playerId = $data['player_id'] ?? null;
        $this->apiId = $data['@id'] ?? null;
        $this->url = $data['url'] ?? null;
        $this->username = $data['username'] ?? null;
        $this->followers = $data['followers'] ?? null;
        $this->countryUrl = $data['country'] ?? null;
        $this->lastOnline = isset($data['last_online']) ? Carbon::createFromTimestamp($data['last_online']) : null;
        $this->joinedAt = isset($data['joined']) ? Carbon::createFromTimestamp($data['joined']) : null;
        $this->status = $data['status'] ?? null;
        $this->isStreamer = isset($data['is_streamer']) ? (bool)$data['is_streamer'] : null;
        $this->verified = isset($data['verified']) ? (bool)$data['verified'] : null;
        $this->league = $data['league'] ?? null;
        $this->streamingPlatforms = $data['streaming_platforms'] ?? null;
    }


    /**
     * Hydrate a User from a Chess.com API response.
     */
    public static function fromApiResponse(UserModel $user): self
    {
        $path = '/player/' . $user->chess_com_link;
        $url = self::BASE_URL . $path;
        $response = Http::get($url)->json();
        return new self($response);
    }

    /**
     * Return a plain array of ['year'=>YYYY,'month'=>MM] for each archive.
     */
    public static function getArchives(UserModel $user): array
    {
        $url = self::BASE_URL . "/player/{$user->chess_com_link}/games/archives";
        $response = Http::get($url)->throw()->json();
        $out = [];
        foreach ($response['archives'] ?? [] as $archiveUrl) {
            if (!is_string($archiveUrl)) {
                continue;
            }
            $path = parse_url($archiveUrl, PHP_URL_PATH) ?: '';
            $segments = explode('/', trim($path, '/'));
            $n = count($segments);
            if ($n >= 2) {
                $out[] = [
                    'year' => (int)$segments[$n - 2],
                    'month' => (int)$segments[$n - 1],
                ];
            }
        }

        return $out;
    }

    public static function getMonthlyGames(
        UserModel $user,
        ?int      $year = null,
        ?int      $month = null,
                  $start_date = null,   // "YYYY-MM-DD" or timestamp
                  $end_date = null,   // "YYYY-MM-DD" or timestamp
                  $start_time = null,   // "HH:MM:SS"
                  $end_time = null    // "HH:MM:SS"
    ): Collection
    {
        // 1) Default to current year/month in UTC
        $now = Carbon::now('UTC');
        $year = $year ?? $now->year;
        $month = $month ?? $now->month;
        $mm = str_pad((string)$month, 2, '0', STR_PAD_LEFT);

        // 2) Fetch that month's games JSON
        $url = self::BASE_URL
            . "/player/{$user->chess_com_link}/games/{$year}/{$mm}";
        $raw = Http::timeout(10)
            ->get($url)
            ->throw()
            ->json('games', []);

        return collect($raw)
            // 3) Hydrate into Game value objects
            ->map(fn(array $g) => Game::fromApiResponse($g))
            // 4) Apply date+time filtering
            ->filter(function (Game $g) use (
                $start_date, $start_time, $end_date, $end_time
            ) {
                // build UTC start‐anchor
                $startAnchor = null;
                if ($start_date !== null) {
                    $startAnchor = Carbon::createFromFormat(
                        'Y-m-d', $start_date, 'UTC'
                    )->startOfDay();
                    if ($start_time !== null) {
                        $startAnchor->setTimeFromTimeString($start_time);
                    }
                }

                // build UTC end‐anchor
                $endAnchor = null;
                if ($end_date !== null) {
                    $endAnchor = Carbon::createFromFormat(
                        'Y-m-d', $end_date, 'UTC'
                    )->endOfDay();
                    if ($end_time !== null) {
                        $endAnchor->setTimeFromTimeString($end_time);
                    }
                }

                // pull PGN tags
                $tagDate = $g->tags['date'] ?? null; // "YYYY.MM.DD"
                $tagStart = $g->tags['start_time'] ?? null; // "HH:MM:SS"
                if (!$tagDate || !$tagStart) {
                    // no timestamp present
                    return false;
                }

                // combine into a UTC timestamp
                $gameDT = Carbon::createFromFormat(
                    'Y.m.d H:i:s',
                    $tagDate . ' ' . $tagStart,
                    'UTC'
                );

                // exclude before start‐anchor
                if ($startAnchor && $gameDT->lt($startAnchor)) {
                    return false;
                }
                // exclude after end‐anchor
                if ($endAnchor && $gameDT->gt($endAnchor)) {
                    return false;
                }

                return true;
            })
            ->values();
    }


}

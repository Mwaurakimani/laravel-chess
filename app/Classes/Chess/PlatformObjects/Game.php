<?php

namespace App\Classes\Chess\PlatformObjects;

use Illuminate\Support\Arr;

class Game
{
    public string  $url;
    public string  $pgn;
    public string  $timeControl;
    public ?int    $endTime;
    public bool    $rated;
    public string  $tcn;
    public string  $uuid;
    public string  $initialSetup;
    public string  $fen;
    public ?int    $startTime;
    public string  $timeClass;
    public string  $rules;
    public Player  $white;
    public Player  $black;
    public ?string $eco;

    /** Parsed tags from the PGN header (all of them). */
    public array   $tags;

    private function __construct(array $data)
    {
        $this->url          = $data['url'];
        $this->pgn          = $data['pgn'];
        $this->timeControl  = $data['time_control'];
        $this->endTime      = isset($data['end_time']) ? (int)$data['end_time'] : null;
        $this->rated        = (bool) $data['rated'];
        $this->tcn          = $data['tcn'];
        $this->uuid         = $data['uuid'];
        $this->initialSetup = $data['initial_setup'];
        $this->fen          = $data['fen'];
        $this->startTime    = isset($data['start_time']) ? (int)$data['start_time'] : null;
        $this->timeClass    = $data['time_class'];
        $this->rules        = $data['rules'];
        $this->eco          = $data['eco'] ?? null;

        // Hydrate the players
        $this->white = Player::fromArray(Arr::get($data, 'white', []));
        $this->black = Player::fromArray(Arr::get($data, 'black', []));

        // Parse **all** PGN tags into an associative array
        $this->tags = $this->parseAllPgnTags($this->pgn);
    }

    /**
     * Factory to build from the raw JSON array.
     */
    public static function fromApiResponse(array $data): self
    {
        return new self($data);
    }

    /**
     * Parses every PGN header tag ([Tag "Value"]) into [tag => value].
     */
    protected function parseAllPgnTags(string $pgn): array
    {
        $tags = [];
        // Match lines like [TagName "Value"] capturing TagName and Value
        if (preg_match_all('/\[(\w+)\s+"([^"]*)"\]/', $pgn, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                // Tag names are case-sensitive in PGN, normalize to lower-snake for lookup
                $key = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $m[1]));
                $tags[$key] = $m[2];
            }
        }
        return $tags;
    }
}

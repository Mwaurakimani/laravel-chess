<?php

namespace App\Classes\Chess\PlatformObjects;

class Player
{
    public int    $rating;
    public string $result;
    public string $apiId;     // the "@id" field
    public string $username;
    public string $uuid;

    private function __construct(array $data)
    {
        $this->rating   = (int) ($data['rating'] ?? 0);
        $this->result   = $data['result'] ?? '';
        $this->apiId    = $data['@id'] ?? '';
        $this->username = $data['username'] ?? '';
        $this->uuid     = $data['uuid'] ?? '';
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}

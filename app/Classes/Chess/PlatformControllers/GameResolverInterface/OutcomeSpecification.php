<?php

namespace App\Classes\Chess\PlatformControllers\GameResolverInterface;

use App\Models\Challenge;
use App\Models\ChessMatchesResults;

interface OutcomeSpecification
{
    public function isSatisfiedBy(ChessMatchesResults $match, Challenge $challenge): bool;

    public function getRole(): string; // 'challenger'|'contender'|'draw'
}

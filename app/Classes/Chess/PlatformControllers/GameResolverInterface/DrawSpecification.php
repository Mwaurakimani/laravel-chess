<?php

namespace App\Classes\Chess\PlatformControllers\GameResolverInterface;

use App\Models\Challenge;
use App\Models\ChessMatchesResults;

class DrawSpecification implements OutcomeSpecification
{
    public function isSatisfiedBy(ChessMatchesResults $match, Challenge $challenge): bool
    {
        return $match->white_result === 'stalemate'
               && $match->black_result === 'stalemate';
    }

    public function getRole(): string
    {
        return 'draw';
    }
}

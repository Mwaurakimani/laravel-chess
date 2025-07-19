<?php

namespace App\Classes\Chess\PlatformControllers\GameResolverInterface;

use App\Models\Challenge;
use App\Models\ChessMatchesResults;

class BlackWinSpecification implements OutcomeSpecification
{
    public function isSatisfiedBy(ChessMatchesResults $match, Challenge $challenge): bool
    {
        return $match->black_result === 'win'
               && in_array($match->white_result, ['checkmated', 'timeout'], true);
    }

    public function getRole(): string
    {
        return 'black';
    }
}

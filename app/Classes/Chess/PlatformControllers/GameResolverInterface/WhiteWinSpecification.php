<?php

namespace App\Classes\Chess\PlatformControllers\GameResolverInterface;

use App\Models\Challenge;
use App\Models\ChessMatchesResults;

class WhiteWinSpecification implements OutcomeSpecification
{
    public function isSatisfiedBy(ChessMatchesResults $match, Challenge $challenge): bool
    {
        return $match->white_result === 'win'
               && in_array($match->black_result, ['checkmated', 'timeout'], true);
    }

    public function getRole(): string
    {
        // we still need to distinguish challenger vs contender later
        return 'white';
    }
}

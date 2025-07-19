<?php


namespace App\Classes\Chess\PlatformControllers\GameResolverInterface;

use App\Models\Challenge;
use App\Models\ChessMatchesResults;

class GameResultResolver
{
    /** @var OutcomeSpecification[] */
    protected array $specs;

    public function __construct()
    {
        $this->specs = [
            new DrawSpecification(),
            new WhiteWinSpecification(),
            new BlackWinSpecification(),
        ];
    }

    /**
     * @return 'challenger'|'contender'|'draw'|'anomaly'
     */
    public function resolve(ChessMatchesResults $match, Challenge $challenge): string
    {
        // run through each specification
        foreach ($this->specs as $spec) {
            if ($spec->isSatisfiedBy($match, $challenge)) {
                $role = $spec->getRole();

                if ($role === 'draw') {
                    return 'draw';
                }

                // map white/black back to challenger/contender
                $winnerUsername = strtolower($role === 'white'
                                                 ? $match->white
                                                 : $match->black
                );

                $links = [
                    'challenger' => strtolower($challenge->user->chess_com_link),
                    'contender'  => strtolower($challenge->opponent->chess_com_link),
                ];

                if ($winnerUsername === $links['challenger']) {
                    return 'challenger';
                }

                if ($winnerUsername === $links['contender']) {
                    return 'contender';
                }

                // if somehow it doesn’t match, break out as anomaly
                break;
            }
        }

        return 'anomaly';
    }
}


<?php

namespace App\Http\Controllers;

use App\Events\UserJoined;
use Exception;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Challenge;
use Inertia\Response;

class DashboardController extends Controller
{

    /**
     * @throws Exception
     */
    public function index(): Response
    {
        broadcast(new UserJoined(Auth::user()));
        $user = Auth::user();

        $recentMatches = Challenge::where(function ($q) use ($user) {
            $q
                ->where('user_id', $user->id)
                ->orWhere('opponent_id', $user->id)
            ;
        })
                                  ->whereIn('challenge_status', ['won', 'loss', 'draw'])
                                  ->latest()
                                  ->take(10)
                                  ->get()
                                  ->map(function ($match) use ($user) {
                                      $opponent = $match->user_id === $user->id ? $match->opponent : $match->user;
                                      $result   = match (true) {
                                          $match->challenge_status === 'draw'                                        => 'Draw',
                                          ($match->user_id === $user->id && $match->challenge_status === 'won')      => 'Win',
                                          ($match->opponent_id === $user->id && $match->challenge_status === 'won')  => 'Loss',
                                          ($match->user_id === $user->id && $match->challenge_status === 'loss')     => 'Loss',
                                          ($match->opponent_id === $user->id && $match->challenge_status === 'loss') => 'Win',
                                          default                                                                    => 'Anomaly'
                                      };

                                      return [
                                          'opponent' => $opponent?->name ?? 'Unknown',
                                          'result'   => $result,
                                          'platform' => $match->platform->name ?? 'Unknown',
                                          'tokens'   => $match->tokens,
                                          'stake'    => (float)$match->stake,
                                          'date'     => $match->created_at->format('M d'),
                                      ];
                                  })
        ;

        return Inertia::render('Dashboard', [
            'user'          => $user,
            'recentMatches' => $recentMatches,
        ]);
    }

}

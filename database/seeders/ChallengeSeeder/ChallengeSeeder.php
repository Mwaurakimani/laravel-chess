<?php

namespace Database\Seeders\ChallengeSeeder;

use App\Models\Challenge;
use App\Models\User;
use Database\Seeders\Classes\DataExtractor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ChallengeSeeder extends Seeder
{
    use WithoutModelEvents, DataExtractor;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
//            $this->load_played_matches();
            $this->load_played_users_test_matches();
        } catch (\Exception $e) {
            logger()->error('ChallengeSeeder failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function load_played_matches(): true
    {
        $matches = $this->getData('matches_all_users.json');

        foreach ($matches as $match) {
            // 1) find challenger (white) and contender (black)
            $whiteUsername = $match['white'];
            $blackUsername = $match['black'];

            $challenger = User::where('chess_com_link', $whiteUsername)->first();
            $contender  = User::where('chess_com_link', $blackUsername)->first();

            // skip if either user not found
            if (! $challenger || ! $contender) {
                logger()->warning("Skipping match {$match['match_link']}, user not found.");
                continue;
            }

            // 2) compute timestamps
            $start = Carbon::parse($match['start_time']);
            $createdAt = $start->copy()->subMinutes(20);
            $acceptedAt = $createdAt->copy()->addMinutes(rand(3, 6));

            // 3) stake & tokens
            $stake = rand(50, 300);
            $tokens = (int) round($stake * 0.1);

            // 4) status from challenger (white) POV
            // white_result: 'win', 'timeout', 'abandoned', 'stalemate', etc.
            $whiteResult = strtolower($match['white_result']);
            $status = match ($whiteResult) {
                'win'           => 'won',
                'stalemate', 'draw', 'agreed', 'repetition' => 'draw',
                default         => 'loss',
            };

            // 5) insert
            Challenge::create([
                'user_id'          => $challenger->id,
                'opponent_id'      => $contender->id,
                'request_state'    => 'accepted',     // since both are ready
                'position'         => 'challenger',
                'views'            => 0,
                'challenge_status' => 'pending',
                'stake'            => $stake,
                'tokens'           => $tokens,
                'platform_id'      => 1,
                'time_control'     => '5+0',
                'challenger_ready' => 1,
                'contender_ready'  => 1,
                'accepted_at'      => $acceptedAt,
                'rejected_at'      => null,
                'canceled_at'      => null,
                'created_at'       => $createdAt,
                'updated_at'       => $createdAt,
            ]);
        }

        return true;
    }

    public function load_played_users_test_matches(): bool
    {
        $matches = $this->getData('matches_test_users.json');

        foreach ($matches as $match) {
            Challenge::create($match);
        }

        return true;
    }

}

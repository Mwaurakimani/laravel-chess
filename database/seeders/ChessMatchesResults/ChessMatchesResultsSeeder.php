<?php

namespace Database\Seeders\ChessMatchesResults;

use App\Models\Challenge;
use App\Models\ChessMatchesResults;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\Classes\DataExtractor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class ChessMatchesResultsSeeder extends Seeder
{
    use DataExtractor;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            $matches = $this->getData('matches_all_users.json');

            foreach ($matches as $match) {
                // 1) Parse start_time into Carbon
                $start = Carbon::parse($match['start_time']);

                // 2) Find the two users
                $whiteUser = User::where('chess_com_link', $match['white'])->first();
                $blackUser = User::where('chess_com_link', $match['black'])->first();

                if (! $whiteUser || ! $blackUser) {
                    Log::warning("Skipping game {$match['match_link']}: user not found.");
                    continue;
                }

                // 3) Compute the expected challenge created_at (2 min before)
                $expectedCreated = $start->copy()->subMinutes(14);

                // 4) Lookup the matching challenge
                $challenge = Challenge::where('user_id', $whiteUser->id)
                    ->where('opponent_id', $blackUser->id)
                    ->whereBetween('created_at', [
                        $expectedCreated->copy()->subMinute(),
                        $expectedCreated->copy()->addMinute(),
                    ])
                    ->first();

                if (!$challenge) {
                    Log::warning("No matching challenge for game {$match['match_link']} at {$start->toDateTimeString()}");
                    $challengeId = null;
                    continue;
                } else {
                    $challengeId = $challenge->id;
                }

                // 5) Inject challenge_id and create record
                ChessMatchesResults::create(array_merge($match, [
                    'challenge_id' => $challengeId,
                ]));
            }
        } catch (\Exception $e) {
            Log::error('ChessMatchesResultsSeeder failed: ' . $e->getMessage());
            throw $e;
        }
    }
}

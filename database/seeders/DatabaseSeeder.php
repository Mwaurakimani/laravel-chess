<?php

namespace Database\Seeders;

use App\Models\User;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Seeders\ChallengeSeeder\ChallengeSeeder;
use Database\Seeders\ChessMatchesResults\ChessMatchesResultsSeeder;
use Database\Seeders\Platforms\PlatformSeeder;
use Database\Seeders\Roles\RoleSeeder;
use Database\Seeders\users\UsersSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->call([
                RoleSeeder::class,
                PlatformSeeder::class,
                UsersSeeder::class,
                ChallengeSeeder::class,
//                ChessMatchesResultsSeeder::class
            ]);
        });
    }

}

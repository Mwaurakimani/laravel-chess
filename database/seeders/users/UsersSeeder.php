<?php

namespace Database\Seeders\users;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\Classes\DataExtractor;

class UsersSeeder extends Seeder
{
    use DataExtractor;

    public function run(): void
    {
        try {
            $users = $this->getData('users.json');

            foreach ($users as &$user) {
                $user['password'] = Hash::make($user['password'] ?? 'password');
                $user['balance'] = 5000;
                $user['token_balance'] = 500;
                $user['account_status'] = 'active';
                $user['roles']  = json_encode($user['roles']);
            }

            User::insert($users);

//            $this->initUsers(); // ← Call extra 10-user seeder
        } catch (\Exception $e) {
            logger()->error('UsersSeeder failed: ' . $e->getMessage());
        }
    }

    public function initUsers(): void
    {
        $users = [];

        // Create 10 additional users
        for ($i = 1; $i <= 10; $i++) {
            $users[] = [
                'name'           => "User $i",
                'email'          => "user$i@example.com",
                'phone'          => '2547' . rand(10000000, 99999999),
                'password'       => Hash::make('password'),
                'balance'        => rand(1000, 10000),
                'token_balance'  => rand(20, 200),
                'lichess_link'   => "user$i",
                'chess_com_link' => "user$i",
                'account_status' => 'active',
                'roles'          => json_encode($i <= 3 ? ['moderator'] : ['player']),
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        }

        User::insert($users);
    }
}

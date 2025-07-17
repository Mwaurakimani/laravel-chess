<?php

namespace Database\Seeders\Roles;

use Database\Seeders\Classes\DataExtractor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    use DataExtractor;

    /**
     * @throws \Exception
     */
    public function run(): void
    {
        try {
            $roles = $this->getData('roles.json');

            foreach ($roles as $role) {
                Role::firstOrCreate($role); // pass the entire array directly
            }
        } catch (\Exception $e) {
            logger()->error('RoleSeeder failed: ' . $e->getMessage());
        }
    }


}

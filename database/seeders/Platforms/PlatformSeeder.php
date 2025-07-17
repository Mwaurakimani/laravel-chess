<?php

namespace Database\Seeders\Platforms;

use Illuminate\Database\Seeder;
use App\Models\Platform;
use Database\Seeders\Classes\DataExtractor;

class PlatformSeeder extends Seeder
{
    use DataExtractor;

    public function run(): void
    {
        try {
            $platforms = $this->getData('platforms.json');

            // Append timestamps
            $now = now()->subDay();
            foreach ($platforms as $i => &$platform) {
                $platform['created_at'] = $now->copy()->addSeconds($i * 10);
                $platform['updated_at'] = $now->copy()->addSeconds($i * 10);
            }

            Platform::upsert($platforms, ['id'], ['name', 'link', 'status', 'updated_at']);
        } catch (\Exception $e) {
            logger()->error('PlatformSeeder failed: ' . $e->getMessage());
        }
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\FeatureName;
use App\Models\FeatureFlag;
use Illuminate\Database\Seeder;

class FeatureFlagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'feature_name' => FeatureName::SCHEDULE_EXPORT,
                'status' => false,
            ],
            [
                'feature_name' => FeatureName::SCHEDULE_IMPORT,
                'status' => false,
            ],
            [
                'feature_name' => FeatureName::CREDITOR_COMMUNICATION,
                'status' => false,
            ],
        ];

        FeatureFlag::query()->insert($data);
    }
}

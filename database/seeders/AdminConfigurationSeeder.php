<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AdminConfigurationSlug;
use App\Models\AdminConfiguration;
use Illuminate\Database\Seeder;

class AdminConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timestamp = now();

        $data = [
            [
                'name' => AdminConfigurationSlug::EMAIL_RATE->displayName(),
                'slug' => AdminConfigurationSlug::EMAIL_RATE,
                'value' => '0.1',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ];

        AdminConfiguration::query()->insert($data);
    }
}

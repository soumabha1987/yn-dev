<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NewReportType;
use App\Enums\ReportHistoryStatus;
use App\Models\ReportHistory;
use App\Models\Subclient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportHistory>
 */
class ReportHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'report_type' => fake()->randomElement(NewReportType::values()),
            'subclient_id' => Subclient::factory(),
            'status' => fake()->randomElement(ReportHistoryStatus::values()),
            'records' => fake()->randomNumber(),
            'start_date' => $startDate = fake()->date(),
            'end_date' => Carbon::parse($startDate)->addDays(fake()->numberBetween(1, 60)),
            'downloaded_file_name' => fn ($attributes) => $attributes['status'] === ReportHistoryStatus::FAILED->value ? null : fake()->filePath(),
        ];
    }
}

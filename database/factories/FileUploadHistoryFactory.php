<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FileUploadHistoryStatus;
use App\Enums\FileUploadHistoryType;
use App\Models\Company;
use App\Models\FileUploadHistory;
use App\Models\Subclient;
use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FileUploadHistory>
 */
class FileUploadHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'subclient_id' => Subclient::factory(),
            'uploaded_by' => User::factory(),
            'filename' => fake()->randomNumber(4) . '.' . fake()->fileExtension(),
            'status' => fake()->randomElement(FileUploadHistoryStatus::values()),
            'cfpb_hidden' => fake()->boolean(),
            'failed_filename' => function (array $attributes) {
                $attributes['status'] = $attributes['status'] instanceof BackedEnum ? $attributes['status']->value : $attributes['status'];

                return $attributes['status'] === FileUploadHistoryStatus::FAILED ? $attributes['filename'] : null;
            },
            'type' => fake()->randomElement(FileUploadHistoryType::values()),
            'total_records' => fake()->randomNumber(3, true),
            'processed_count' => function (array $attributes) {
                $attributes['status'] = $attributes['status'] instanceof BackedEnum ? $attributes['status']->value : $attributes['status'];

                if (in_array($attributes['status'], [FileUploadHistoryStatus::FAILED, FileUploadHistoryStatus::VALIDATING])) {
                    return max(0, $attributes['total_records'] - 100);
                }

                return $attributes['total_records'];
            },
            'failed_count' => function (array $attributes) {
                $attributes['status'] = $attributes['status'] instanceof BackedEnum ? $attributes['status']->value : $attributes['status'];

                if ($attributes['status'] === FileUploadHistoryStatus::FAILED) {
                    return max(0, $attributes['total_records'] - 120);
                }

                return 0;
            },
            'is_sftp_import' => fake()->boolean(),
            'is_hidden' => fake()->boolean(),
        ];
    }
}

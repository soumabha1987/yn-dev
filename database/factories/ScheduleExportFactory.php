<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NewReportType;
use App\Enums\ScheduleExportDeliveryType;
use App\Enums\ScheduleExportFrequency;
use App\Models\Company;
use App\Models\CsvHeader;
use App\Models\ScheduleExport;
use App\Models\SftpConnection;
use App\Models\Subclient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduleExport>
 */
class ScheduleExportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $deliveryType = fake()->randomElement(ScheduleExportDeliveryType::values());

        return [
            'company_id' => Company::factory(),
            'subclient_id' => Subclient::factory(),
            'user_id' => User::factory(),
            'sftp_connection_id' => $deliveryType === ScheduleExportDeliveryType::SFTP->value ? SftpConnection::factory()->state(['enabled' => true]) : null,
            'csv_header_id' => $deliveryType === ScheduleExportDeliveryType::SFTP->value ? CsvHeader::factory()->state(['is_mapped' => true]) : null,
            'report_type' => fake()->randomElement(NewReportType::values()),
            'frequency' => fake()->randomElement(ScheduleExportFrequency::values()),
            'pause' => fake()->boolean(),
            'emails' => $deliveryType === ScheduleExportDeliveryType::EMAIL->value
                ? collect()->times(fake()->numberBetween(1, 5), fn () => fake()->unique()->email())->all()
                : null,
        ];
    }
}

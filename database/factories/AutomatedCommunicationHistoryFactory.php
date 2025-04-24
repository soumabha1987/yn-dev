<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AutomatedCommunicationHistoryStatus;
use App\Models\AutomatedCommunicationHistory;
use App\Models\CommunicationStatus;
use App\Models\Company;
use App\Models\Consumer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomatedCommunicationHistory>
 */
class AutomatedCommunicationHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'communication_status_id' => CommunicationStatus::factory(),
            'consumer_id' => Consumer::factory(),
            'company_id' => Company::factory(),
            'status' => fake()->randomElement(AutomatedCommunicationHistoryStatus::values()),
        ];
    }
}

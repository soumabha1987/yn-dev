<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CommunicationCode;
use App\Enums\CommunicationStatusTriggerType;
use App\Models\AutomatedTemplate;
use App\Models\CommunicationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunicationStatus>
 */
class CommunicationStatusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trigger_type' => fake()->randomElement(CommunicationStatusTriggerType::values()),
            'automated_email_template_id' => AutomatedTemplate::factory()->email(),
            'automated_sms_template_id' => AutomatedTemplate::factory()->sms(),
            'code' => fake()->randomElement(CommunicationCode::values()),
            'description' => fake()->sentence(50),
            'trigger_description' => fake()->sentence(50),
        ];
    }
}

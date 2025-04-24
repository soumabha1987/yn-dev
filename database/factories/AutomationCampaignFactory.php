<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AutomationCampaignFrequency;
use App\Models\AutomationCampaign;
use App\Models\CommunicationStatus;
use BackedEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<AutomationCampaign>
 */
class AutomationCampaignFactory extends Factory
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
            'frequency' => fake()->randomElement(AutomationCampaignFrequency::values()),
            'weekly' => function (array $attributes) {
                $attributes['frequency'] = $attributes['frequency'] instanceof BackedEnum ? $attributes['frequency']->value : $attributes['frequency'];

                $attributes['frequency'] === AutomationCampaignFrequency::WEEKLY->value
                    ? fake()->randomElement(array_keys(Carbon::getDays()))
                    : null;
            },
            'hourly' => function (array $attributes) {
                $attributes['frequency'] = $attributes['frequency'] instanceof BackedEnum ? $attributes['frequency']->value : $attributes['frequency'];

                $attributes['frequency'] === AutomationCampaignFrequency::HOURLY->value
                   ? fake()->randomElement([12, 36, 48, 72])
                   : null;
            },
            'enabled' => fake()->boolean(),
            'start_at' => fake()->dateTimeBetween('-2 years', '+2 years'),
        ];
    }
}

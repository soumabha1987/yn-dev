<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CampaignTracker;
use App\Models\CampaignTrackerConsumer;
use App\Models\Consumer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignTrackerConsumer>
 */
class CampaignTrackerConsumerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_tracker_id' => CampaignTracker::factory(),
            'consumer_id' => Consumer::factory(),
            'click' => fake()->randomNumber(),
            'cost' => fake()->randomNumber(),
        ];
    }
}

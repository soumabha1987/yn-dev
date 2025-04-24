<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignTracker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignTracker>
 */
class CampaignTrackerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'clicks_count' => $clickCount = fake()->randomNumber(),
            'pif_completed_count' => $pifCount = fake()->randomNumber(),
            'ppl_completed_count' => $pplCount = fake()->randomNumber(),
            'custom_offer_count' => $customCount = fake()->randomNumber(),
            'no_pay_count' => $noPayCount = fake()->randomNumber(),
            'delivered_count' => $deliveredCount = fake()->randomNumber(),
            'consumer_count' => $clickCount + $pifCount + $pplCount + $customCount + $noPayCount + $deliveredCount + fake()->randomNumber(),
            'total_balance_of_consumers' => fake()->randomNumber(),
        ];
    }
}

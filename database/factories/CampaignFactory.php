<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CampaignFrequency;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\Group;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
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
            'company_id' => Company::factory(),
            'group_id' => Group::factory(),
            'template_id' => Template::factory(),
            'frequency' => fake()->randomElement(CampaignFrequency::values()),
            'day_of_week' => fake()->numberBetween(0, 6),
            'day_of_month' => fake()->numberBetween(1, 31),
            'start_date' => today()->addDays($days = fake()->numberBetween(1, 100))->toDateString(),
            'end_date' => today()->addDays(fake()->numberBetween($days, 350))->toDateString(),
        ];
    }
}

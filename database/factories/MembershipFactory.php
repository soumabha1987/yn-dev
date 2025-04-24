<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MembershipFrequency;
use App\Models\Membership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'price' => fake()->randomFloat(2, 1, 1000),
            'e_letter_fee' => fake()->randomFloat(2, 0.1, 25),
            'description' => fake()->sentence(),
            'frequency' => fake()->randomElement(MembershipFrequency::values()),
            'upload_accounts_limit' => fake()->numberBetween(1, 1000),
            'fee' => fake()->numberBetween(1, 100),
            'e_letter_fee' => fake()->randomFloat(2, 0.05, 25),
            'status' => true,
            'meta_data' => [],
        ];
    }
}

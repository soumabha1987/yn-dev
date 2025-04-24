<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GroupConsumerState;
use App\Models\Company;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
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
            'name' => fake()->name(),
            'description' => fake()->text(),
            'consumer_state' => fake()->randomElement(GroupConsumerState::values()),
            'pif_balance_discount_percent' => fake()->numberBetween(0, 99),
            'ppa_balance_discount_percent' => fake()->numberBetween(0, 99),
            'min_monthly_pay_percent' => fake()->numberBetween(1, 99),
            'max_days_first_pay' => fake()->numberBetween(1, 999),
            'minimum_settlement_percentage' => fake()->numberBetween(2, 20),
            'minimum_payment_plan_percentage' => fake()->numberBetween(2, 20),
            'max_first_pay_days' => fake()->numberBetween(100, 999),
        ];
    }
}

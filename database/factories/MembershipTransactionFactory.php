<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MembershipTransactionStatus;
use App\Models\Company;
use App\Models\Membership;
use App\Models\MembershipTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipTransaction>
 */
class MembershipTransactionFactory extends Factory
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
            'membership_id' => Membership::factory(),
            'status' => fake()->randomElement(MembershipTransactionStatus::values()),
            'price' => fake()->randomFloat(2, max: 2000),
            'tilled_transaction_id' => fake()->uuid(),
            'plan_end_date' => fake()->dateTime(),
            'partner_revenue_share' => fake()->numberBetween(1, 99999),
        ];
    }
}

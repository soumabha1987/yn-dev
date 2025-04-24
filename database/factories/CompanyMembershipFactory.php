<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CompanyMembershipStatus;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Membership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyMembership>
 */
class CompanyMembershipFactory extends Factory
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
            'next_membership_plan_id' => Membership::factory(),
            'status' => fake()->randomElement(CompanyMembershipStatus::values()),
            'current_plan_start' => fake()->dateTimeBetween('now', '+1 years'),
            'current_plan_end' => fake()->dateTimeBetween('+2 years', '+5 years'),
            'auto_renew' => true,
        ];
    }
}

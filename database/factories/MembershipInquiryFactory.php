<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MembershipInquiryStatus;
use App\Models\Company;
use App\Models\MembershipInquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipInquiry>
 */
class MembershipInquiryFactory extends Factory
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
            'status' => fake()->randomElement(MembershipInquiryStatus::values()),
            'description' => fake()->sentence(),
            'accounts_in_scope' => fake()->randomNumber(),
        ];
    }
}

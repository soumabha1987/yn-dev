<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Reason;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reason>
 */
class ReasonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $labels = [
            'Please dispute. This is not my Account',
            'I never plan to pay this Account',
            'Bankruptcy',
            'I\'m deployed in the military',
            'I\'m unemployed and would like to pay later',
            'Deceased',
            'Need Credit Counseling. Too many bills',
            'Need Consolidation Loan. Too many bills',
            'Other',
        ];

        return [
            'label' => fake()->randomElement($labels),
            'is_system' => fake()->boolean(),
        ];
    }
}

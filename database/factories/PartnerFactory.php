<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Partner>
 */
class PartnerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'contact_first_name' => fake()->firstName(),
            'contact_last_name' => fake()->lastName(),
            'contact_email' => fake()->unique()->safeEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'report_emails' => collect()->times(fake()->numberBetween(1, 5), fn () => fake()->unique()->email())->all(),
            'revenue_share' => fake()->numberBetween(1, 99),
            'creditors_quota' => fake()->numberBetween(1, 99999),
            'registration_code' => fake()->word(),
        ];
    }
}

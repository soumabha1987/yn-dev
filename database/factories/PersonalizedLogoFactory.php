<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\PersonalizedLogo;
use App\Models\Subclient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonalizedLogo>
 */
class PersonalizedLogoFactory extends Factory
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
            'subclient_id' => fake()->boolean() ? Subclient::factory() : null,
            'primary_color' => fake()->hexColor(),
            'secondary_color' => fake()->hexColor(),
            'size' => fake()->numberBetween(160, 520),
            'customer_communication_link' => fake()->url(),
        ];
    }
}

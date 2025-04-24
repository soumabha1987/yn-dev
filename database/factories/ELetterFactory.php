<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\ELetter;
use App\Models\Subclient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ELetter>
 */
class ELetterFactory extends Factory
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
            'subclient_id' => fake()->boolean(25) ? Subclient::factory() : null,
            'message' => fake()->sentence(),
            'disabled' => fake()->boolean(10),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Consumer;
use App\Models\ConsumerPersonalizedLogo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsumerPersonalizedLogo>
 */
class ConsumerPersonalizedLogoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'consumer_id' => Consumer::factory(),
            'primary_color' => fake()->hexColor,
            'secondary_color' => fake()->hexColor,
        ];
    }
}

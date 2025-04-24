<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Consumer;
use App\Models\ConsumerELetter;
use App\Models\ELetter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsumerELetter>
 */
class ConsumerELetterFactory extends Factory
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
            'e_letter_id' => ELetter::factory(),
            'read_by_consumer' => fake()->boolean(),
        ];
    }
}

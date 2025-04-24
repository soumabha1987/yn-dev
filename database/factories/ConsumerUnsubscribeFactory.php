<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Consumer;
use App\Models\ConsumerUnsubscribe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsumerUnsubscribe>
 */
class ConsumerUnsubscribeFactory extends Factory
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
            'consumer_id' => Consumer::factory(),
            'email' => fake()->email(),
            'phone' => fake()->phoneNumber(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Consumer;
use App\Models\ConsumerLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsumerLog>
 */
class ConsumerLogFactory extends Factory
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
            'log_message' => fake()->sentence(),
        ];
    }
}

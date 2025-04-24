<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CustomContentType;
use App\Models\Company;
use App\Models\CustomContent;
use App\Models\Subclient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomContent>
 */
class CustomContentFactory extends Factory
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
            'subclient_id' => Subclient::factory(),
            'type' => fake()->randomElement(CustomContentType::values()),
            'content' => fake()->randomHtml(),
        ];
    }
}

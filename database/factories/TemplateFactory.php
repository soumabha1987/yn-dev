<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TemplateType;
use App\Models\Company;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Template>
 */
class TemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_id' => Company::factory(),
            'name' => fake()->name(),
            'type' => fake()->randomElement(TemplateType::values()),
            'subject' => fake()->word(),
            'description' => fake()->text(),
        ];
    }
}

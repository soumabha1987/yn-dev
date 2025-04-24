<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AutomatedTemplateType;
use App\Models\AutomatedTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomatedTemplate>
 */
class AutomatedTemplateFactory extends Factory
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
            'name' => $this->faker->name,
            'type' => $this->faker->randomElement(AutomatedTemplateType::values()),
            'subject' => $this->faker->sentence(2),
            'content' => $this->faker->sentence(20),
        ];
    }

    public function email(): self
    {
        return $this->state(['type' => AutomatedTemplateType::EMAIL]);
    }

    public function sms(): self
    {
        return $this->state(['type' => AutomatedTemplateType::SMS]);
    }
}

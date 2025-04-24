<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\State;
use App\Models\ConsumerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsumerProfile>
 */
class ConsumerProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'state' => fake()->randomElement(State::values()),
            'zip' => fake()->randomNumber(5, true),
            'mobile' => fake()->randomElement(['9004590023', '9003490056', '9005690056', '9005890058']),
            'landline' => fake()->randomNumber(6, true),
            'email' => fake()->safeEmail(),
            'text_permission' => fake()->boolean(),
            'email_permission' => fake()->boolean(),
            'landline_call_permission' => fake()->boolean(),
            'usps_permission' => fake()->boolean(),
            'image' => fake()->imageUrl(),
            'username' => fake()->userName(),
            'is_communication_updated' => fake()->boolean(),
            'verified_at' => fake()->dateTimeBetween('-2 years', '+2 years'),
        ];
    }
}

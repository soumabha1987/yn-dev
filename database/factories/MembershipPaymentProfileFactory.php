<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\State;
use App\Models\Company;
use App\Models\MembershipPaymentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MembershipPaymentProfile>
 */
class MembershipPaymentProfileFactory extends Factory
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
            'tilled_payment_method_id' => fake()->uuid(),
            'tilled_customer_id' => fake()->uuid(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'last_four_digit' => Str::substr(fake()->creditCardNumber(), -4),
            'expiry' => fake()->date('m/Y'),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->randomElement(State::values()),
            'zip' => fake()->randomNumber(5, strict: true),
            'response' => [],
        ];
    }
}

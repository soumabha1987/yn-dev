<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MerchantType;
use App\Enums\State;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\ExternalPaymentProfile;
use App\Models\Subclient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalPaymentProfile>
 */
class ExternalPaymentProfileFactory extends Factory
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
            'subclient_id' => fake()->boolean() ? Subclient::factory() : null,
            'consumer_id' => Consumer::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'method' => fake()->randomElement(MerchantType::values()),
            'account_number' => fake()->randomNumber(5, true),
            'routing_number' => fake()->randomElement(['021000021', '121042882', '011103093', '011000138', '021101108']),
            'last_four_digit' => fake()->randomNumber(4, true),
            'expiry' => fake()->date('m/Y'),
            'profile_id' => fake()->uuid(),
            'payment_profile_id' => fake()->uuid(),
            'stripe_payment_method_id' => fake()->uuid(),
            'stripe_customer_id' => fake()->uuid(),
            'tilled_customer_id' => fake()->uuid(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->randomElement(State::values()),
            'zip' => fake()->postcode(),
        ];
    }
}

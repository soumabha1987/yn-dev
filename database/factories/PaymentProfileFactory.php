<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MerchantType;
use App\Enums\State;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\Merchant;
use App\Models\PaymentProfile;
use App\Models\Subclient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentProfile>
 */
class PaymentProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $routingNumbers = ['021000021', '121042882', '011103093', '011000138', '021101108'];

        return [
            'company_id' => Company::factory(),
            'consumer_id' => Consumer::factory(),
            'subclient_id' => Subclient::factory(),
            'merchant_id' => Merchant::factory(),
            'method' => fake()->randomElement(MerchantType::values()),
            'last4digit' => fake()->randomNumber(4, true),
            'expirity' => fake()->date('m/Y'),
            'gateway_token' => fake()->uuid(),
            'account_number' => fake()->randomNumber(5, true),
            'routing_number' => fake()->randomElement($routingNumbers),
            'profile_id' => fake()->uuid(),
            'payment_profile_id' => fake()->uuid(),
            'shipping_profile_id' => fake()->uuid(),
            'fname' => fake()->firstName(),
            'lname' => fake()->lastName(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'state' => fake()->randomElement(State::values()),
            'zip' => fake()->postcode(),
            'gateway_type' => fake()->word(),
            'stripe_payment_method_id' => fake()->uuid(),
            'stripe_customer_id' => fake()->uuid(),
        ];
    }
}

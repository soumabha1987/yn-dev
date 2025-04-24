<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Consumer;
use App\Models\PaymentProfile;
use App\Models\StripePaymentDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StripePaymentDetail>
 */
class StripePaymentDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_profile_id' => PaymentProfile::factory(),
            'consumer_id' => Consumer::factory(),
            'stripe_payment_method_id' => fake()->uuid(),
            'stripe_customer_id' => fake()->uuid(),
        ];
    }
}

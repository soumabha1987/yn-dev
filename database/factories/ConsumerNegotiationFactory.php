<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InstallmentType;
use App\Enums\NegotiationType;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsumerNegotiation>
 */
class ConsumerNegotiationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'consumer_id' => Consumer::factory(),
            'company_id' => Company::factory(),
            'negotiation_type' => fake()->randomElement(NegotiationType::values()),
            'payment_plan_current_balance' => fake()->randomNumber(3, strict: false),
            'one_time_settlement' => fake()->randomNumber(3, strict: false),
            'negotiate_amount' => fake()->randomNumber(3, strict: false),
            'monthly_amount' => fake()->randomNumber(3, strict: false),
            'no_of_installments' => fake()->randomNumber(1, strict: false),
            'last_month_amount' => fake()->randomNumber(3, strict: false),
            'installment_type' => fake()->randomElement(InstallmentType::values()),
            'first_pay_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'offer_accepted' => fake()->boolean(),
            'offer_accepted_at' => fake()->dateTimeBetween('-2 years', 'now'),
            'counter_one_time_amount' => fake()->randomNumber(3, strict: false),
            'counter_negotiate_amount' => fake()->randomNumber(3, strict: false),
            'counter_monthly_amount' => fake()->randomNumber(3, strict: false),
            'counter_no_of_installments' => fake()->randomNumber(1, strict: false),
            'counter_last_month_amount' => fake()->randomNumber(3, strict: false),
            'counter_first_pay_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'counter_offer_accepted' => fake()->boolean(),
            'communication_data' => fake()->sentence(),
            'reason' => fake()->sentence(),
            'note' => fake()->sentence(),
            'active_negotiation' => fake()->boolean(),
            'account_number' => fake()->randomNumber(5, true),
            'approved_by' => fake()->word(),
            'counter_note' => fake()->sentence(),
        ];
    }
}

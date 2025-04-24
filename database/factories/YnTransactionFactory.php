<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MembershipTransactionStatus;
use App\Models\Company;
use App\Models\YnTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<YnTransaction>
 */
class YnTransactionFactory extends Factory
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
            'amount' => fake()->randomFloat(2, max: 9999),
            'billing_cycle_start' => fake()->dateTime('-7 days'),
            'billing_cycle_end' => fake()->dateTime(),
            'sms_count' => fake()->numberBetween(0, 10),
            'phone_no_count' => fake()->numberBetween(0, 10),
            'email_count' => fake()->numberBetween(0, 10),
            'eletter_count' => fake()->numberBetween(0, 10),
            'status' => fake()->randomElement(MembershipTransactionStatus::values()),
            'sms_cost' => fake()->randomFloat(0, max: 99),
            'email_cost' => fake()->randomFloat(0, max: 99),
            'eletter_cost' => fake()->randomFloat(0, max: 99),
            'rnn_invoice_id' => fake()->randomNumber(4, true),
            'reference_number' => fake()->randomNumber(4, true),
            'superadmin_process' => fake()->boolean(),
            'partner_revenue_share' => fake()->numberBetween(1, 99999),
        ];
    }
}

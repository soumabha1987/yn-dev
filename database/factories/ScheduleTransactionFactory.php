<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\ExternalPaymentProfile;
use App\Models\PaymentProfile;
use App\Models\ScheduleTransaction;
use App\Models\Subclient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduleTransaction>
 */
class ScheduleTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_id' => fake()->randomNumber(6, true),
            'transaction_type' => fake()->randomElement(TransactionType::values()),
            'company_id' => Company::factory(),
            'consumer_id' => Consumer::factory(),
            'subclient_id' => Subclient::factory(),
            'schedule_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'payment_profile_id' => PaymentProfile::factory(),
            'external_payment_profile_id' => ExternalPaymentProfile::factory(),
            'status' => fake()->randomElement(TransactionStatus::values()),
            'attempt_count' => fake()->randomNumber(),
            'last_attempted_at' => fake()->dateTimeBetween(),
            'status_code' => fake()->word(),
            'amount' => fake()->randomFloat(2, 0, 1000),
            'previous_schedule_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'schedule_time' => fake()->date('H:i:s'),
            'stripe_payment_detail_id' => fake()->numberBetween(10, 99),
            'revenue_share_percentage' => fake()->numberBetween(1, 99),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MerchantType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\ExternalPaymentProfile;
use App\Models\PaymentProfile;
use App\Models\Subclient;
use App\Models\Transaction;
use App\Models\YnTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'yn_transaction_id' => YnTransaction::factory(),
            'consumer_id' => Consumer::factory(),
            'subclient_id' => Subclient::factory(),
            'company_id' => Company::factory(),
            'payment_profile_id' => PaymentProfile::factory(),
            'external_payment_profile_id' => ExternalPaymentProfile::factory(),
            'transaction_id' => fake()->uuid(),
            'transaction_type' => fake()->randomElement(TransactionType::values()),
            'status' => fake()->randomElement(TransactionStatus::values()),
            'status_code' => fake()->text(5),
            'amount' => fake()->randomFloat(2, max: 9999),
            'processing_charges' => fake()->randomFloat(2, max: 10),
            'rnn_share' => fake()->randomFloat(2, max: 9999),
            'company_share' => fake()->randomFloat(2, max: 9999),
            'payment_mode' => fake()->randomElement(MerchantType::values()),
            'last4digit' => fake()->randomNumber(4, true),
            'rnn_share_pass' => fake()->dateTimeBetween('-2 years'),
            'rnn_invoice_id' => fake()->randomNumber(4, true),
            'superadmin_process' => fake()->boolean(),
        ];
    }
}

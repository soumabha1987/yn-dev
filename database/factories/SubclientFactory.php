<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BankAccountType;
use App\Enums\CompanyCategory;
use App\Enums\IndustryType;
use App\Enums\State;
use App\Enums\SubclientStatus;
use App\Enums\YearlyVolumeRange;
use App\Models\Company;
use App\Models\Subclient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subclient>
 */
class SubclientFactory extends Factory
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
            'subclient_name' => fake()->name(),
            'unique_identification_number' => fake()->uuid(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'status' => fake()->randomElement(SubclientStatus::values()),
            'has_merchant' => fake()->boolean(),
            'pif_balance_discount_percent' => fake()->randomFloat(2),
            'ppa_balance_discount_percent' => fake()->randomFloat(2),
            'min_monthly_pay_percent' => fake()->randomFloat(2, 1, 100),
            'max_days_first_pay' => fake()->randomDigitNotZero(),
            'minimum_settlement_percentage' => fake()->numberBetween(2, 20),
            'minimum_payment_plan_percentage' => fake()->numberBetween(2, 20),
            'max_first_pay_days' => fake()->numberBetween(100, 999),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->randomElement(State::values()),
            'zip' => fake()->randomNumber(5, strict: true),
            'tilled_merchant_account_id' => fake()->uuid(),
            'tilled_webhook_secret' => fake()->password(),
            'tilled_profile_completed_at' => fake()->dateTimeBetween(),
            'tilled_customer_id' => fake()->randomNumber(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'dob' => fake()->date(),
            'account_holder_name' => fake()->name(),
            'bank_name' => fake()->company(),
            'bank_account_type' => fake()->randomElement(BankAccountType::values()),
            'bank_account_number' => fake()->randomNumber(2, true),
            'bank_routing_number' => fake()->randomElement(['021000021', '121042882', '011103093', '011000138', '021101108']),
            'average_transaction_amount' => fake()->randomFloat(2, 100, 10000),
            'legal_name' => fake()->company(),
            'fed_tax_id' => fake()->randomNumber(9, strict: true),
            'statement_descriptor' => fake()->word(),
            'yearly_volume_range' => fake()->randomElement(YearlyVolumeRange::values()),
            'job_title' => fake()->jobTitle(),
            'percentage_shareholding' => fake()->randomFloat(2, 1, 100),
            'company_category' => fake()->randomElement(CompanyCategory::values()),
            'industry_type' => fake()->randomElement(IndustryType::values()),
            'owner_full_name' => fake()->name(),
            'owner_email' => fake()->email(),
            'owner_phone' => fake()->phoneNumber(),
            'owner_address' => fake()->streetAddress(),
            'owner_city' => fake()->word(),
            'owner_state' => fake()->randomElement(State::values()),
            'owner_zip' => fake()->randomNumber(5, strict: true),
            'approved_at' => fake()->dateTimeBetween('-2 years', '2 years'),
        ];
    }
}

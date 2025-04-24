<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BankAccountType;
use App\Enums\CompanyBusinessCategory;
use App\Enums\CompanyCategory;
use App\Enums\CompanyStatus;
use App\Enums\DebtType;
use App\Enums\IndustryType;
use App\Enums\State;
use App\Enums\Timezone;
use App\Enums\YearlyVolumeRange;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'approved_by' => User::first()?->id,
            'company_name' => fake()->company(),
            'status' => fake()->randomElement(CompanyStatus::values()),
            'pif_balance_discount_percent' => fake()->randomFloat(2, 1, 100),
            'ppa_balance_discount_percent' => fake()->randomFloat(2, 1, 100),
            'min_monthly_pay_percent' => fake()->randomFloat(2, 1, 100),
            'max_days_first_pay' => fake()->randomDigitNotZero(),
            'minimum_settlement_percentage' => fake()->numberBetween(2, 20),
            'minimum_payment_plan_percentage' => fake()->numberBetween(2, 20),
            'max_first_pay_days' => fake()->numberBetween(30, 999),
            'address' => fake()->streetAddress(),
            'city' => fake()->word(),
            'state' => fake()->randomElement(State::values()),
            'zip' => fake()->randomNumber(5, strict: true),
            'country' => fake()->country(),
            'timezone' => fake()->randomElement(Timezone::values()),
            'tilled_merchant_account_id' => fake()->uuid(),
            'tilled_profile_completed_at' => fake()->dateTimeBetween(),
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
            'business_category' => fake()->randomElement(CompanyBusinessCategory::values()),
            'debt_type' => fake()->randomElement(DebtType::values()),
            'owner_full_name' => fake()->name(),
            'owner_email' => fake()->email(),
            'owner_phone' => fake()->phoneNumber(),
            'owner_address' => fake()->streetAddress(),
            'owner_city' => fake()->word(),
            'owner_state' => fake()->randomElement(State::values()),
            'owner_zip' => fake()->randomNumber(5, strict: true),
            'billing_email' => fake()->companyEmail(),
            'billing_phone' => fake()->phoneNumber(),
            'billing_address' => fake()->streetAddress(),
            'billing_city' => fake()->word(),
            'billing_state' => fake()->randomElement(State::values()),
            'billing_zip' => fake()->randomNumber(5, strict: true),
            'tilled_payment_response' => [],
            'eletter_rate' => fake()->randomFloat(2),
            'sms_rate' => fake()->randomFloat(2),
            'email_rate' => fake()->randomFloat(2),
            'approved_at' => fake()->dateTimeBetween(),
        ];
    }
}

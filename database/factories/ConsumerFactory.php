<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CompanyMembershipStatus;
use App\Enums\ConsumerStatus;
use App\Enums\State;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use App\Models\ConsumerProfile;
use App\Models\Reason;
use App\Models\Subclient;
use BackedEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consumer>
 */
class ConsumerFactory extends Factory
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
            'consumer_profile_id' => ConsumerProfile::factory(),
            'subclient_id' => Subclient::factory(),
            'reference_number' => fake()->numberBetween(100, 5000),
            'account_number' => fake()->bankAccountNumber,
            'member_account_number' => fake()->uuid(),
            'status' => fake()->randomElement(array_diff(ConsumerStatus::values(), [ConsumerStatus::RENEGOTIATE->value])),
            'last4ssn' => fake()->numberBetween(1000, 9999),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->name(),
            'last_name' => fake()->lastName(),
            'dob' => fake()->date(),
            'address1' => fake()->address(),
            'address2' => fake()->secondaryAddress(),
            'city' => fake()->city(),
            'state' => fake()->randomElement(State::values()),
            'zip' => fake()->numberBetween(100000, 999999),
            'mobile1' => fake()->phoneNumber(),
            'email1' => fake()->safeEmail(),
            'total_balance' => fake()->numberBetween(100, 2000),
            'current_balance' => fake()->numberBetween(100, 2000),
            'pif_discount_percent' => fake()->numberBetween(1, 10),
            'pay_setup_discount_percent' => fake()->numberBetween(1, 10),
            'min_monthly_pay_percent' => fake()->numberBetween(1, 10),
            'max_days_first_pay' => fake()->numberBetween(1, 10),
            'minimum_settlement_percentage' => fake()->numberBetween(1, 100),
            'minimum_payment_plan_percentage' => fake()->numberBetween(1, 100),
            'max_first_pay_days' => fake()->numberBetween(1, 10),
            'pass_through1' => fake()->firstName(),
            'pass_through2' => fake()->firstName(),
            'pass_through3' => fake()->firstName(),
            'pass_through4' => fake()->firstName(),
            'pass_through5' => fake()->firstName(),
            'last_login_at' => fake()->dateTimeBetween('-3 months', 'now'),
            'ppa_amount' => fake()->numberBetween(10, 100),
            'counter_offer' => fake()->boolean(),
            'offer_accepted' => fake()->boolean(),
            'payment_setup' => fake()->boolean(),
            'has_failed_payment' => fake()->boolean(),
            'invitation_link' => substr(fake()->url(), 0, 90),
            'disputed_at' => function (array $attributes) {
                $status = $attributes['status'] instanceof BackedEnum ? $attributes['status']->value : $attributes['status'];

                return $status === ConsumerStatus::DISPUTE->value ? fake()->dateTimeBetween('-1 year') : null;
            },
        ];
    }

    public function activeMembershipCompany(): Factory
    {
        return $this->state(fn (): array => [
            'company_id' => Company::factory()->has(CompanyMembership::factory([
                'status' => CompanyMembershipStatus::ACTIVE,
                'current_plan_end' => now()->addYear(),
            ])),
        ]);
    }

    public function configure(): self
    {
        return $this->afterCreating(function (Consumer $consumer): void {
            if ($consumer->status === ConsumerStatus::NOT_PAYING) {
                $reason = Reason::factory()->create();
                $consumer->update(['reason_id' => $reason->id]);
            }
        });
    }
}

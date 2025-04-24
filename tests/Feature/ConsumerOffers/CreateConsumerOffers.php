<?php

declare(strict_types=1);

namespace Tests\Feature\ConsumerOffers;

use App\Enums\CompanyStatus;
use App\Enums\ConsumerStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\NegotiationType;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\ConsumerProfile;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Support\Carbon;

trait CreateConsumerOffers
{
    private function createConsumerOffers(): User
    {
        $user = User::factory()
            ->for(Company::factory()->state([
                'status' => CompanyStatus::ACTIVE,
                'current_step' => CreditorCurrentStep::COMPLETED,
            ]))
            ->create(['subclient_id' => null]);

        Merchant::factory()->create(['company_id' => $user->company_id, 'subclient_id' => null]);

        CompanyMembership::factory()->create(['company_id' => $user->company_id]);

        $amount = 100;

        $commonConsumer = [
            'last_name' => 'test',
            'dob' => Carbon::create('1999', '11', '11'),
            'last4ssn' => '1111',
            'company_id' => $user->company_id,
            'subclient_id' => null,
            'current_balance' => $amount,
            'custom_offer' => true,
        ];

        $commonConsumerNegotiation = [
            'company_id' => $user->company_id,
            'active_negotiation' => true,
            'installment_type' => 'monthly',
            'payment_plan_current_balance' => null,
            'first_pay_date' => now(),
        ];

        $consumerProfile = ConsumerProfile::query()->create([
            'email' => fake()->safeEmail(),
            'mobile' => fake()->phoneNumber(),
            'text_permission' => false,
            'email_permission' => true,
        ]);

        // Create Offer
        ConsumerNegotiation::factory()
            ->for(Consumer::factory()->for($consumerProfile)->create([
                ...$commonConsumer,
                'first_name' => 'Create offer',
                'counter_offer' => false,
                'status' => ConsumerStatus::PAYMENT_SETUP->value,
                'offer_accepted' => false,
            ]))
            ->create([
                ...$commonConsumerNegotiation,
                'offer_accepted' => false,
                'negotiation_type' => NegotiationType::PIF->value,
                'one_time_settlement' => fake()->numberBetween(1, $amount - 1),
                'no_of_installments' => 1,
            ]);

        //Counter Offer
        ConsumerNegotiation::factory()
            ->for(Consumer::factory()->for($consumerProfile)->create([
                ...$commonConsumer,
                'first_name' => 'Counter offer',
                'counter_offer' => true,
                'status' => ConsumerStatus::PAYMENT_SETUP->value,
                'offer_accepted' => false,
            ]))
            ->create([
                ...$commonConsumerNegotiation,
                'offer_accepted' => false,
                'negotiation_type' => NegotiationType::PIF->value,
                'one_time_settlement' => fake()->numberBetween(1, $amount - 1),
                'no_of_installments' => 1,
                'counter_first_pay_date' => now(),
                'counter_one_time_amount' => $amount,
                'counter_no_of_installments' => 1,
            ]);

        //Creditor accept offer
        ConsumerNegotiation::factory()
            ->for(Consumer::factory()->for($consumerProfile)->create([
                ...$commonConsumer,
                'first_name' => 'Creditor accept offer',
                'counter_offer' => false,
                'status' => ConsumerStatus::PAYMENT_ACCEPTED->value,
                'offer_accepted' => true,
            ]))
            ->create([
                ...$commonConsumerNegotiation,
                'offer_accepted' => true,
                'negotiation_type' => NegotiationType::PIF->value,
                'one_time_settlement' => fake()->numberBetween(1, $amount),
                'no_of_installments' => 1,
                'offer_accepted_at' => now(),
                'approved_by' => $user->name,
            ]);

        // Decline Offer
        ConsumerNegotiation::factory()
            ->for(Consumer::factory()->for($consumerProfile)->create([
                ...$commonConsumer,
                'first_name' => 'Delete offer',
                'counter_offer' => false,
                'status' => ConsumerStatus::PAYMENT_DECLINED->value,
                'offer_accepted' => false,
            ]))
            ->create([
                ...$commonConsumerNegotiation,
                'offer_accepted' => false,
                'negotiation_type' => NegotiationType::PIF->value,
                'one_time_settlement' => fake()->numberBetween(1, $amount),
                'no_of_installments' => 1,
            ]);

        //Consumer Accept Counteroffer
        ConsumerNegotiation::factory()
            ->for($consumer = Consumer::factory()->for($consumerProfile)->create([
                ...$commonConsumer,
                'first_name' => 'Consumer Accept Counter offer',
                'counter_offer' => true,
                'status' => ConsumerStatus::PAYMENT_ACCEPTED->value,
                'offer_accepted' => true,
            ]))
            ->create([
                ...$commonConsumerNegotiation,
                'offer_accepted' => false,
                'negotiation_type' => NegotiationType::PIF->value,
                'one_time_settlement' => fake()->numberBetween(1, $amount),
                'no_of_installments' => 1,
                'counter_first_pay_date' => now(),
                'counter_one_time_amount' => $amount,
                'counter_no_of_installments' => 1,
                'counter_offer_accepted' => true,
                'approved_by' => $consumer->first_name,
            ]);

        // Installment negotiation type Offer
        ConsumerNegotiation::factory()
            ->for(Consumer::factory()->create([
                ...$commonConsumer,
                'first_name' => 'Installment offer',
                'counter_offer' => false,
                'status' => ConsumerStatus::PAYMENT_SETUP->value,
                'offer_accepted' => false,
            ]))
            ->create([
                ...$commonConsumerNegotiation,
                'offer_accepted' => false,
                'negotiation_type' => NegotiationType::INSTALLMENT->value,
                'negotiate_amount' => $amount,
                'no_of_installments' => 10,
                'monthly_amount' => $amount / 10,
                'last_month_amount' => $amount % 10,
            ]);

        return $user;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer\Payment;

use AllowDynamicProperties;
use App\Enums\ConsumerStatus;
use App\Enums\CustomContentType;
use App\Enums\MerchantName;
use App\Enums\NegotiationType;
use App\Livewire\Consumer\Payment;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\CustomContent;
use App\Models\Merchant;
use Illuminate\Support\Number;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[AllowDynamicProperties]
class PaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = Consumer::factory()
            ->create([
                'pif_discount_percent' => null,
                'pay_setup_discount_percent' => null,
                'ppa_amount' => null,
                'min_monthly_pay_percent' => null,
                'subclient_id' => null,
                'current_balance' => 600,
                'status' => ConsumerStatus::PAYMENT_SETUP,
            ]);

        $this->withoutVite()
            ->actingAs($this->consumer, 'consumer');

        $this->merchant = Merchant::factory()
            ->create([
                'company_id' => $this->consumer->company_id,
                'merchant_name' => fake()->randomElement([MerchantName::AUTHORIZE, MerchantName::USA_EPAY]),
                'subclient_id' => null,
            ]);

        $this->customContent = CustomContent::factory()
            ->create([
                'company_id' => $this->consumer->company_id,
                'subclient_id' => null,
                'type' => CustomContentType::TERMS_AND_CONDITIONS,
            ]);
    }

    #[Test]
    public function it_can_renders_the_view_file_when_pif_payment(): void
    {
        $this->consumer->company()->update([
            'pif_balance_discount_percent' => $pifPercentage = 10,
        ]);

        $discountAmount = $this->consumer->current_balance - ($this->consumer->current_balance * $pifPercentage / 100);

        ConsumerNegotiation::factory()
            ->create([
                'company_id' => $this->consumer->company_id,
                'consumer_id' => $this->consumer->id,
                'negotiation_type' => NegotiationType::PIF,
                'offer_accepted' => true,
                'counter_offer_accepted' => false,
                'active_negotiation' => true,
                'no_of_installments' => null,
                'one_time_settlement' => $discountAmount,
                'counter_one_time_amount' => null,
                'account_number' => $this->consumer->account_number,
                'first_pay_date' => today()->toDateString(),
            ]);

        Livewire::test(Payment::class, ['consumer' => $this->consumer])
            ->assertViewHas([
                'minimumPifDiscountedAmount' => (float) $discountAmount,
                'termsAndCondition' => $this->customContent->content,
                'installmentDetails' => [],
            ])
            ->assertSee(now()->format('M d, Y'))
            ->assertSee(Number::currency((float) $discountAmount))
            ->assertOk();

        $this->assertEquals($this->consumer->id, $this->consumer->consumerNegotiation->consumer_id);
        $this->assertEquals($this->consumer->company_id, $this->consumer->consumerNegotiation->company_id);
        $this->assertEquals(NegotiationType::PIF, $this->consumer->consumerNegotiation->negotiation_type);
        $this->assertEquals(number_format($discountAmount, 2, thousands_separator: ''), $this->consumer->consumerNegotiation->one_time_settlement);
        $this->assertEquals($this->consumer->account_number, $this->consumer->consumerNegotiation->account_number);
        $this->assertTrue($this->consumer->consumerNegotiation->offer_accepted);
        $this->assertTrue($this->consumer->consumerNegotiation->active_negotiation);
    }

    #[Test]
    public function it_can_renders_the_view_file_when_pif_payment_already_paid(): void
    {
        $this->consumer->update(['status' => ConsumerStatus::SETTLED]);

        ConsumerNegotiation::factory()
            ->for($this->consumer)
            ->create(['payment_plan_current_balance' => 0]);

        Livewire::test(Payment::class, ['consumer' => $this->consumer])
            ->assertRedirectToRoute('consumer.complete_payment', ['consumer' => $this->consumer])
            ->assertStatus(302);
    }

    #[Test]
    public function it_can_renders_the_view_file_when_installment_payment(): void
    {
        $this->consumer->company()->update([
            'ppa_balance_discount_percent' => $ppaPercentage = 10,
        ]);

        $discountAmount = $this->consumer->current_balance - ($this->consumer->current_balance * $ppaPercentage / 100);

        ConsumerNegotiation::factory()
            ->create([
                'company_id' => $this->consumer->company_id,
                'consumer_id' => $this->consumer->id,
                'negotiation_type' => NegotiationType::INSTALLMENT->value,
                'offer_accepted' => true,
                'counter_offer_accepted' => false,
                'active_negotiation' => true,
                'payment_plan_current_balance' => null,
                'no_of_installments' => $installmentCount = 8,
                'monthly_amount' => $monthlyAmount = (int) ($discountAmount / $installmentCount),
                'last_month_amount' => $lastMonthAmount = $discountAmount - ($monthlyAmount * $installmentCount),
                'negotiate_amount' => $discountAmount,
                'first_pay_date' => today()->toDateString(),
            ]);

        Livewire::test(Payment::class, ['consumer' => $this->consumer])
            ->assertSet('isPifNegotiation', false)
            ->assertViewHas('installmentDetails.0.amount', $monthlyAmount)
            ->assertViewHas('installmentDetails', fn (array $installmentDetails) => count($installmentDetails) === $installmentCount + 1)
            ->assertViewHas('installmentDetails.0.schedule_date', now()->format('M d, Y'))
            ->assertViewHas("installmentDetails.$installmentCount.amount", $lastMonthAmount)
            ->assertSee(Number::currency((float) $discountAmount))
            ->assertViewHas('termsAndCondition', $this->customContent->content)
            ->assertOk();
    }

    #[Test]
    public function it_can_renders_make_payment_require_validation(): void
    {
        ConsumerNegotiation::factory()
            ->create([
                'company_id' => $this->consumer->company_id,
                'consumer_id' => $this->consumer->id,
                'negotiation_type' => NegotiationType::PIF,
                'offer_accepted' => true,
                'counter_offer_accepted' => false,
                'active_negotiation' => true,
                'no_of_installments' => null,
                'account_number' => $this->consumer->account_number,
                'first_pay_date' => today()->toDateString(),
            ]);

        Livewire::test(Payment::class, ['consumer' => $this->consumer])
            ->set('form.address', '')
            ->set('form.state', '')
            ->set('form.city', '')
            ->set('form.zip', '')
            ->call('makePayment')
            ->assertHasErrors([
                'form.address' => 'required',
                'form.state' => 'required',
                'form.city' => 'required',
                'form.zip' => 'required',
            ])
            ->assertOk();
    }

    #[Test]
    public function it_can_renders_make_payment_non_require_max_limit_numeric_date_and_rule_in_validation(): void
    {
        ConsumerNegotiation::factory()
            ->create([
                'company_id' => $this->consumer->company_id,
                'consumer_id' => $this->consumer->id,
                'negotiation_type' => NegotiationType::PIF,
                'offer_accepted' => true,
                'counter_offer_accepted' => false,
                'active_negotiation' => true,
                'no_of_installments' => null,
                'account_number' => $this->consumer->account_number,
                'first_pay_date' => today()->toDateString(),
            ]);

        Livewire::test(Payment::class, ['consumer' => $this->consumer])
            ->set('form.address', fake()->realTextBetween(500, 700))
            ->set('form.city', fake()->realTextBetween(500, 700))
            ->set('form.state', fake()->realTextBetween(5, 10))
            ->set('form.zip', fake()->realTextBetween(5, 10))
            ->set('form.method', fake()->realTextBetween(5, 10))
            ->set('form.card_number', fake()->realTextBetween(5, 10))
            ->set('form.card_holder_name', fake()->realTextBetween(500, 700))
            ->set('form.cvv', fake()->realTextBetween(5, 10))
            ->set('form.account_type', fake()->realTextBetween(5, 10))
            ->set('form.account_number', fake()->realTextBetween(5, 10))
            ->set('form.routing_number', fake()->realTextBetween(5, 10))
            ->set('form.is_terms_accepted', false)
            ->set('form.expiry', now()->subDay()->format('m/Y/d'))
            ->call('makePayment')
            ->assertHasErrors([
                'form.address' => 'max:255',
                'form.city' => 'max:255',
                'form.state' => ['in'],
                'form.zip' => 'max_digits:5',
                'form.method' => ['in'],
                'form.card_number' => 'min_digits:14',
                'form.card_holder_name' => 'max:255',
                'form.cvv' => 'min_digits:3',
                'form.account_type' => ['in'],
                'form.account_number' => 'numeric',
                'form.routing_number' => 'numeric',
                'form.is_terms_accepted' => 'accepted',
                'form.expiry' => 'date_format:m/Y',
            ])
            ->assertOk();
    }
}

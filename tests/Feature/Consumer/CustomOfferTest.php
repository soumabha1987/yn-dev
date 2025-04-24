<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer;

use AllowDynamicProperties;
use App\Enums\ConsumerStatus;
use App\Enums\InstallmentType;
use App\Enums\NegotiationType;
use App\Enums\TransactionStatus;
use App\Livewire\Consumer\CustomOffer;
use App\Models\Consumer;
use App\Models\ScheduleTransaction;
use Illuminate\Support\Facades\Cache;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[AllowDynamicProperties]
class CustomOfferTest extends TestCase
{
    protected function setUp(): void
    {
        $this->markTestSkipped();
        parent::setUp();

        $this->consumer = Consumer::factory()->create([
            'status' => ConsumerStatus::JOINED,
            'current_balance' => 1000,
            'pif_discount_percent' => 50,
            'pay_setup_discount_percent' => 25,
            'min_monthly_pay_percent' => 10,
            'minimum_settlement_percentage' => 20,
            'minimum_payment_plan_percentage' => 10,
            'max_days_first_pay' => 12,
            'max_first_pay_days' => 18,
            'negotiation_count' => 3,
        ]);

        $this->withoutVite()
            ->actingAs($this->consumer, 'consumer');
    }

    #[Test]
    public function it_can_render_with_correct_view(): void
    {
        $this->get(route('consumer.custom-offer', $this->consumer))->assertOk();

        Livewire::test(CustomOffer::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->assertViewIs('livewire.consumer.custom-offer')
            ->assertSetStrict('minimumPpaDiscountedAmount', 750.0);
    }

    #[Test]
    public function it_can_render_with_settlement_type(): void
    {
        $this->get(route('consumer.custom-offer.type', ['settlement', $this->consumer]))
            ->assertOk()
            ->assertSeeLivewire(CustomOffer::class);
    }

    #[Test]
    public function it_can_render_with_installment_type(): void
    {
        $this->get(route('consumer.custom-offer.type', ['installment', $this->consumer]))
            ->assertOk()
            ->assertSeeLivewire(CustomOffer::class);
    }

    #[Test]
    public function it_can_throw_required_validation_error_for_when_sending_custom_offer(): void
    {
        Livewire::test(CustomOffer::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->call('createCustomOffer')
            ->assertOk()
            ->assertHasErrors([
                'form.amount' => ['required'],
                'form.first_pay_date' => ['required'],
            ])
            ->assertHasNoErrors(['form.installment_type', 'form.reason', 'form.note']);
    }

    #[Test]
    public function amount_should_be_always_greater_than_zero(): void
    {
        Livewire::test(CustomOffer::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->set('form.amount', -1.2)
            ->call('createCustomOffer')
            ->assertOk()
            ->assertHasErrors([
                'form.amount' => ['gt'],
                'form.first_pay_date' => ['required'],
            ])
            ->assertHasNoErrors(['form.installment_type', 'form.reason', 'form.note']);
    }

    #[Test]
    #[DataProvider('negotiationType')]
    public function installment_type(string $negotiationType): void
    {
        Livewire::test(CustomOffer::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->set('form.negotiation_type', $negotiationType)
            ->set('form.installment_type', '')
            ->set('form.amount', 12.55)
            ->call('createCustomOffer')
            ->assertOk()
            ->tap(function (Testable $test) use ($negotiationType): void {
                if ($negotiationType === NegotiationType::INSTALLMENT->value) {
                    $test->assertHasErrors(['form.installment_type' => ['required']])
                        ->assertHasNoErrors(['form.negotiation_type', 'form.reason', 'form.note']);

                    return;
                }

                $test->assertHasNoErrors(['form.installment_type', 'form.negotiation_type', 'form.reason', 'form.note']);
            });
    }

    #[Test]
    public function it_can_submit_pif_custom_offer(): void
    {
        $scheduleTransaction = ScheduleTransaction::factory()
            ->for($this->consumer)
            ->for($this->consumer->company)
            ->for($this->consumer->subclient)
            ->create([
                'status' => TransactionStatus::SCHEDULED,
            ]);

        Livewire::test(CustomOffer::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->set('form.negotiation_type', NegotiationType::PIF->value)
            ->set('form.installment_type', '')
            ->set('form.amount', 400)
            ->set('form.first_pay_date', today()->addDays(13)->toDateString())
            ->set('form.note', $note = 'Hey creditor! I just have this amount for this month? Can you please accept my offer')
            ->set('form.reason', $reason = 'For loan completion at the end of the month')
            ->call('createCustomOffer')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('form.isOfferAccepted', false)
            ->assertSet('form.offerSent', true);

        $negotiateAmount = round($this->consumer->current_balance - ($this->consumer->current_balance * $this->consumer->pif_discount_percent / 100));

        $this->assertModelMissing($scheduleTransaction);

        $this->assertEquals(ConsumerStatus::PAYMENT_SETUP, $this->consumer->refresh()->status);
        $this->assertTrue($this->consumer->custom_offer);
        $this->assertFalse($this->consumer->counter_offer);
        $this->assertFalse($this->consumer->offer_accepted);

        $consumerNegotiation = $this->consumer->consumerNegotiation->refresh();
        $this->assertEquals($this->consumer->company_id, $consumerNegotiation->company_id);
        $this->assertEquals(today()->addDays(13)->toDateString(), $consumerNegotiation->first_pay_date->toDateString());
        $this->assertEquals($reason, $consumerNegotiation->reason);
        $this->assertEquals($note, $consumerNegotiation->note);
        $this->assertEquals(NegotiationType::PIF, $consumerNegotiation->negotiation_type);
        $this->assertNull($consumerNegotiation->installment_type);
        $this->assertEquals($negotiateAmount, $consumerNegotiation->one_time_settlement);
        $this->assertNull($consumerNegotiation->no_of_installments);
        $this->assertTrue($consumerNegotiation->active_negotiation);
        $this->assertEquals('500.50', $consumerNegotiation->monthly_amount);
        $this->assertNull($consumerNegotiation->negotiate_amount);
        $this->assertNull($consumerNegotiation->last_month_amount);

        $this->assertEquals(Cache::get('new_offer_count_' . $this->consumer->company_id), 1);
    }

    #[Test]
    public function it_can_send_installment_custom_offer(): void
    {
        Livewire::test(CustomOffer::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->set('form.negotiation_type', NegotiationType::INSTALLMENT->value)
            ->set('form.installment_type', InstallmentType::BIMONTHLY->value)
            ->set('form.amount', 50)
            ->set('form.first_pay_date', today()->addDays(15)->toDateString())
            ->call('createCustomOffer')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('form.offerSent', true);

        $this->assertEquals(ConsumerStatus::PAYMENT_SETUP, $this->consumer->refresh()->status);
        $this->assertTrue($this->consumer->custom_offer);
        $this->assertFalse($this->consumer->counter_offer);
        $this->assertFalse($this->consumer->offer_accepted);

        $consumerNegotiation = $this->consumer->consumerNegotiation->refresh();
        $this->assertFalse($consumerNegotiation->offer_accepted);
        $this->assertEquals($this->consumer->company_id, $consumerNegotiation->company_id);
        $this->assertEquals(today()->addDays(15)->toDateString(), $consumerNegotiation->first_pay_date->toDateString());
        $this->assertNull($consumerNegotiation->reason);
        $this->assertNull($consumerNegotiation->note);
        $this->assertEquals(NegotiationType::INSTALLMENT, $consumerNegotiation->negotiation_type);
        $this->assertEquals(InstallmentType::BIMONTHLY, $consumerNegotiation->installment_type);
        $this->assertNull($consumerNegotiation->one_time_settlement);
        $this->assertEquals('15', $consumerNegotiation->no_of_installments);
        $this->assertTrue($consumerNegotiation->active_negotiation);
        $this->assertEquals('50.00', $consumerNegotiation->monthly_amount);
        $this->assertEquals('750.00', $consumerNegotiation->negotiate_amount);
        $this->assertEquals('0.00', $consumerNegotiation->last_month_amount);

        $this->assertEquals(1, Cache::get('new_offer_count_' . $this->consumer->company_id));
    }

    #[Test]
    public function it_can_submit_installment_custom_offer_but_amount_is_minimum_5_percent_of_discounted_amount(): void
    {
        Livewire::test(CustomOffer::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->set('form.negotiation_type', NegotiationType::INSTALLMENT->value)
            ->set('form.installment_type', InstallmentType::WEEKLY->value)
            ->set('form.amount', 0.125)
            ->set('form.first_pay_date', today()->addDays(13)->toDateString())
            ->call('createCustomOffer')
            ->assertOk()
            ->assertHasErrors(['form.amount'])
            ->assertHasNoErrors(['form.first_pay_date']);
    }

    #[Test]
    public function it_can_send_custom_offer_for_pif_but_offer_was_automatically_accepted(): void
    {
        $negotiateAmount = round($this->consumer->current_balance - ($this->consumer->current_balance * $this->consumer->pif_discount_percent / 100));

        Livewire::test(CustomOffer::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->set('form.first_pay_date', today()->addDay()->toDateString())
            ->set('form.amount', $negotiateAmount)
            ->set('form.negotiation_type', NegotiationType::PIF->value)
            ->call('createCustomOffer')
            ->assertHasNoErrors()
            ->assertSet('form.isOfferAccepted', true);

        $this->assertEquals(ConsumerStatus::PAYMENT_ACCEPTED, $this->consumer->refresh()->status);
        $this->assertFalse($this->consumer->custom_offer);
        $this->assertFalse($this->consumer->counter_offer);
        $this->assertTrue($this->consumer->offer_accepted);

        $consumerNegotiation = $this->consumer->consumerNegotiation->refresh();
        $this->assertTrue($consumerNegotiation->offer_accepted);
        $this->assertEquals($this->consumer->company_id, $consumerNegotiation->company_id);
        $this->assertEquals(today()->addDay()->toDateString(), $consumerNegotiation->first_pay_date->toDateString());
        $this->assertNull($consumerNegotiation->reason);
        $this->assertNull($consumerNegotiation->note);
        $this->assertEquals(NegotiationType::PIF, $consumerNegotiation->negotiation_type);
        $this->assertNull($consumerNegotiation->installment_type);
        $this->assertEquals($negotiateAmount, $consumerNegotiation->one_time_settlement);
        $this->assertNull($consumerNegotiation->no_of_installments);
        $this->assertTrue($consumerNegotiation->active_negotiation);
        $this->assertEquals($negotiateAmount, $consumerNegotiation->monthly_amount);
        $this->assertNull($consumerNegotiation->negotiate_amount);
        $this->assertNull($consumerNegotiation->last_month_amount);

        $this->assertEquals(0, Cache::get('new_offer_count_' . $this->consumer->company_id));
    }

    #[Test]
    public function it_can_send_custom_offer_for_installment_but_offer_was_automatically_accepted(): void
    {
        $monthlyAmount = 800 * $this->consumer->min_monthly_pay_percent / 100;

        Livewire::test(CustomOffer::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->set('form.first_pay_date', today()->addDay()->toDateString())
            ->set('form.amount', $monthlyAmount)
            ->set('form.negotiation_type', NegotiationType::INSTALLMENT->value)
            ->set('form.installment_type', InstallmentType::MONTHLY->value)
            ->call('createCustomOffer')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('form.isOfferAccepted', true);

        $this->assertEquals(ConsumerStatus::PAYMENT_ACCEPTED, $this->consumer->refresh()->status);
        $this->assertFalse($this->consumer->custom_offer);
        $this->assertFalse($this->consumer->counter_offer);
        $this->assertTrue($this->consumer->offer_accepted);

        $consumerNegotiation = $this->consumer->consumerNegotiation->refresh();
        $this->assertTrue($consumerNegotiation->offer_accepted);
        $this->assertEquals($this->consumer->company_id, $consumerNegotiation->company_id);
        $this->assertEquals(today()->addDay()->toDateString(), $consumerNegotiation->first_pay_date->toDateString());
        $this->assertNull($consumerNegotiation->reason);
        $this->assertNull($consumerNegotiation->note);
        $this->assertEquals(NegotiationType::INSTALLMENT, $consumerNegotiation->negotiation_type);
        $this->assertEquals(InstallmentType::MONTHLY, $consumerNegotiation->installment_type);
        $this->assertNull($consumerNegotiation->one_time_settlement);
        $this->assertEquals('9', $consumerNegotiation->no_of_installments);
        $this->assertTrue($consumerNegotiation->active_negotiation);
        $this->assertEquals(number_format($monthlyAmount, 2), $consumerNegotiation->monthly_amount);
        $this->assertEquals('750.00', $consumerNegotiation->negotiate_amount);
        $this->assertEquals('30.00', $consumerNegotiation->last_month_amount);

        $this->assertEquals(0, Cache::get('new_offer_count_' . $this->consumer->company_id));
    }

    #[Test]
    public function it_can_send_installment_custom_offer_last_month_amount_in_decimal(): void
    {
        Livewire::test(CustomOffer::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->set('form.negotiation_type', NegotiationType::INSTALLMENT->value)
            ->set('form.installment_type', InstallmentType::BIMONTHLY->value)
            ->set('form.amount', 49.95)
            ->set('form.first_pay_date', today()->addDays(15)->toDateString())
            ->call('createCustomOffer')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('form.offerSent', true);

        $this->assertEquals(ConsumerStatus::PAYMENT_SETUP, $this->consumer->refresh()->status);
        $this->assertTrue($this->consumer->custom_offer);
        $this->assertFalse($this->consumer->counter_offer);
        $this->assertFalse($this->consumer->offer_accepted);

        $consumerNegotiation = $this->consumer->consumerNegotiation->refresh();
        $this->assertFalse($consumerNegotiation->offer_accepted);
        $this->assertEquals($this->consumer->company_id, $consumerNegotiation->company_id);
        $this->assertEquals(today()->addDays(15)->toDateString(), $consumerNegotiation->first_pay_date->toDateString());
        $this->assertNull($consumerNegotiation->reason);
        $this->assertNull($consumerNegotiation->note);
        $this->assertEquals(NegotiationType::INSTALLMENT, $consumerNegotiation->negotiation_type);
        $this->assertEquals(InstallmentType::BIMONTHLY, $consumerNegotiation->installment_type);
        $this->assertNull($consumerNegotiation->one_time_settlement);
        $this->assertEquals('15', $consumerNegotiation->no_of_installments);
        $this->assertTrue($consumerNegotiation->active_negotiation);
        $this->assertEquals('49.95', $consumerNegotiation->monthly_amount);
        $this->assertEquals('750.00', $consumerNegotiation->negotiate_amount);
        $this->assertEquals('0.75', $consumerNegotiation->last_month_amount);

        $this->assertEquals(1, Cache::get('new_offer_count_' . $this->consumer->company_id));
    }

    #[Test]
    public function it_can_allow_to_propose_different_date_for_one_time_settlement(): void
    {
        $this->consumer->update([
            'current_balance' => 2000,
            'pif_discount_percent' => 15,
        ]);

        Livewire::test(CustomOffer::class, ['consumer' => $this->consumer, 'type' => 'settlement'])
            ->assertOk()
            ->assertSet('form.negotiation_type', NegotiationType::PIF->value)
            ->assertSet('form.amount', 1700);
    }

    #[Test]
    public function it_can_allow_to_propose_different_date_for_installment(): void
    {
        $this->consumer->update([
            'current_balance' => 5000,
            'min_monthly_pay_percent' => 10,
            'pay_setup_discount_percent' => 5,
        ]);

        Livewire::test(CustomOffer::class, ['consumer' => $this->consumer, 'type' => 'installment'])
            ->assertOk()
            ->assertSet('form.negotiation_type', NegotiationType::INSTALLMENT->value)
            ->assertSet('form.installment_type', InstallmentType::MONTHLY->value)
            ->assertSet('form.amount', 5000 * 0.1 - 500 * 0.05); // 5000 * 10% - 5%
    }

    public static function negotiationType(): array
    {
        return [
            '`is not required when negotiation type is pif`' => [NegotiationType::PIF->value],
            '`is required when negotiation type is installment`' => [NegotiationType::INSTALLMENT->value],
        ];
    }
}

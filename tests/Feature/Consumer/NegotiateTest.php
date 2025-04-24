<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer;

use App\Enums\ConsumerStatus;
use App\Enums\CustomContentType;
use App\Enums\InstallmentType;
use App\Enums\NegotiationType;
use App\Livewire\Consumer\Negotiate;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\CustomContent;
use App\Models\Subclient;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NegotiateTest extends TestCase
{
    protected Company $company;

    protected Consumer $consumer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()
            ->has(CustomContent::factory()->state(['content' => 'Hey there!', 'type' => CustomContentType::ABOUT_US]))
            ->create([
                'company_name' => 'Tata Private Limited',
                'owner_phone' => '+1-808-820-9158',
                'owner_email' => 'ttreutel@mcglynn.com',
                'pif_balance_discount_percent' => 10.78,
            ]);

        $this->consumer = Consumer::factory()
            ->for($this->company)
            ->create([
                'subclient_id' => null,
                'status' => ConsumerStatus::JOINED,
            ]);

        $this->withoutVite()
            ->actingAs($this->consumer, 'consumer');
    }

    #[Test]
    public function it_can_redirect_if_account_status_is_not_joined(): void
    {
        $this->consumer->update(['status' => ConsumerStatus::RENEGOTIATE]);

        $this->get(route('consumer.negotiate', $this->consumer->id))
            ->assertRedirectToRoute('consumer.account')
            ->assertStatus(302);
    }

    #[Test]
    public function it_can_render_livewire_component(): void
    {
        $this->get(route('consumer.negotiate', $this->consumer->id))
            ->assertSeeLivewire(Negotiate::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        Livewire::test(Negotiate::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->assertViewIs('livewire.consumer.negotiate')
            ->assertSee($this->consumer->account_number)
            ->assertSee(Number::currency($this->consumer->current_balance))
            ->assertViewHas('creditorDetails.company_name', 'Tata Private Limited')
            ->assertViewHas('creditorDetails.contact_person_name', 'Tata Private Limited')
            ->assertViewHas('creditorDetails.custom_content', 'Hey there!');
    }

    #[Test]
    public function it_can_render_when_pif_discount_percentage_is_available_and_pif_discount_amount_is_nullable(): void
    {
        $this->consumer->update([
            'current_balance' => 30.5,
            'pif_discount_percent' => $payOffDiscountPercentage = 12,
        ]);

        Log::shouldReceive('channel')->with('daily')->andReturnSelf();
        Log::shouldReceive('info')->with("Consumer: {$this->consumer->id} on pif_discount_percent")->andReturnNull();

        Livewire::test(Negotiate::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->assertViewIs('livewire.consumer.negotiate')
            ->assertViewHas('payOffDiscount', fn (float $payOffDiscount): bool => $payOffDiscount === 26.84)
            ->assertSee(__('Save :amount', ['amount' => Number::currency(3.66)]))
            ->assertSee(__(':off  Off', ['off' => Number::percentage($payOffDiscountPercentage)]));
    }

    #[Test]
    public function it_can_render_when_pif_discount_percentage_is_zero(): void
    {
        $this->consumer->update([
            'current_balance' => 35,
            'pif_discount_percent' => 0,
        ]);

        Log::shouldReceive('channel')->with('daily')->andReturnSelf();
        Log::shouldReceive('info')->with("Consumer: {$this->consumer->id} on pif_discount_percent")->andReturnNull();

        Livewire::test(Negotiate::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->assertViewIs('livewire.consumer.negotiate')
            ->assertViewHas('payOffDiscount', fn (float $payOffDiscount): bool => $payOffDiscount === 35.0)
            ->assertDontSee(__('Save :amount', ['amount' => Number::currency(0)]));
    }

    #[Test]
    public function it_can_render_with_subclient_terms(): void
    {
        $subclient = Subclient::factory()
            ->for($this->company)
            ->create([
                'pif_balance_discount_percent' => 10,
                'ppa_balance_discount_percent' => 6,
                'min_monthly_pay_percent' => 12,
            ]);

        $this->consumer->update([
            'current_balance' => 100.50,
            'pif_discount_percent' => null,
            'pay_setup_discount_percent' => null,
            'min_monthly_pay_percent' => null,
            'subclient_id' => $subclient->id,
        ]);

        Log::shouldReceive('channel')->with('daily')->andReturnSelf();
        Log::shouldReceive('info')->with("Consumer: {$this->consumer->id} on subclient discount percentage")->andReturnNull();

        Livewire::test(Negotiate::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->assertViewIs('livewire.consumer.negotiate')
            ->assertViewHas('payOffDiscount', fn (float $payOffDiscount): bool => $payOffDiscount === 90.45)
            ->assertViewHas('installmentDetails', [
                'message' => '7 monthly payments of $11.34 and last payment of $15.09',
                'installments' => 7.0,
                'monthly_amount' => 11.34,
                'last_month_amount' => 15.09,
                'discounted_amount' => 6.03,
                'discount_percentage' => '6%',
            ]);
    }

    #[Test]
    public function it_can_render_with_company_terms_of_pif_discount_percentage(): void
    {
        $this->consumer->update([
            'current_balance' => 50,
            'pif_discount_percent' => null,
        ]);

        Log::shouldReceive('channel')->with('daily')->andReturnSelf();
        Log::shouldReceive('info')->with("Consumer: {$this->consumer->id} on company discount percentage")->andReturnNull();

        Livewire::test(Negotiate::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->assertViewIs('livewire.consumer.negotiate')
            ->assertViewHas('payOffDiscount', fn (float $payOffDiscount): bool => $payOffDiscount === 44.61);
    }

    #[Test]
    public function it_can_send_the_settlement_offer_with_custom_date(): void
    {
        $this->consumer->update([
            'current_balance' => 35,
            'pif_discount_percent' => 5,
            'max_days_first_pay' => 20,
        ]);

        Livewire::test(Negotiate::class, ['consumer' => $this->consumer])
            ->assertViewIs('livewire.consumer.negotiate')
            ->assertViewHas('installmentDetails.installments', null)
            ->assertViewHas('installmentDetails.monthly_amount', null)
            ->assertViewHas('installmentDetails.last_month_amount', null)
            ->assertViewHas('reasons', fn (Collection $reasons) => $reasons->isEmpty())
            ->set('first_pay_date', today()->addDays(5)->toDateString())
            ->assertSeeHtml('wire:submit="createSettlementOffer"')
            ->call('createSettlementOffer')
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertDatabaseHas(ConsumerNegotiation::class, [
            'first_pay_date' => today()->addDays(5)->toDateString(),
            'active_negotiation' => false,
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'negotiation_type' => NegotiationType::PIF->value,
            'one_time_settlement' => 33.25,
            'installment_type' => null,
            'offer_accepted' => false,
            'negotiate_amount' => null,
            'no_of_installments' => null,
            'monthly_amount' => null,
            'last_month_amount' => null,
        ]);

        $this->assertEquals(ConsumerStatus::JOINED, $this->consumer->refresh()->status);
        $this->assertFalse($this->consumer->offer_accepted);
        $this->assertFalse($this->consumer->custom_offer);
        $this->assertFalse($this->consumer->counter_offer);
    }

    #[Test]
    #[DataProvider('installmentDetails')]
    public function it_can_render_livewire_component_with_offers_details_when(Closure $installmentDetails): void
    {
        $installmentDetails = $installmentDetails($this->consumer);

        Livewire::test(Negotiate::class, ['consumer' => $this->consumer])
            ->assertViewIs('livewire.consumer.negotiate')
            ->assertViewHas('installmentDetails.installments', $installmentDetails['installments'])
            ->assertViewHas('installmentDetails.monthly_amount', $installmentDetails['monthly_amount'])
            ->assertViewHas('installmentDetails.last_month_amount', $installmentDetails['last_month_amount'])
            ->assertViewHas('reasons', fn (Collection $reasons) => $reasons->isEmpty())
            ->set('first_pay_date', today()->addDays(5)->toDateString())
            ->assertSeeHtml('wire:submit="createInstallmentOffer"')
            ->call('createInstallmentOffer')
            ->assertRedirectToRoute('consumer.payment', $this->consumer)
            ->assertHasNoErrors();

        $this->assertDatabaseHas(ConsumerNegotiation::class, [
            'first_pay_date' => today()->addDays(5)->toDateTimeString(),
            'active_negotiation' => false,
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'negotiation_type' => NegotiationType::INSTALLMENT->value,
            'installment_type' => InstallmentType::MONTHLY->value,
            'offer_accepted' => false,
            'negotiate_amount' => number_format($installmentDetails['negotiate_amount'], 2, thousands_separator: ''),
            'no_of_installments' => $installmentDetails['installments'],
            'monthly_amount' => number_format($installmentDetails['monthly_amount'], 2, thousands_separator: ''),
            'last_month_amount' => $installmentDetails['last_month_amount'] > 0 ? number_format((float) $installmentDetails['last_month_amount'], 2, thousands_separator: '') : null,
        ]);

        $this->assertEquals(ConsumerStatus::JOINED, $this->consumer->refresh()->status);
        $this->assertFalse($this->consumer->offer_accepted);
        $this->assertFalse($this->consumer->custom_offer);
        $this->assertFalse($this->consumer->counter_offer);
    }

    public static function installmentDetails(): array
    {
        return [
            'first case is using ppa amount and monthly pay amount' => [
                function (Consumer $consumer): array {
                    $consumer->update([
                        'current_balance' => 100,
                        'pay_setup_discount_percent' => 20,
                        'min_monthly_pay_percent' => 10,
                        'max_days_first_pay' => 20,
                    ]);

                    return [
                        'installments' => 10,
                        'monthly_amount' => 8,
                        'last_month_amount' => 0,
                        'negotiate_amount' => 80,
                    ];
                },
            ],
            'second case is using pay setup percentage and minimum monthly pay amount' => [
                function (Consumer $consumer): array {
                    $consumer->update([
                        'current_balance' => 100,
                        'pay_setup_discount_percent' => 15,
                        'min_monthly_pay_percent' => 15,
                        'max_days_first_pay' => 20,
                    ]);

                    return [
                        'installments' => 5,
                        'monthly_amount' => 12.75,
                        'last_month_amount' => 21.25,
                        'negotiate_amount' => 85,
                    ];
                },
            ],
            'third case is using ppa amount is available and pay setup percentage is also available' => [
                function (Consumer $consumer): array {
                    $consumer->update([
                        'current_balance' => 100,
                        'pay_setup_discount_percent' => 20,
                        'min_monthly_pay_percent' => 17,
                        'max_days_first_pay' => 20,
                    ]);

                    return [
                        'installments' => 5,
                        'monthly_amount' => 13.6,
                        'last_month_amount' => 12,
                        'negotiate_amount' => 80,
                    ];
                },
            ],
            'fourth case is using pay setup percentage and minimum monthly pay amount' => [
                function (Consumer $consumer): array {
                    $consumer->update([
                        'current_balance' => 100,
                        'pay_setup_discount_percent' => 15,
                        'min_monthly_pay_percent' => 17,
                        'max_days_first_pay' => 20,
                    ]);

                    return [
                        'installments' => 5,
                        'monthly_amount' => 14.45,
                        'last_month_amount' => 12.75,
                        'negotiate_amount' => 85,
                    ];
                },
            ],
            'fifth case is using pay setup discount percentage and minimum monthly pay percentage' => [
                function (Consumer $consumer): array {
                    $consumer->update([
                        'current_balance' => 100,
                        'pay_setup_discount_percent' => 25,
                        'min_monthly_pay_percent' => 20,
                        'max_days_first_pay' => 20,
                    ]);

                    return [
                        'installments' => 5,
                        'monthly_amount' => 15,
                        'last_month_amount' => 0,
                        'negotiate_amount' => 75,
                    ];
                },
            ],
            'six case is using pay setup discount percentage and minimum monthly pay percentage' => [
                function (Consumer $consumer): array {
                    $consumer->update([
                        'current_balance' => 100,
                        'pay_setup_discount_percent' => 0,
                        'min_monthly_pay_percent' => 10,
                        'max_days_first_pay' => 20,
                    ]);

                    return [
                        'installments' => 10,
                        'monthly_amount' => 10,
                        'last_month_amount' => 0,
                        'negotiate_amount' => 100,
                    ];
                },
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer;

use App\Enums\ConsumerStatus;
use App\Enums\InstallmentType;
use App\Enums\MerchantName;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Livewire\Consumer\SchedulePlan;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\PaymentProfile;
use App\Models\ScheduleTransaction;
use App\Models\Transaction;
use App\Services\Consumer\AuthorizePaymentService;
use App\Services\Consumer\StripePaymentService;
use App\Services\Consumer\TilledPaymentService;
use App\Services\Consumer\USAEpayPaymentService;
use Closure;
use Filament\Notifications\Notification;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SchedulePlanTest extends TestCase
{
    protected Consumer $consumer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = Consumer::factory()
            ->has(PaymentProfile::factory())
            ->has(ConsumerNegotiation::factory())
            ->create([
                'status' => ConsumerStatus::PAYMENT_ACCEPTED,
                'offer_accepted' => true,
            ]);

        $this->withoutVite()
            ->actingAs($this->consumer, 'consumer');
    }

    #[Test]
    public function it_can_not_allow_to_visit_route_if_logged_in_consumer_and_route_consumer_is_different(): void
    {
        $consumer = Consumer::factory()->create([
            'company_id' => $this->consumer->company_id,
            'subclient_id' => $this->consumer->subclient_id,
            'status' => ConsumerStatus::PAYMENT_ACCEPTED,
            'offer_accepted' => true,
        ]);

        $this->get(route('consumer.schedule_plan', ['consumer' => $consumer]))
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    #[Test]
    #[DataProvider('consumerCase')]
    public function it_will_redirect_to_account_page_when_we_dont_have_consumer_negotiation(Closure $consumerCase): void
    {
        $consumerCase($this->consumer);

        $this->get(route('consumer.schedule_plan', ['consumer' => $this->consumer]))
            ->tap(function (): void {
                if ($this->consumer->refresh()->status !== ConsumerStatus::DEACTIVATED && $this->consumer->offer_accepted) {
                    Notification::assertNotified($this->consumer->paymentProfile === null ? __('Please finish your payment setup first.') : __('There is no active plan.'));
                }
            })
            ->assertRedirectToRoute('consumer.account')
            ->assertStatus(Response::HTTP_FOUND);
    }

    #[Test]
    public function it_can_render_the_livewire_component(): void
    {
        $this->get(route('consumer.schedule_plan', ['consumer' => $this->consumer]))
            ->assertSeeLivewire(SchedulePlan::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        Livewire::test(SchedulePlan::class, ['consumer' => $this->consumer])
            ->assertViewIs('livewire.consumer.schedule-plan')
            ->assertViewHas('scheduleTransactions', fn (Collection $scheduleTransactions) => $scheduleTransactions->isEmpty())
            ->assertViewHas('transactions', fn (Collection $scheduleTransactions) => $scheduleTransactions->isEmpty())
            ->assertOk();
    }

    #[Test]
    #[DataProvider('transactionStatus')]
    public function it_can_render_with_some_data(TransactionStatus $transactionStatus): void
    {
        $scheduleTransaction = ScheduleTransaction::factory()
            ->for($this->consumer->subclient)
            ->for($this->consumer->company)
            ->for($this->consumer)
            ->create(['status' => $transactionStatus]);

        $transaction = Transaction::factory()
            ->for($this->consumer->subclient)
            ->for($this->consumer->company)
            ->for($this->consumer->paymentProfile)
            ->for($this->consumer)
            ->create(['transaction_type' => TransactionType::PARTIAL_PIF]);

        Livewire::test(SchedulePlan::class, ['consumer' => $this->consumer])
            ->assertViewHas('scheduleTransactions', fn (Collection $scheduleTransactions) => $scheduleTransaction->is($scheduleTransactions->first()))
            ->assertViewHas('transactions', fn (Collection $transactions) => $transaction->is($transactions->first()))
            ->assertOk();
    }

    #[Test]
    #[DataProvider('merchant')]
    public function it_will_pay_installment_instead_of_monthly(MerchantName $merchantName, $merchantService): void
    {
        $scheduleTransaction = ScheduleTransaction::factory()
            ->for($this->consumer->subclient)
            ->for($this->consumer->company)
            ->for($this->consumer)
            ->create(['status' => TransactionStatus::SCHEDULED]);

        $this->consumer->subclient()->update(['has_merchant' => true]);

        $this->consumer->paymentProfile->merchant()->update([
            'merchant_name' => $merchantName,
            'company_id' => $this->consumer->company_id,
            'subclient_id' => $this->consumer->subclient_id,
            'merchant_type' => $this->consumer->paymentProfile->method,
        ]);

        $this->partialMock($merchantService)
            ->shouldReceive('payInstallment')
            ->once()
            ->andReturn(true);

        Livewire::test(SchedulePlan::class, ['consumer' => $this->consumer])
            ->call('payInstallmentAmount', $scheduleTransaction)
            ->assertDispatched('close-confirmation-box')
            ->assertOk();
    }

    #[Test]
    #[DataProvider('merchant')]
    public function it_will_pay_remaining_amount(MerchantName $merchantName, $merchantService): void
    {
        ScheduleTransaction::factory(2)
            ->for($this->consumer->subclient)
            ->for($this->consumer->company)
            ->for($this->consumer)
            ->create(['status' => TransactionStatus::SCHEDULED]);

        $this->consumer->subclient()->update(['has_merchant' => true]);

        $this->consumer->paymentProfile->merchant()->update([
            'merchant_name' => $merchantName,
            'company_id' => $this->consumer->company_id,
            'subclient_id' => $this->consumer->subclient_id,
            'merchant_type' => $this->consumer->paymentProfile->method,
        ]);

        $this->partialMock($merchantService)
            ->shouldReceive('payRemainingAmount')
            ->once()
            ->andReturn(true);

        Livewire::test(SchedulePlan::class, ['consumer' => $this->consumer])
            ->call('payRemainingAmount')
            ->assertDispatched('close-confirmation-box')
            ->assertOk();
    }

    #[Test]
    #[DataProvider('transactionStatus')]
    public function it_can_reschedule_payment(TransactionStatus $transactionStatus): void
    {
        $scheduleTransaction = ScheduleTransaction::factory()
            ->for($this->consumer->subclient)
            ->for($this->consumer->company)
            ->for($this->consumer)
            ->create(['status' => $transactionStatus]);

        Livewire::test(SchedulePlan::class, ['consumer' => $this->consumer])
            ->call('reschedule', $scheduleTransaction)
            ->tap(function () use ($transactionStatus, $scheduleTransaction): void {
                if ($transactionStatus === TransactionStatus::FAILED) {
                    $this->assertEquals(TransactionStatus::RESCHEDULED, $scheduleTransaction->refresh()->status);
                    $this->assertDatabaseCount(ScheduleTransaction::class, 2);
                }
            })
            ->assertOk();
    }

    #[Test]
    #[DataProvider('dates')]
    public function it_can_skip_payment(string $scheduleDate): void
    {
        $this->consumer->consumerNegotiation()->update(['installment_type' => InstallmentType::WEEKLY]);

        $scheduleTransaction = ScheduleTransaction::factory()
            ->for($this->consumer->subclient)
            ->for($this->consumer->company)
            ->for($this->consumer)
            ->create([
                'schedule_date' => $scheduleDate,
                'status' => TransactionStatus::SCHEDULED,
            ]);

        Livewire::test(SchedulePlan::class, ['consumer' => $this->consumer])
            ->call('skipPayment', $scheduleTransaction)
            ->tap(function () use ($scheduleDate, $scheduleTransaction): void {
                if ($scheduleDate === today()->addDays(2)->toDateString()) {
                    $this->assertEquals(1, $this->consumer->refresh()->skip_schedules);
                    $this->assertEquals(TransactionStatus::SCHEDULED, $scheduleTransaction->refresh()->status);
                    $this->assertEquals($scheduleDate, $scheduleTransaction->previous_schedule_date->toDateString());
                    $this->assertEquals(Carbon::parse($scheduleDate)->addWeek()->toDateString(), $scheduleTransaction->schedule_date->toDateString());
                }
            })
            ->assertDispatched('close-confirmation-box')
            ->assertOk();
    }

    #[Test]
    public function it_can_throw_required_validation_when_update_schedule_date(): void
    {
        $scheduleTransactions = ScheduleTransaction::factory(2)
            ->sequence(
                ['schedule_date' => today()->addDay()->toDateString()],
                ['schedule_date' => today()->addDays(3)->toDateString()]
            )
            ->for($this->consumer->subclient)
            ->for($this->consumer->company)
            ->for($this->consumer)
            ->create();

        Livewire::test(SchedulePlan::class, ['consumer' => $this->consumer])
            ->set('new_date', '')
            ->call('updateScheduleDate', $scheduleTransactions->first())
            ->assertHasErrors(['new_date' => ['required']])
            ->assertOk();
    }

    #[Test]
    public function it_can_throw_date_format_validation_when_update_schedule_date(): void
    {
        $scheduleTransactions = ScheduleTransaction::factory(2)
            ->sequence(
                ['schedule_date' => today()->addDay()->toDateString()],
                ['schedule_date' => today()->addDays(3)->toDateString()]
            )
            ->for($this->consumer->subclient)
            ->for($this->consumer->company)
            ->for($this->consumer)
            ->create();

        Livewire::test(SchedulePlan::class, ['consumer' => $this->consumer])
            ->set('new_date', fake()->name())
            ->call('updateScheduleDate', $scheduleTransactions->first())
            ->assertHasErrors(['new_date' => ['date', 'date_format']])
            ->assertOk();
    }

    #[Test]
    public function it_can_throw_after_or_equal_validation_when_update_schedule_date(): void
    {
        $scheduleTransactions = ScheduleTransaction::factory(2)
            ->sequence(
                ['schedule_date' => today()->addDay()->toDateString()],
                ['schedule_date' => today()->addDays(3)->toDateString()]
            )
            ->for($this->consumer->subclient)
            ->for($this->consumer->company)
            ->for($this->consumer)
            ->create();

        Livewire::test(SchedulePlan::class, ['consumer' => $this->consumer])
            ->set('new_date', today()->subDay()->toDateString())
            ->call('updateScheduleDate', $scheduleTransactions->first())
            ->assertHasErrors(['new_date' => 'after_or_equal'])
            ->assertOk();
    }

    #[Test]
    public function it_date_before_the_next_scheduled_when_update_schedule_date(): void
    {
        $scheduleTransactions = ScheduleTransaction::factory(2)
            ->sequence(
                ['schedule_date' => today()->addDay()->toDateString()],
                ['schedule_date' => today()->addDays(3)->toDateString()]
            )
            ->for($this->consumer->subclient)
            ->for($this->consumer->company)
            ->for($this->consumer)
            ->create();

        Livewire::test(SchedulePlan::class, ['consumer' => $this->consumer])
            ->set('new_date', today()->addDays(5)->toDateString())
            ->call('updateScheduleDate', $scheduleTransactions->first())
            ->assertHasErrors(['new_date' => 'before'])
            ->assertOk();
    }

    #[Test]
    public function it_update_schedule_transaction(): void
    {
        $scheduleTransactions = ScheduleTransaction::factory(2)
            ->sequence(
                ['schedule_date' => today()->addDay()->toDateString()],
                ['schedule_date' => today()->addDays(3)->toDateString()]
            )
            ->for($this->consumer->subclient)
            ->for($this->consumer->company)
            ->for($this->consumer)
            ->create();

        Livewire::test(SchedulePlan::class, ['consumer' => $this->consumer])
            ->set('new_date', $newScheduleDate = today()->addDays(2)->toDateString())
            ->call('updateScheduleDate', $scheduleTransactions->first())
            ->assertHasNoErrors()
            ->assertDispatched('close-dialog')
            ->assertSet('new_date', now()->addDay()->toDateString())
            ->assertOk();

        $this->assertNotEquals(today()->addDay()->toDateString(), $scheduleTransactions->first()->refresh()->previous_schedule_date->toDateString());
        $this->assertNotEquals($newScheduleDate, $scheduleTransactions->first()->schedule_date->toDateString());
        $this->assertNotEquals(TransactionStatus::CONSUMER_CHANGE_DATE->value, $scheduleTransactions->first()->status);

        $newScheduleTransaction = $scheduleTransactions->first();

        $this->assertDatabaseHas(ScheduleTransaction::class, [
            'consumer_id' => $newScheduleTransaction->consumer_id,
            'schedule_date' => $newScheduleDate,
            'previous_schedule_date' => $newScheduleTransaction->schedule_date,
            'status' => TransactionStatus::SCHEDULED,
        ]);
    }

    public static function consumerCase(): array
    {
        return [
            '`when deactivated consumer`' => [fn (Consumer $consumer) => $consumer->update(['status' => ConsumerStatus::DEACTIVATED])],
            '`when consumer negotiation is not available`' => [fn (Consumer $consumer) => $consumer->consumerNegotiation()->delete()],
            '`when consumer have no payment profile`' => [fn (Consumer $consumer) => $consumer->paymentProfile()->delete()],
            '`when consumer have not plan start`' => [fn (Consumer $consumer) => $consumer->update(['offer_accepted' => false])],
        ];
    }

    public static function merchant(): array
    {
        return [
            'authorize' => [MerchantName::AUTHORIZE, AuthorizePaymentService::class],
            'usaepay' => [MerchantName::USA_EPAY, USAEpayPaymentService::class],
            'stripe' => [MerchantName::STRIPE, StripePaymentService::class],
            'younegotiate' => [MerchantName::YOU_NEGOTIATE, TilledPaymentService::class],
        ];
    }

    public static function transactionStatus(): array
    {
        return [
            'failed' => [TransactionStatus::FAILED],
            'scheduled' => [TransactionStatus::SCHEDULED],
        ];
    }

    public static function dates(): array
    {
        return [
            'today' => [today()->toDateString()],
            'after two days' => [today()->addDays(2)->toDateString()],
        ];
    }
}

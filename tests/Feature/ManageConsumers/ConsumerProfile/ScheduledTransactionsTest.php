<?php

declare(strict_types=1);

namespace Tests\Feature\ManageConsumers\ConsumerProfile;

use App\Enums\ConsumerStatus;
use App\Enums\InstallmentType;
use App\Enums\Role as EnumRole;
use App\Enums\TransactionStatus;
use App\Livewire\Creditor\ManageConsumers\ConsumerProfile\ScheduledTransactions;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\MembershipPaymentProfile;
use App\Models\ScheduleTransaction;
use App\Models\YnTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class ScheduledTransactionsTest extends AuthTestCase
{
    public Consumer $consumer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = Consumer::factory()
            ->create([
                'status' => ConsumerStatus::PAYMENT_ACCEPTED,
                'current_balance' => '123.34',
            ]);

        $this->companyMembership->update(['company_id' => $this->consumer->company_id]);
    }

    #[Test]
    public function it_can_render_scheduled_transactions_view(): void
    {
        Livewire::test(ScheduledTransactions::class, ['consumer' => $this->consumer])
            ->assertViewIs('livewire.creditor.manage-consumers.consumer-profile.scheduled-transactions')
            ->assertViewHas('scheduledTransactions');
    }

    #[Test]
    public function it_can_render_cancelled_schedule_transactions_view_when_user_superadmin(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        Livewire::test(ScheduledTransactions::class, ['consumer' => $this->consumer])
            ->assertSee(__('Company Name'))
            ->assertOk();
    }

    #[Test]
    public function it_can_render_cancelled_schedule_transactions_view_when_user_creditor(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        Livewire::test(ScheduledTransactions::class, ['consumer' => $this->consumer])
            ->assertDontSee(__('Company Name'))
            ->assertOk();
    }

    #[Test]
    public function it_can_display_scheduled_transactions(): void
    {
        [$firstTransaction, $secondTransaction] = ScheduleTransaction::factory(2)->create([
            'status' => TransactionStatus::SCHEDULED->value,
            'consumer_id' => $this->consumer->id,
        ]);

        Livewire::test(ScheduledTransactions::class, ['consumer' => $this->consumer])
            ->assertSee($firstTransaction->status->displayName())
            ->assertSee($secondTransaction->status->displayName())
            ->assertSee(__('Reschedule'))
            ->assertSee(__('Cancel'))
            ->assertDontSee(__('No result found'));
    }

    #[Test]
    public function it_can_call_trait_mount_method_for_set_schedule_date(): void
    {
        Livewire::test(ScheduledTransactions::class, ['consumer' => $this->consumer])
            ->assertSet('schedule_date', now()->toDateString())
            ->assertOk();
    }

    #[Test]
    public function it_can_reschedule_scheduled_transactions(): void
    {
        $scheduleTransaction = ScheduleTransaction::factory()->create([
            'status' => TransactionStatus::SCHEDULED->value,
            'consumer_id' => $this->consumer->id,
        ]);

        Livewire::test(ScheduledTransactions::class, ['consumer' => $this->consumer])
            ->set('schedule_date', now()->addDay()->toDateString())
            ->call('reschedule', $scheduleTransaction)
            ->assertDispatched('close-dialog');

        $this->assertDatabaseCount(ScheduleTransaction::class, 2);
        $this->assertEquals($scheduleTransaction->refresh()->status, TransactionStatus::CREDITOR_CHANGE_DATE);
    }

    #[Test]
    public function it_can_not_allow_current_date_for_rescheduled(): void
    {
        $scheduleTransaction = ScheduleTransaction::factory()->make([
            'status' => TransactionStatus::SCHEDULED->value,
            'consumer_id' => $this->consumer->id,
        ]);

        Livewire::test(ScheduledTransactions::class, ['consumer' => $this->consumer])
            ->call('reschedule', $scheduleTransaction)
            ->assertSet('schedule_date', now()->toDateString())
            ->assertHasErrors('schedule_date', ['after_or_equal:now']);
    }

    #[Test]
    public function it_can_cancel_the_scheduled_transactions(): void
    {
        config(['services.merchant.tilled_merchant_account_id' => fake()->uuid()]);

        Http::fake(fn () => Http::response(['status' => fake()->randomElement(['succeeded', 'processing'])]));

        MembershipPaymentProfile::factory()->create(['company_id' => $this->consumer->company_id]);

        $consumerNegotiation = ConsumerNegotiation::factory()
            ->for($this->consumer)
            ->create(['payment_plan_current_balance' => '123.34']);

        $scheduleTransaction = ScheduleTransaction::factory()->create([
            'status' => TransactionStatus::SCHEDULED->value,
            'consumer_id' => $this->consumer->id,
            'amount' => '123.34',
        ]);

        Livewire::test(ScheduledTransactions::class, ['consumer' => $this->consumer])
            ->call('cancelScheduled', $scheduleTransaction)
            ->assertDispatched('refresh-please');

        $this->assertEquals(TransactionStatus::CANCELLED, $scheduleTransaction->refresh()->status);
        $this->assertEquals(0.00, $this->consumer->refresh()->current_balance);
        $this->assertEquals(0.00, $consumerNegotiation->refresh()->payment_plan_current_balance);

        $this->assertDatabaseCount(YnTransaction::class, 1);
    }

    #[Test]
    public function it_can_consumer_settle_with_failed_scheduled_transactions(): void
    {
        $this->consumer->update(['status' => ConsumerStatus::SETTLED]);

        [$firstTransaction, $secondTransaction] = ScheduleTransaction::factory(2)->create([
            'status' => TransactionStatus::FAILED->value,
            'consumer_id' => $this->consumer->id,
        ]);

        Livewire::test(ScheduledTransactions::class, ['consumer' => $this->consumer])
            ->assertSee($firstTransaction->status->displayName())
            ->assertSee($secondTransaction->status->displayName())
            ->assertDontSee(__('Reschedule'))
            ->assertDontSee(__('Cancel'))
            ->assertDontSee(__('No result found'));
    }

    #[Test]
    #[DataProvider('dates')]
    public function it_can_skip_payment(string $scheduleDate): void
    {
        ConsumerNegotiation::factory()
            ->for($this->consumer)
            ->create(['installment_type' => InstallmentType::WEEKLY]);

        $scheduleTransaction = ScheduleTransaction::factory()
            ->for($this->consumer->subclient)
            ->for($this->consumer->company)
            ->for($this->consumer)
            ->create([
                'schedule_date' => $scheduleDate,
                'status' => TransactionStatus::SCHEDULED,
            ]);

        Livewire::test(ScheduledTransactions::class, ['consumer' => $this->consumer])
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

    public static function dates(): array
    {
        return [
            'today' => [today()->toDateString()],
            'after two days' => [today()->addDays(2)->toDateString()],
        ];
    }
}

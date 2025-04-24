<?php

declare(strict_types=1);

namespace Tests\Feature\ManageConsumers\ConsumerProfile;

use App\Enums\MerchantType;
use App\Enums\Role as EnumRole;
use App\Enums\TransactionStatus;
use App\Livewire\Creditor\ManageConsumers\ConsumerProfile\Transactions;
use App\Models\Consumer;
use App\Models\ScheduleTransaction;
use App\Models\Transaction;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class TransactionsTest extends AuthTestCase
{
    public Consumer $consumer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = Consumer::factory()->create();
    }

    #[Test]
    public function it_can_called_mount_method_and_set_our_data(): void
    {
        Livewire::test(Transactions::class, ['consumer' => $this->consumer])
            ->assertSet('schedule_date', now()->toDateString())
            ->assertViewIs('livewire.creditor.manage-consumers.consumer-profile.transactions');
    }

    #[Test]
    public function it_can_display_the_no_results_found(): void
    {
        Livewire::test(Transactions::class, ['consumer' => $this->consumer])
            ->assertSee(__('No result found'))
            ->assertOk();
    }

    #[Test]
    public function it_can_render_cancelled_schedule_transactions_view_when_user_superadmin(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        Livewire::test(Transactions::class, ['consumer' => $this->consumer])
            ->assertSee(__('Company Name'))
            ->assertOk();
    }

    #[Test]
    public function it_can_render_cancelled_schedule_transactions_view_when_user_creditor(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        Livewire::test(Transactions::class, ['consumer' => $this->consumer])
            ->assertDontSee(__('Company Name'))
            ->assertOk();
    }

    #[Test]
    public function it_can_display_the_transactions(): void
    {
        $transaction = Transaction::create([
            'consumer_id' => $this->consumer->id,
            'status' => $status = fake()->randomElement([TransactionStatus::SUCCESSFUL->value, TransactionStatus::FAILED->value]),
            'payment_mode' => fake()->randomElement(MerchantType::values()),
            'gateway_response' => [],
        ]);

        Livewire::test(Transactions::class, ['consumer' => $this->consumer])
            ->assertViewHas('transactions')
            ->assertViewIs('livewire.creditor.manage-consumers.consumer-profile.transactions')
            ->assertDontSee(__('No result found'))
            ->assertSee($transaction->created_at->formatWithTimezone())
            ->assertSee($this->consumer->first_name . ' ' . $this->consumer->last_name)
            ->assertSee(Str::of($status)->title()->headline()->toString())
            ->assertSee(Number::currency((float) $transaction->amount ?? 0))
            ->assertOk();
    }

    #[Test]
    public function it_can_not_allow_to_choose_current_date(): void
    {
        $scheduleTransaction = ScheduleTransaction::factory()->create();

        $transaction = Transaction::create([
            'transaction_id' => $scheduleTransaction->transaction_id,
            'consumer_id' => $this->consumer->id,
            'status' => fake()->randomElement([TransactionStatus::SUCCESSFUL->value, TransactionStatus::FAILED->value]),
            'payment_mode' => fake()->randomElement(MerchantType::values()),
            'gateway_response' => [],
        ]);

        Livewire::test(Transactions::class, ['consumer' => $this->consumer])
            ->call('reschedule', $transaction->id)
            ->assertDontSee(__('No result found'))
            ->assertNotDispatched('close-dialog')
            ->assertHasErrors('schedule_date', ['after_or_equal:now']);
    }

    #[Test]
    public function it_can_reschedule_transaction_if_consumer_the_schedule_transaction_is_available(): void
    {
        $scheduleTransaction = ScheduleTransaction::factory()->create(['status' => TransactionStatus::FAILED]);

        $transaction = Transaction::create([
            'transaction_id' => $scheduleTransaction->transaction_id,
            'consumer_id' => $this->consumer->id,
            'status' => fake()->randomElement([TransactionStatus::SUCCESSFUL->value, TransactionStatus::FAILED->value]),
            'payment_mode' => fake()->randomElement(MerchantType::values()),
            'gateway_response' => [],
        ]);

        Livewire::test(Transactions::class, ['consumer' => $this->consumer])
            ->set('schedule_date', now()->addDay()->toDateString())
            ->call('reschedule', $transaction->transaction_id)
            ->assertDontSee(__('No result found'))
            ->assertDispatched('close-dialog');

        $this->assertDatabaseCount(ScheduleTransaction::class, 2);

        $this->assertTrue($scheduleTransaction->refresh()->status === TransactionStatus::RESCHEDULED);
    }

    #[Test]
    public function it_can_not_reschedule_transaction_if_consumer_the_schedule_transaction_is_not_available(): void
    {
        $transaction = Transaction::create([
            'transaction_id' => fake()->randomNumber(6, true),
            'consumer_id' => $this->consumer->id,
            'status' => fake()->randomElement([TransactionStatus::SUCCESSFUL->value, TransactionStatus::FAILED->value]),
            'payment_mode' => fake()->randomElement(MerchantType::values()),
            'gateway_response' => [],
        ]);

        Livewire::test(Transactions::class, ['consumer' => $this->consumer])
            ->set('schedule_date', now()->addDay()->toDateString())
            ->call('reschedule', $transaction->transaction_id)
            ->assertDontSee(__('No result found'))
            ->assertDispatched('close-dialog');

        $this->assertDatabaseCount(ScheduleTransaction::class, 0);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\ManageConsumers\ConsumerProfile;

use App\Enums\MerchantType;
use App\Enums\Role as EnumRole;
use App\Enums\TransactionStatus;
use App\Livewire\Creditor\ManageConsumers\ConsumerProfile\CancelledScheduleTransactions;
use App\Models\Consumer;
use App\Models\ScheduleTransaction;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class CancelledScheduleTransactionsTest extends AuthTestCase
{
    public Consumer $consumer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = Consumer::factory()->create();
    }

    #[Test]
    public function it_can_render_cancelled_schedule_transactions_view(): void
    {
        Livewire::test(CancelledScheduleTransactions::class, ['consumer' => $this->consumer])
            ->assertViewIs('livewire.creditor.manage-consumers.consumer-profile.cancelled-schedule-transactions')
            ->assertViewHas('cancelledScheduleTransactions');
    }

    #[Test]
    public function it_can_render_cancelled_schedule_transactions_view_when_user_superadmin(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        Livewire::test(CancelledScheduleTransactions::class, ['consumer' => $this->consumer])
            ->assertSee(__('Company Name'))
            ->assertSee(__('Subclient Name'))
            ->assertOk();
    }

    #[Test]
    public function it_can_render_cancelled_schedule_transactions_view_when_user_creditor(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        Livewire::test(CancelledScheduleTransactions::class, ['consumer' => $this->consumer])
            ->assertDontSee(__('Company Name'))
            ->assertSee(__('Subclient Name'))
            ->assertOk();
    }

    #[Test]
    public function it_can_display_no_result_found_when_cancelled_transactions_is_not_available(): void
    {
        Livewire::test(CancelledScheduleTransactions::class, ['consumer' => $this->consumer])
            ->assertViewIs('livewire.creditor.manage-consumers.consumer-profile.cancelled-schedule-transactions')
            ->assertOk()
            ->assertSee(__('No result found'));
    }

    #[Test]
    public function it_can_render_cancelled_schedule_transactions(): void
    {
        $cancelledScheduleTransaction = ScheduleTransaction::factory()->create([
            'consumer_id' => $this->consumer->id,
            'status' => TransactionStatus::CANCELLED->value,
            'schedule_date' => today(),
        ]);

        $method = match ($cancelledScheduleTransaction->paymentProfile->method) {
            MerchantType::CC => 'CARD',
            MerchantType::ACH => 'ACH',
        };

        Livewire::test(CancelledScheduleTransactions::class, ['consumer' => $this->consumer])
            ->assertViewIs('livewire.creditor.manage-consumers.consumer-profile.cancelled-schedule-transactions')
            ->assertSee($cancelledScheduleTransaction->schedule_date->format('M d, Y'))
            ->assertSee($cancelledScheduleTransaction->status->displayName())
            ->assertSee($method)
            ->assertDontSee(__('No result found'))
            ->assertOk();
    }
}

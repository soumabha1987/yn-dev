<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Livewire\Creditor\Dashboard\UpcomingTransaction;
use App\Models\Consumer;
use App\Models\ScheduleTransaction;
use App\Models\Subclient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Number;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpcomingTransactionTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['subclient_id' => null]);
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        Livewire::actingAs($this->user)
            ->test(UpcomingTransaction::class)
            ->assertViewIs('livewire.creditor.dashboard.upcoming-transaction')
            ->assertViewHas('transactions', fn (LengthAwarePaginator $transactions) => $transactions->isEmpty())
            ->assertOk();
    }

    #[Test]
    public function it_can_render_schedule_transactions(): void
    {
        $consumer = Consumer::factory()->create(['company_id' => $this->user->company_id]);

        $scheduleTransaction = ScheduleTransaction::factory()
            ->create([
                'consumer_id' => $consumer->id,
                'company_id' => $this->user->company_id,
                'status' => TransactionStatus::SCHEDULED,
            ]);

        Livewire::actingAs($this->user)
            ->test(UpcomingTransaction::class)
            ->assertViewIs('livewire.creditor.dashboard.upcoming-transaction')
            ->assertViewHas('transactions', fn (LengthAwarePaginator $transactions) => $scheduleTransaction->is($transactions->getCollection()->first()))
            ->assertSee($consumer->member_account_number)
            ->assertSee($consumer->first_name . ' ' . $consumer->last_name)
            ->assertSee($consumer->original_account_name)
            ->assertSee($consumer->subclient_name ? $consumer->subclient_name . '/' . $consumer->subclient_account_number : 'N/A')
            ->assertSee($consumer->placement_date ? $consumer->placement_date->format('M d, Y') : 'N/A')
            ->assertSee($scheduleTransaction->transaction_type === TransactionType::PIF ? __('Settle') : __('Pay Plan'))
            ->assertSee(Number::currency((float) ($scheduleTransaction->amount ?? 0)))
            ->assertOk();
    }

    #[Test]
    public function it_can_render_schedule_transactions_for_subclients(): void
    {
        $subclient = Subclient::factory()->for($this->user->company)->create();

        $this->user->update(['subclient_id' => $subclient->id]);

        $consumer = Consumer::factory()->create([
            'company_id' => $this->user->company_id,
            'subclient_id' => $subclient->id,
        ]);

        $scheduleTransaction = ScheduleTransaction::factory()
            ->create([
                'consumer_id' => $consumer->id,
                'subclient_id' => $subclient->id,
                'company_id' => $this->user->company_id,
                'status' => TransactionStatus::SCHEDULED->value,
            ]);

        Livewire::actingAs($this->user)
            ->test(UpcomingTransaction::class)
            ->assertViewIs('livewire.creditor.dashboard.upcoming-transaction')
            ->assertViewHas('transactions', fn (LengthAwarePaginator $transactions) => $scheduleTransaction->is($transactions->getCollection()->first()))
            ->assertSee($consumer->member_account_number)
            ->assertSee($consumer->first_name . ' ' . $consumer->last_name)
            ->assertOk();
    }

    #[Test]
    public function it_can_reschedule_upcoming_transaction(): void
    {
        $upcomingTransaction = ScheduleTransaction::factory()->create([
            'status' => TransactionStatus::CANCELLED->value,
            'company_id' => $this->user->company_id,
        ]);

        Livewire::actingAs($this->user)
            ->test(UpcomingTransaction::class)
            ->set('schedule_date', now()->addDay()->toDateString())
            ->call('reschedule', $upcomingTransaction)
            ->assertDontSee($upcomingTransaction->status)
            ->assertDispatched('close-dialog');

        $this->assertEquals($upcomingTransaction->refresh()->status, TransactionStatus::CREDITOR_CHANGE_DATE);
    }

    #[Test]
    public function it_can_not_allow_current_date_for_rescheduled(): void
    {
        $upcomingTransaction = ScheduleTransaction::factory()->create([
            'status' => TransactionStatus::CANCELLED->value,
            'company_id' => $this->user->company_id,
        ]);

        Livewire::actingAs($this->user)
            ->test(UpcomingTransaction::class)
            ->call('reschedule', $upcomingTransaction)
            ->assertSet('schedule_date', now()->toDateString())
            ->assertHasErrors('schedule_date', ['after_or_equal:now']);
    }

    #[Test]
    public function it_can_export_upcoming_transactions(): void
    {
        $consumer = Consumer::factory()->create(['company_id' => $this->user->company_id]);

        ScheduleTransaction::factory()
            ->create([
                'consumer_id' => $consumer->id,
                'company_id' => $this->user->company_id,
                'status' => TransactionStatus::SCHEDULED->value,
            ]);

        Livewire::actingAs($this->user)
            ->test(UpcomingTransaction::class)
            ->call('export')
            ->assertFileDownloaded()
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_schedule_date(string $direction): void
    {
        $createdScheduleTransactions = ScheduleTransaction::factory(5)
            ->sequence(fn (Sequence $sequence) => ['schedule_date' => now()->addDays($sequence->index)])
            ->for($this->user->company)
            ->for(Consumer::factory()->for($this->user->company))
            ->create([
                'status' => TransactionStatus::SCHEDULED,
                'subclient_id' => null,
            ]);

        Livewire::actingAs($this->user)
            ->test(UpcomingTransaction::class)
            ->assertOk()
            ->assertSet('sortCol', 'schedule_date')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'transactions',
                fn (LengthAwarePaginator $transactions) => $direction === 'ASC'
                    ? $createdScheduleTransactions->last()->is($transactions->getCollection()->first())
                    : $createdScheduleTransactions->first()->is($transactions->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_amount(string $direction): void
    {
        $createdScheduleTransactions = ScheduleTransaction::factory(5)
            ->sequence(fn (Sequence $sequence) => ['amount' => $sequence->index + 33.23])
            ->for($this->user->company)
            ->for(Consumer::factory()->for($this->user->company))
            ->create([
                'status' => TransactionStatus::SCHEDULED,
                'subclient_id' => null,
            ]);

        Livewire::actingAs($this->user)
            ->test(UpcomingTransaction::class)
            ->assertOk()
            ->set('sortCol', 'amount')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'transactions',
                fn (LengthAwarePaginator $transactions) => $direction === 'ASC'
                    ? $createdScheduleTransactions->last()->is($transactions->getCollection()->first())
                    : $createdScheduleTransactions->first()->is($transactions->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_consumer_name(string $direction): void
    {
        $createdScheduleTransactions = ScheduleTransaction::factory(5)
            ->for($this->user->company)
            ->sequence(fn (Sequence $sequence) => [
                'consumer_id' => Consumer::factory()
                    ->for($this->user->company)
                    ->state([
                        'first_name' => null,
                        'last_name' => range('A', 'Z')[$sequence->index + 3],
                        'subclient_id' => null,
                    ]),
            ])
            ->create([
                'status' => TransactionStatus::SCHEDULED,
                'subclient_id' => null,
            ]);

        Livewire::actingAs($this->user)
            ->test(UpcomingTransaction::class)
            ->assertOk()
            ->set('sortCol', 'consumer_name')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'transactions',
                fn (LengthAwarePaginator $transactions) => $direction === 'ASC'
                    ? $createdScheduleTransactions->last()->is($transactions->getCollection()->first())
                    : $createdScheduleTransactions->first()->is($transactions->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_account_number(string $direction): void
    {
        $createdScheduleTransactions = ScheduleTransaction::factory(5)
            ->for($this->user->company)
            ->sequence(fn (Sequence $sequence) => [
                'consumer_id' => Consumer::factory()
                    ->for($this->user->company)
                    ->state([
                        'member_account_number' => $sequence->index + 2,
                        'subclient_id' => null,
                    ]),
            ])
            ->create([
                'status' => TransactionStatus::SCHEDULED,
                'subclient_id' => null,
            ]);

        Livewire::actingAs($this->user)
            ->test(UpcomingTransaction::class)
            ->assertOk()
            ->set('sortCol', 'account_number')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'transactions',
                fn (LengthAwarePaginator $transactions) => $direction === 'ASC'
                    ? $createdScheduleTransactions->last()->is($transactions->getCollection()->first())
                    : $createdScheduleTransactions->first()->is($transactions->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_original_account_name(string $direction): void
    {
        $createdScheduleTransactions = ScheduleTransaction::factory(5)
            ->for($this->user->company)
            ->sequence(fn (Sequence $sequence) => [
                'consumer_id' => Consumer::factory()
                    ->for($this->user->company)
                    ->state([
                        'original_account_name' => range('A', 'Z')[$sequence->index + 1],
                        'subclient_id' => null,
                    ]),
            ])
            ->create([
                'status' => TransactionStatus::SCHEDULED,
                'subclient_id' => null,
            ]);

        Livewire::actingAs($this->user)
            ->test(UpcomingTransaction::class)
            ->assertOk()
            ->set('sortCol', 'account_name')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'transactions',
                fn (LengthAwarePaginator $transactions) => $direction === 'ASC'
                    ? $createdScheduleTransactions->last()->is($transactions->getCollection()->first())
                    : $createdScheduleTransactions->first()->is($transactions->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_sub_name(string $direction): void
    {
        $createdScheduleTransactions = ScheduleTransaction::factory(5)
            ->for($this->user->company)
            ->sequence(fn (Sequence $sequence) => [
                'consumer_id' => Consumer::factory()
                    ->for($this->user->company)
                    ->state([
                        'subclient_name' => range('A', 'Z')[$sequence->index + 1],
                        'subclient_id' => null,
                    ]),
            ])
            ->create([
                'status' => TransactionStatus::SCHEDULED,
                'subclient_id' => null,
            ]);

        Livewire::actingAs($this->user)
            ->test(UpcomingTransaction::class)
            ->assertOk()
            ->set('sortCol', 'sub_name')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'transactions',
                fn (LengthAwarePaginator $transactions) => $direction === 'ASC'
                    ? $createdScheduleTransactions->last()->is($transactions->getCollection()->first())
                    : $createdScheduleTransactions->first()->is($transactions->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_placement_date(string $direction): void
    {
        $createdScheduleTransactions = ScheduleTransaction::factory(5)
            ->for($this->user->company)
            ->sequence(fn (Sequence $sequence) => [
                'consumer_id' => Consumer::factory()
                    ->for($this->user->company)
                    ->state([
                        'placement_date' => today()->addDays($sequence->index + 2)->toDateString(),
                        'subclient_id' => null,
                    ]),
            ])
            ->create([
                'status' => TransactionStatus::SCHEDULED,
                'subclient_id' => null,
            ]);

        Livewire::actingAs($this->user)
            ->test(UpcomingTransaction::class)
            ->assertOk()
            ->set('sortCol', 'placement_date')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'transactions',
                fn (LengthAwarePaginator $transactions) => $direction === 'ASC'
                    ? $createdScheduleTransactions->last()->is($transactions->getCollection()->first())
                    : $createdScheduleTransactions->first()->is($transactions->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_pay_type(string $direction): void
    {
        $createdScheduleTransactions = ScheduleTransaction::factory()
            ->for($this->user->company)
            ->for(
                Consumer::factory()
                    ->for($this->user->company)
                    ->state(['subclient_id' => null]),
            )
            ->forEachSequence(
                ['transaction_type' => TransactionType::INSTALLMENT],
                ['transaction_type' => TransactionType::PIF],
            )
            ->create([
                'status' => TransactionStatus::SCHEDULED,
                'subclient_id' => null,
            ]);

        Livewire::actingAs($this->user)
            ->test(UpcomingTransaction::class)
            ->assertOk()
            ->set('sortCol', 'pay_type')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'transactions',
                fn (LengthAwarePaginator $transactions) => $direction === 'ASC'
                    ? $createdScheduleTransactions->last()->is($transactions->getCollection()->first())
                    : $createdScheduleTransactions->first()->is($transactions->getCollection()->first())
            );
    }

    public static function sortDirection(): array
    {
        return [
            ['ASC'],
            ['DESC'],
        ];
    }
}

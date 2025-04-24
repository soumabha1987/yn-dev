<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Enums\TransactionStatus;
use App\Livewire\Creditor\Dashboard\FailedPayments;
use App\Models\Consumer;
use App\Models\ScheduleTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FailedPaymentsTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Model::preventLazyLoading();

        $this->user = User::factory()->create(['subclient_id' => null]);
    }

    #[Test]
    public function it_can_load_livewire_component(): void
    {
        Livewire::actingAs($this->user)
            ->test(FailedPayments::class)
            ->assertViewIs('livewire.creditor.dashboard.failed-payments')
            ->assertViewHas('scheduleTransactions', fn (LengthAwarePaginator $scheduleTransactions) => $scheduleTransactions->isEmpty())
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_some_data(): void
    {
        $scheduleTransaction = ScheduleTransaction::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'transaction_id' => fake()->uuid(),
                'status' => TransactionStatus::FAILED,
            ]);

        Livewire::actingAs($this->user)
            ->test(FailedPayments::class)
            ->assertViewIs('livewire.creditor.dashboard.failed-payments')
            ->assertViewHas('scheduleTransactions', fn (LengthAwarePaginator $scheduleTransactions) => $scheduleTransaction->is($scheduleTransactions->getCollection()->first()))
            ->assertSee($scheduleTransaction->schedule_date->format('M d, Y'))
            ->assertSee($scheduleTransaction->last_attempted_at->formatWithTimezone())
            ->assertSee($scheduleTransaction->consumer->member_account_number)
            ->assertSee(str($scheduleTransaction->consumer->first_name . ' ' . $scheduleTransaction->consumer->last_name)->title())
            ->assertSee($scheduleTransaction->consumer->original_account_name ?? 'N/A')
            ->assertSee($scheduleTransaction->consumer->subclient_name ?? 'N/A')
            ->assertSee($scheduleTransaction->consumer->placement_date?->format('M d, Y') ?? 'N/A')
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_due_date(string $direction): void
    {
        $createdScheduleTransactions = ScheduleTransaction::factory(10)
            ->sequence(
                fn (Sequence $sequence) => [
                    'schedule_date' => today()->subDays($sequence->index + 2),
                ]
            )
            ->create([
                'company_id' => $this->user->company_id,
                'status' => TransactionStatus::FAILED,
            ]);

        Livewire::actingAs($this->user)
            ->test(FailedPayments::class)
            ->assertOk()
            ->set('sortCol', 'due_date')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'scheduleTransactions',
                fn (LengthAwarePaginator $scheduleTransactions) => $direction === 'ASC'
                    ? $createdScheduleTransactions->last()->is($scheduleTransactions->getCollection()->first())
                    : $createdScheduleTransactions->first()->is($scheduleTransactions->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_last_failed_date(string $direction): void
    {
        $createdScheduleTransactions = ScheduleTransaction::factory(10)
            ->sequence(fn (Sequence $sequence) => ['last_attempted_at' => today()->subDays($sequence->index)])
            ->create([
                'company_id' => $this->user->company_id,
                'status' => TransactionStatus::FAILED,
            ]);

        Livewire::actingAs($this->user)
            ->test(FailedPayments::class)
            ->assertOk()
            ->set('sortCol', 'last_failed_date')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'scheduleTransactions',
                fn (LengthAwarePaginator $scheduleTransactions) => $direction === 'ASC'
                    ? $createdScheduleTransactions->last()->is($scheduleTransactions->getCollection()->first())
                    : $createdScheduleTransactions->first()->is($scheduleTransactions->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_consumer_name(string $direction): void
    {
        $createdScheduleTransactions = ScheduleTransaction::factory(10)
            ->sequence(fn (Sequence $sequence) => ['consumer_id' => Consumer::factory()->state(['first_name' => range('A', 'Z')[$sequence->index + 1]])])
            ->create([
                'company_id' => $this->user->company_id,
                'status' => TransactionStatus::FAILED,
            ]);

        Livewire::actingAs($this->user)
            ->test(FailedPayments::class)
            ->assertOk()
            ->set('sortCol', 'consumer_name')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'scheduleTransactions',
                fn (LengthAwarePaginator $scheduleTransactions) => $direction === 'ASC'
                    ? $createdScheduleTransactions->first()->is($scheduleTransactions->getCollection()->first())
                    : $createdScheduleTransactions->last()->is($scheduleTransactions->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_account_number(string $direction): void
    {
        $createdScheduleTransactions = ScheduleTransaction::factory(10)
            ->sequence(fn (Sequence $sequence) => ['consumer_id' => Consumer::factory()->state(['member_account_number' => $sequence->index + 10000])])
            ->create([
                'company_id' => $this->user->company_id,
                'status' => TransactionStatus::FAILED,
            ]);

        Livewire::actingAs($this->user)
            ->test(FailedPayments::class)
            ->assertOk()
            ->set('sortCol', 'account_number')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'scheduleTransactions',
                fn (LengthAwarePaginator $scheduleTransactions) => $direction === 'ASC'
                    ? $createdScheduleTransactions->first()->is($scheduleTransactions->getCollection()->first())
                    : $createdScheduleTransactions->last()->is($scheduleTransactions->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_account_name(string $direction): void
    {
        $createdScheduleTransactions = ScheduleTransaction::factory(10)
            ->sequence(fn (Sequence $sequence) => [
                'consumer_id' => Consumer::factory()->state(['original_account_name' => range('A', 'Z')[$sequence->index + 3]]),
            ])
            ->create([
                'company_id' => $this->user->company_id,
                'status' => TransactionStatus::FAILED,
            ]);

        Livewire::actingAs($this->user)
            ->test(FailedPayments::class)
            ->assertOk()
            ->set('sortCol', 'account_name')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'scheduleTransactions',
                fn (LengthAwarePaginator $scheduleTransactions) => $direction === 'ASC'
                    ? $createdScheduleTransactions->first()->is($scheduleTransactions->getCollection()->first())
                    : $createdScheduleTransactions->last()->is($scheduleTransactions->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_sub_account_name(string $direction): void
    {
        $createdScheduleTransactions = ScheduleTransaction::factory(10)
            ->sequence(fn (Sequence $sequence) => [
                'consumer_id' => Consumer::factory()->state(['subclient_name' => range('A', 'Z')[$sequence->index + 3]]),
            ])
            ->create([
                'company_id' => $this->user->company_id,
                'status' => TransactionStatus::FAILED,
            ]);

        Livewire::actingAs($this->user)
            ->test(FailedPayments::class)
            ->assertOk()
            ->set('sortCol', 'sub_account_name')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'scheduleTransactions',
                fn (LengthAwarePaginator $scheduleTransactions) => $direction === 'ASC'
                    ? $createdScheduleTransactions->first()->is($scheduleTransactions->getCollection()->first())
                    : $createdScheduleTransactions->last()->is($scheduleTransactions->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_placement_date(string $direction): void
    {
        $createdScheduleTransactions = ScheduleTransaction::factory(10)
            ->sequence(fn (Sequence $sequence) => [
                'consumer_id' => Consumer::factory()->state(['placement_date' => today()->addDays($sequence->index + 3)]),
            ])
            ->create([
                'company_id' => $this->user->company_id,
                'status' => TransactionStatus::FAILED,
            ]);

        Livewire::actingAs($this->user)
            ->test(FailedPayments::class)
            ->assertOk()
            ->set('sortCol', 'placement_date')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'scheduleTransactions',
                fn (LengthAwarePaginator $scheduleTransactions) => $direction === 'ASC'
                    ? $createdScheduleTransactions->first()->is($scheduleTransactions->getCollection()->first())
                    : $createdScheduleTransactions->last()->is($scheduleTransactions->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('searchParams')]
    public function it_can_search_by_consumer_name(string $fieldName): void
    {
        $createdScheduleTransactions = ScheduleTransaction::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'consumer_id' => Consumer::factory()->state(['member_account_number' => range('1', '9')[$sequence->index]]),
            ])
            ->create([
                'company_id' => $this->user->company_id,
                'status' => TransactionStatus::FAILED,
            ]);

        $searchBy = match ($fieldName) {
            'first_name' => $createdScheduleTransactions->first()->consumer->first_name,
            'member_account_number' => $createdScheduleTransactions->first()->consumer->member_account_number,
        };

        Livewire::actingAs($this->user)
            ->test(FailedPayments::class)
            ->assertOk()
            ->set('search', $searchBy)
            ->assertViewHas(
                'scheduleTransactions',
                fn (LengthAwarePaginator $scheduleTransactions) => $createdScheduleTransactions->first()->is($scheduleTransactions->getCollection()->first())
            );
    }

    #[Test]
    public function it_can_export_dispute_consumers(): void
    {
        ScheduleTransaction::factory(10)
            ->create([
                'company_id' => $this->user->company_id,
                'status' => TransactionStatus::FAILED,
            ]);

        Livewire::actingAs($this->user)
            ->test(FailedPayments::class)
            ->call('export')
            ->assertFileDownloaded()
            ->assertOk();
    }

    public static function sortDirection(): array
    {
        return [
            ['ASC'],
            ['DESC'],
        ];
    }

    public static function searchParams(): array
    {
        return [
            ['first_name'],
            ['member_account_number'],
        ];
    }
}

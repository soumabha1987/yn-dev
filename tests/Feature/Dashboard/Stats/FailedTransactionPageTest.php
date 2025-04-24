<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard\Stats;

use App\Enums\CompanyMembershipStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\Role as EnumRole;
use App\Enums\TransactionStatus;
use App\Livewire\Creditor\Dashboard\Stats\FailedTransactionPage;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use App\Models\ScheduleTransaction;
use App\Models\Subclient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FailedTransactionPageTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['subclient_id' => null]);
    }

    #[Test]
    public function it_can_render_livewire_component_when_visit_route(): void
    {
        $this->user->company()->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);

        CompanyMembership::factory()
            ->for($this->user->company)
            ->create([
                'status' => CompanyMembershipStatus::ACTIVE->value,
                'current_plan_end' => now()->addMonth(),
            ]);

        $this->withoutVite()
            ->actingAs($this->user)
            ->get(route('creditor.dashboard.failed-transaction'))
            ->assertSeeLivewire(FailedTransactionPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        Livewire::actingAs($this->user)
            ->test(FailedTransactionPage::class)
            ->assertViewIs('livewire.creditor.dashboard.stats.failed-transaction-page')
            ->assertViewHas('scheduleTransactions', fn (LengthAwarePaginator $scheduleTransactions) => $scheduleTransactions->isEmpty())
            ->assertOk();
    }

    #[Test]
    public function it_can_render_with_some_data(): void
    {
        $consumer = Consumer::factory()->create([
            'company_id' => $this->user->company_id,
        ]);

        $Scheduletransaction = ScheduleTransaction::factory()->create([
            'consumer_id' => $consumer->id,
            'status' => TransactionStatus::FAILED,
            'company_id' => $this->user->company_id,
            'transaction_id' => fake()->uuid(),
        ]);

        $Scheduletransaction->forceFill(['last_attempted_at' => now()->subDays(5)]);
        $Scheduletransaction->save();

        Livewire::actingAs($this->user)
            ->test(FailedTransactionPage::class)
            ->assertViewIs('livewire.creditor.dashboard.stats.failed-transaction-page')
            ->assertViewHas('scheduleTransactions', fn (LengthAwarePaginator $scheduleTransactions) => $Scheduletransaction->is($scheduleTransactions->getCollection()->first()))
            ->assertSee($Scheduletransaction->consumer->member_account_number)
            ->assertSee($Scheduletransaction->consumer->first_name . ' ' . $Scheduletransaction->consumer->last_name)
            ->assertSee($Scheduletransaction->transaction->transaction_id ?? 'N/A')
            ->assertOk();
    }

    #[Test]
    public function it_can_render_with_some_data_for_subclient(): void
    {
        $subclient = Subclient::factory()
            ->for($this->user->company)
            ->create();

        $scheduleTransaction = ScheduleTransaction::factory()
            ->for($this->user->company)
            ->for($subclient)
            ->for(Consumer::factory()->for($this->user->company)->for($subclient))
            ->create(['status' => TransactionStatus::FAILED]);

        $scheduleTransaction->forceFill(['last_attempted_at' => now()->subDays(5)]);
        $scheduleTransaction->save();

        Livewire::actingAs($this->user)
            ->test(FailedTransactionPage::class)
            ->assertOk()
            ->assertViewIs('livewire.creditor.dashboard.stats.failed-transaction-page')
            ->assertViewHas('scheduleTransactions', fn (LengthAwarePaginator $scheduleTransactions) => $scheduleTransaction->is($scheduleTransactions->getCollection()->first()))
            ->assertSee($scheduleTransaction->consumer->member_account_number)
            ->assertSee($scheduleTransaction->consumer->first_name . ' ' . $scheduleTransaction->consumer->last_name)
            ->assertSee($scheduleTransaction->transaction->transaction_id ?? 'N/A');
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_date_time(string $direction): void
    {
        $createdScheduleTransactions = ScheduleTransaction::factory(5)
            ->for($this->user->company)
            ->create(['status' => TransactionStatus::FAILED]);

        $createdScheduleTransactions->each(function (ScheduleTransaction $scheduleTransaction, int $index): void {
            $scheduleTransaction->forceFill(['last_attempted_at' => now()->addDays($index)]);
            $scheduleTransaction->save();
        });

        Livewire::actingAs($this->user)
            ->test(FailedTransactionPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'date_time')
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
    public function it_can_order_by_amount(string $direction): void
    {
        $createdScheduleTransactions = ScheduleTransaction::factory(3)
            ->sequence(
                ['amount' => '12.33'],
                ['amount' => '122.33'],
                ['amount' => '1022.33'],
            )
            ->for($this->user->company)
            ->create([
                'status' => TransactionStatus::FAILED,
                'last_attempted_at' => now()->subDays(5),
            ]);

        Livewire::actingAs($this->user)
            ->test(FailedTransactionPage::class)
            ->assertOk()
            ->set('sortCol', 'amount')
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
    public function it_can_order_by_member_account_number(string $direction): void
    {
        $createdScheduleTransactions = ScheduleTransaction::factory(5)
            ->sequence(fn (Sequence $sequence): array => ['consumer_id' => Consumer::factory()->state(['member_account_number' => $sequence->index])])
            ->for($this->user->company)
            ->create([
                'status' => TransactionStatus::FAILED,
                'last_attempted_at' => now()->subDays(5),
            ]);

        Livewire::actingAs($this->user)
            ->test(FailedTransactionPage::class)
            ->assertOk()
            ->set('sortCol', 'member_account_number')
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
    public function it_can_order_by_consumer_name(string $direction): void
    {
        $createdScheduleTransactions = ScheduleTransaction::factory(5)
            ->sequence(fn (Sequence $sequence): array => [
                'consumer_id' => Consumer::factory()
                    ->state([
                        'first_name' => null,
                        'last_name' => range('A', 'Z')[$sequence->index],
                    ]),
            ])
            ->for($this->user->company)
            ->create([
                'status' => TransactionStatus::FAILED,
                'last_attempted_at' => now()->subDays(5),
            ]);

        Livewire::actingAs($this->user)
            ->test(FailedTransactionPage::class)
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

    public static function sortDirection(): array
    {
        return [
            ['ASC'],
            ['DESC'],
        ];
    }
}

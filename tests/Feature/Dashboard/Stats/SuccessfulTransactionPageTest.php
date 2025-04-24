<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard\Stats;

use App\Enums\CompanyMembershipStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\Role as EnumRole;
use App\Enums\TransactionStatus;
use App\Livewire\Creditor\Dashboard\Stats\SuccessfulTransactionPage;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use App\Models\Subclient;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuccessfulTransactionPageTest extends TestCase
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
            ->get(route('creditor.dashboard.successful-transaction'))
            ->assertSeeLivewire(SuccessfulTransactionPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        Livewire::actingAs($this->user)
            ->test(SuccessfulTransactionPage::class)
            ->assertViewIs('livewire.creditor.dashboard.stats.successful-transaction-page')
            ->assertViewHas('transactions', fn (LengthAwarePaginator $transactions) => $transactions->isEmpty())
            ->assertOk();
    }

    #[Test]
    public function it_can_render_with_some_data(): void
    {
        $consumer = Consumer::factory()->create([
            'company_id' => $this->user->company_id,
        ]);

        $transaction = Transaction::query()->create([
            'consumer_id' => $consumer->id,
            'status' => TransactionStatus::SUCCESSFUL->value,
            'company_id' => $this->user->company_id,
            'transaction_id' => fake()->uuid(),
        ]);

        $transaction->forceFill(['created_at' => now()->subDays(5)]);
        $transaction->save();

        Livewire::actingAs($this->user)
            ->test(SuccessfulTransactionPage::class)
            ->assertViewIs('livewire.creditor.dashboard.stats.successful-transaction-page')
            ->assertViewHas('transactions', fn (LengthAwarePaginator $transactions) => $transaction->is($transactions->getCollection()->first()))
            ->assertSee($transaction->consumer->member_account_number)
            ->assertSee($transaction->consumer->first_name . ' ' . $transaction->consumer->last_name)
            ->assertSee($transaction->transaction_id)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_with_some_data_for_subclient(): void
    {
        $subclient = Subclient::factory()
            ->for($this->user->company)
            ->create();

        $transaction = Transaction::factory()
            ->for($this->user->company)
            ->for($subclient)
            ->for(Consumer::factory()->for($this->user->company)->for($subclient))
            ->create(['status' => TransactionStatus::SUCCESSFUL]);

        $transaction->forceFill(['created_at' => now()->subDays(5)]);
        $transaction->save();

        Livewire::actingAs($this->user)
            ->test(SuccessfulTransactionPage::class)
            ->assertOk()
            ->assertViewIs('livewire.creditor.dashboard.stats.successful-transaction-page')
            ->assertViewHas('transactions', fn (LengthAwarePaginator $transactions) => $transaction->is($transactions->getCollection()->first()))
            ->assertSee($transaction->consumer->member_account_number)
            ->assertSee($transaction->consumer->first_name . ' ' . $transaction->consumer->last_name)
            ->assertSee($transaction->transaction_id);
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_date_time(string $direction): void
    {
        $createdTransactions = Transaction::factory(5)
            ->for($this->user->company)
            ->create(['status' => TransactionStatus::SUCCESSFUL]);

        $createdTransactions->each(function (Transaction $transaction, int $index): void {
            $transaction->forceFill(['created_at' => now()->addDays($index)]);
            $transaction->save();
        });

        Livewire::actingAs($this->user)
            ->test(SuccessfulTransactionPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'date_time')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'transactions',
                fn (LengthAwarePaginator $transactions) => $direction === 'ASC'
                    ? $createdTransactions->first()->is($transactions->getCollection()->first())
                    : $createdTransactions->last()->is($transactions->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_amount(string $direction): void
    {
        $createdTransactions = Transaction::factory(5)
            ->sequence(fn (Sequence $sequence): array => ['amount' => $sequence->index + 37.44])
            ->for($this->user->company)
            ->create(['status' => TransactionStatus::SUCCESSFUL]);

        Livewire::actingAs($this->user)
            ->test(SuccessfulTransactionPage::class)
            ->assertOk()
            ->set('sortCol', 'amount')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'transactions',
                fn (LengthAwarePaginator $transactions) => $direction === 'ASC'
                    ? $createdTransactions->first()->is($transactions->getCollection()->first())
                    : $createdTransactions->last()->is($transactions->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_member_account_number(string $direction): void
    {
        $createdTransactions = Transaction::factory(5)
            ->sequence(fn (Sequence $sequence): array => ['consumer_id' => Consumer::factory()->state(['member_account_number' => $sequence->index])])
            ->for($this->user->company)
            ->create(['status' => TransactionStatus::SUCCESSFUL]);

        Livewire::actingAs($this->user)
            ->test(SuccessfulTransactionPage::class)
            ->assertOk()
            ->set('sortCol', 'member_account_number')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'transactions',
                fn (LengthAwarePaginator $transactions) => $direction === 'ASC'
                    ? $createdTransactions->first()->is($transactions->getCollection()->first())
                    : $createdTransactions->last()->is($transactions->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_consumer_name(string $direction): void
    {
        $createdTransactions = Transaction::factory(5)
            ->sequence(fn (Sequence $sequence): array => [
                'consumer_id' => Consumer::factory()
                    ->state([
                        'first_name' => null,
                        'last_name' => range('A', 'Z')[$sequence->index],
                    ]),
            ])
            ->for($this->user->company)
            ->create(['status' => TransactionStatus::SUCCESSFUL]);

        Livewire::actingAs($this->user)
            ->test(SuccessfulTransactionPage::class)
            ->assertOk()
            ->set('sortCol', 'consumer_name')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'transactions',
                fn (LengthAwarePaginator $transactions) => $direction === 'ASC'
                    ? $createdTransactions->first()->is($transactions->getCollection()->first())
                    : $createdTransactions->last()->is($transactions->getCollection()->first())
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

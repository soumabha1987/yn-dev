<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Enums\ConsumerStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Livewire\Creditor\Dashboard\RecentTransaction;
use App\Models\Consumer;
use App\Models\Subclient;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecentTransactionTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Model::preventLazyLoading();

        $this->user = User::factory()->create(['subclient_id' => null]);
    }

    #[Test]
    public function it_can_render_livewire_component(): void
    {
        Livewire::actingAs($this->user)
            ->test(RecentTransaction::class)
            ->assertViewIs('livewire.creditor.dashboard.recent-transaction')
            ->assertViewHas('transactions', fn (LengthAwarePaginator $transactions) => $transactions->isEmpty())
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_some_data(): void
    {
        $consumer = Consumer::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'status' => fake()->randomElement([
                    ConsumerStatus::PAYMENT_SETUP,
                    ConsumerStatus::SETTLED,
                    ConsumerStatus::PAYMENT_ACCEPTED,
                ]),
            ]);

        $transaction = Transaction::query()->create([
            'company_id' => $this->user->company_id,
            'consumer_id' => $consumer->id,
            'status' => TransactionStatus::SUCCESSFUL,
            'transaction_type' => TransactionType::PIF,
            'amount' => 10.23,
        ]);

        Livewire::actingAs($this->user)
            ->test(RecentTransaction::class)
            ->assertViewIs('livewire.creditor.dashboard.recent-transaction')
            ->assertViewHas('transactions', fn (LengthAwarePaginator $transactions) => $transaction->is($transactions->getCollection()->first()))
            ->assertSee($transaction->created_at->formatWithTimezone())
            ->assertSee($transaction->consumer->member_account_number)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_some_data_for_subclient(): void
    {
        $subclient = Subclient::factory()->for($this->user->company)->create();

        $this->user->update(['subclient_id' => $subclient->id]);

        $consumer = Consumer::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => $subclient->id,
                'status' => fake()->randomElement([
                    ConsumerStatus::PAYMENT_SETUP,
                    ConsumerStatus::SETTLED,
                    ConsumerStatus::PAYMENT_ACCEPTED,
                ]),
            ]);

        $transaction = Transaction::query()->create([
            'company_id' => $this->user->company_id,
            'subclient_id' => $subclient->id,
            'consumer_id' => $consumer->id,
            'status' => TransactionStatus::SUCCESSFUL,
            'transaction_type' => TransactionType::PIF,
            'amount' => 10.23,
        ]);

        Livewire::actingAs($this->user)
            ->test(RecentTransaction::class)
            ->assertViewIs('livewire.creditor.dashboard.recent-transaction')
            ->assertViewHas('transactions', fn (LengthAwarePaginator $transactions) => $transaction->is($transactions->getCollection()->first()))
            ->assertSee($transaction->created_at->formatWithTimezone(format: 'M d, Y h:i A'))
            ->assertSee($transaction->consumer->member_account_number)
            ->assertOk();
    }

    #[Test]
    public function it_can_export_recent_transactions(): void
    {
        Consumer::factory(5)
            ->create([
                'company_id' => $this->user->company_id,
                'status' => fake()->randomElement([
                    ConsumerStatus::PAYMENT_SETUP,
                    ConsumerStatus::SETTLED,
                    ConsumerStatus::PAYMENT_ACCEPTED,
                ]),
            ])->each(function (Consumer $consumer): void {
                Transaction::query()->create([
                    'consumer_id' => $consumer->id,
                    'company_id' => $consumer->company_id,
                    'transaction_type' => TransactionType::PIF,
                    'status' => TransactionStatus::SUCCESSFUL,
                ]);
            });

        Livewire::actingAs($this->user)
            ->test(RecentTransaction::class)
            ->call('export')
            ->assertFileDownloaded()
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_date(string $direction): void
    {
        $createdTransactions = Transaction::factory(5)
            ->for($this->user->company)
            ->for(Consumer::factory()
                ->state([
                    'company_id' => $this->user->company_id,
                    'status' => fake()->randomElement([
                        ConsumerStatus::PAYMENT_SETUP,
                        ConsumerStatus::SETTLED,
                        ConsumerStatus::PAYMENT_ACCEPTED,
                    ]),
                ]))
            ->create(['status' => TransactionStatus::SUCCESSFUL]);

        $createdTransactions->each(function (Transaction $transaction, int $index): void {
            $transaction->forceFill(['created_at' => now()->addDays($index)]);
            $transaction->save();
        });

        Livewire::actingAs($this->user)
            ->test(RecentTransaction::class)
            ->assertOk()
            ->assertSet('sortCol', 'date')
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
    public function it_can_order_by_account_number(string $direction): void
    {
        $createdTransactions = Transaction::factory(5)
            ->for($this->user->company)
            ->sequence(fn (Sequence $sequence): array => [
                'consumer_id' => Consumer::factory()
                    ->state([
                        'member_account_number' => $sequence->index,
                        'company_id' => $this->user->company_id,
                        'status' => fake()->randomElement([
                            ConsumerStatus::PAYMENT_SETUP,
                            ConsumerStatus::SETTLED,
                            ConsumerStatus::PAYMENT_ACCEPTED,
                        ]),
                    ]),
            ])
            ->create(['status' => TransactionStatus::SUCCESSFUL]);

        Livewire::actingAs($this->user)
            ->test(RecentTransaction::class)
            ->assertOk()
            ->set('sortCol', 'account_number')
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
            ->for($this->user->company)
            ->sequence(fn (Sequence $sequence): array => [
                'consumer_id' => Consumer::factory()
                    ->state([
                        'first_name' => null,
                        'last_name' => range('A', 'Z')[$sequence->index + 3],
                        'company_id' => $this->user->company_id,
                        'status' => fake()->randomElement([
                            ConsumerStatus::PAYMENT_SETUP,
                            ConsumerStatus::SETTLED,
                            ConsumerStatus::PAYMENT_ACCEPTED,
                        ]),
                    ]),
            ])
            ->create(['status' => TransactionStatus::SUCCESSFUL]);

        Livewire::actingAs($this->user)
            ->test(RecentTransaction::class)
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

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_type(string $direction): void
    {
        $consumer = Consumer::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'status' => fake()->randomElement([
                    ConsumerStatus::PAYMENT_SETUP,
                    ConsumerStatus::SETTLED,
                    ConsumerStatus::PAYMENT_ACCEPTED,
                ]),
            ]);

        $createdTransactions = Transaction::factory()
            ->for($this->user->company)
            ->forEachSequence(
                ['transaction_type' => TransactionType::INSTALLMENT],
                ['transaction_type' => TransactionType::PIF]
            )
            ->create([
                'consumer_id' => $consumer->id,
                'status' => TransactionStatus::SUCCESSFUL,
            ]);

        Livewire::actingAs($this->user)
            ->test(RecentTransaction::class)
            ->assertOk()
            ->set('sortCol', 'transaction_type')
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
            ->for($this->user->company)
            ->for(Consumer::factory()
                ->state([
                    'company_id' => $this->user->company_id,
                    'status' => fake()->randomElement([
                        ConsumerStatus::PAYMENT_SETUP,
                        ConsumerStatus::SETTLED,
                        ConsumerStatus::PAYMENT_ACCEPTED,
                    ]),
                ]))
            ->create(['status' => TransactionStatus::SUCCESSFUL]);

        $createdTransactions->each(function (Transaction $transaction, int $index): void {
            $transaction->forceFill(['amount' => range(111, 999)[$index]]);
            $transaction->save();
        });

        Livewire::actingAs($this->user)
            ->test(RecentTransaction::class)
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
    public function it_can_order_by_consumer_sub_client_name(string $direction): void
    {
        $createdTransactions = Transaction::factory(5)
            ->for($this->user->company)
            ->sequence(fn (Sequence $sequence): array => [
                'consumer_id' => Consumer::factory()
                    ->state([
                        'subclient_name' => range('A', 'Z')[$sequence->index + 3],
                        'company_id' => $this->user->company_id,
                        'status' => fake()->randomElement([
                            ConsumerStatus::PAYMENT_SETUP,
                            ConsumerStatus::SETTLED,
                            ConsumerStatus::PAYMENT_ACCEPTED,
                        ]),
                    ]),
            ])
            ->create(['status' => TransactionStatus::SUCCESSFUL]);

        Livewire::actingAs($this->user)
            ->test(RecentTransaction::class)
            ->assertOk()
            ->set('sortCol', 'subclient_name')
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
    public function it_can_order_by_consumer_placement_date(string $direction): void
    {
        $createdTransactions = Transaction::factory(5)
            ->for($this->user->company)
            ->sequence(fn (Sequence $sequence): array => [
                'consumer_id' => Consumer::factory()
                    ->state([
                        'placement_date' => now()->subDays($sequence->index + 3)->toDateString(),
                        'company_id' => $this->user->company_id,
                        'status' => fake()->randomElement([
                            ConsumerStatus::PAYMENT_SETUP,
                            ConsumerStatus::SETTLED,
                            ConsumerStatus::PAYMENT_ACCEPTED,
                        ]),
                    ]),
            ])
            ->create(['status' => TransactionStatus::SUCCESSFUL]);

        Livewire::actingAs($this->user)
            ->test(RecentTransaction::class)
            ->assertOk()
            ->set('sortCol', 'placement_date')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'transactions',
                fn (LengthAwarePaginator $transactions) => $direction === 'ASC'
                    ? $createdTransactions->first()->is($transactions->getCollection()->last())
                    : $createdTransactions->last()->is($transactions->getCollection()->last())
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

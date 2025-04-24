<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Enums\NegotiationType;
use App\Livewire\Creditor\Dashboard\RecentlyCompletedNegotiations;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecentlyCompletedNegotiatiationsTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['subclient_id' => null]);
    }

    #[Test]
    public function it_can_render_livewire_component_with_data(): void
    {
        Livewire::actingAs($this->user)
            ->test(RecentlyCompletedNegotiations::class)
            ->assertViewIs('livewire.creditor.dashboard.recently-completed-negotiations')
            ->assertViewHas('consumers', fn (LengthAwarePaginator $consumers) => $consumers->isEmpty())
            ->assertOk();
    }

    #[Test]
    public function it_can_render_with_consumer_data(): void
    {
        $consumer = Consumer::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'offer_accepted' => true,
                'counter_offer' => true,
                'payment_setup' => true,
            ]);

        $consumerNegotiation = ConsumerNegotiation::factory()->for($consumer)
            ->create([
                'company_id' => $consumer->company_id,
                'offer_accepted' => true,
                'first_pay_date' => today()->addDay(),
                'counter_offer_accepted' => false,
            ]);

        [$promiseAmount, $promiseDate] = match (true) {
            $consumerNegotiation->negotiation_type === NegotiationType::PIF && $consumerNegotiation->offer_accepted => [$consumerNegotiation->one_time_settlement, $consumerNegotiation->first_pay_date],
            $consumerNegotiation->negotiation_type === NegotiationType::PIF && $consumerNegotiation->counter_offer_accepted => [$consumerNegotiation->counter_one_time_amount, $consumerNegotiation->counter_first_pay_date],
            $consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT && $consumerNegotiation->offer_accepted => [$consumerNegotiation->negotiate_amount, $consumerNegotiation->first_pay_date],
            $consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT && $consumerNegotiation->counter_offer_accepted => [$consumerNegotiation->counter_negotiate_amount, $consumerNegotiation->counter_first_pay_date],
            default => [null, null],
        };

        Livewire::actingAs($this->user)
            ->test(RecentlyCompletedNegotiations::class)
            ->assertViewHas('consumers', fn (LengthAwarePaginator $consumers) => $consumer->is($consumers->getCollection()->first()))
            ->assertSee($consumer->member_account_number)
            ->assertSee($consumer->original_account_name)
            ->assertSee(str($consumer->first_name . ' ' . $consumer->last_name)->title())
            ->assertSee(str($consumer->original_account_name)->title())
            ->assertSee(Number::currency((float) $promiseAmount ?? 0))
            ->assertSee($promiseDate ? $promiseDate->format('M d, Y') : 'N/A')
            ->assertOk();
    }

    #[Test]
    public function it_can_render_consumer_with_successfull_transaction(): void
    {
        $consumer = Consumer::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'offer_accepted' => true,
                'counter_offer' => true,
                'payment_setup' => true,
            ]);

        ConsumerNegotiation::factory()->for($consumer)
            ->create([
                'company_id' => $consumer->company_id,
                'offer_accepted' => true,
                'first_pay_date' => today()->addDays(20),
                'counter_offer_accepted' => false,
            ]);

        Transaction::factory()->for($consumer)->create();

        Livewire::actingAs($this->user)
            ->test(RecentlyCompletedNegotiations::class)
            ->assertViewHas('consumers', fn (LengthAwarePaginator $consumers) => $consumer->isNot($consumers->getCollection()->first()))
            ->assertDontSee($consumer->member_account_number)
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_consumer_name(string $direction): void
    {
        $consumersData = Consumer::factory(10)
            ->Sequence(
                fn (Sequence $sequence) => ['first_name' => range('a', 'z')[$sequence->index]],
            )
            ->create([
                'company_id' => $this->user->company_id,
                'offer_accepted' => true,
                'counter_offer' => true,
                'payment_setup' => true,
            ])->each(function (Consumer $consumer) {
                ConsumerNegotiation::factory()->for($consumer)->create([
                    'company_id' => $consumer->company_id,
                    'offer_accepted' => true,
                    'first_pay_date' => today()->addDay(),
                    'counter_offer_accepted' => false,
                ]);
            });

        Livewire::actingAs($this->user)
            ->test(RecentlyCompletedNegotiations::class)
            ->assertOk()
            ->set('sortCol', 'consumer-name')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $consumersData->first()->is($consumers->getCollection()->first())
                    : $consumersData->last()->is($consumers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_account_number(string $direction): void
    {
        $consumersData = Consumer::factory(10)
            ->Sequence(
                fn (Sequence $sequence) => ['member_account_number' => range('a', 'z')[$sequence->index]],
            )
            ->create([
                'company_id' => $this->user->company_id,
                'offer_accepted' => true,
                'counter_offer' => true,
                'payment_setup' => true,
            ])->each(function (Consumer $consumer) {
                ConsumerNegotiation::factory()->for($consumer)->create([
                    'company_id' => $consumer->company_id,
                    'offer_accepted' => true,
                    'first_pay_date' => today()->addDay(),
                    'counter_offer_accepted' => false,
                ]);
            });

        Livewire::actingAs($this->user)
            ->test(RecentlyCompletedNegotiations::class)
            ->assertOk()
            ->set('sortCol', 'master-account-number')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $consumersData->first()->is($consumers->getCollection()->first())
                    : $consumersData->last()->is($consumers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_account_name(string $direction): void
    {
        $consumersData = Consumer::factory(25)
            ->sequence(fn (Sequence $sequence) => ['original_account_name' => range('A', 'Z')[$sequence->index]])
            ->create([
                'company_id' => $this->user->company_id,
                'offer_accepted' => true,
                'counter_offer' => true,
                'payment_setup' => true,
            ])
            ->each(function (Consumer $consumer) {
                ConsumerNegotiation::factory()->create([
                    'consumer_id' => $consumer->id,
                    'company_id' => $consumer->company_id,
                    'offer_accepted' => true,
                    'first_pay_date' => today()->addDay(),
                    'counter_offer_accepted' => false,
                ]);
            });

        Livewire::actingAs($this->user)
            ->test(RecentlyCompletedNegotiations::class)
            ->assertOk()
            ->set('sortCol', 'account-name')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $consumersData->first()->is($consumers->getCollection()->first())
                    : $consumersData->last()->is($consumers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_sub_account_name(string $direction): void
    {
        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'subclient_name' => range('A', 'Z')[$sequence->index + 5],
            ])
            ->create([
                'company_id' => $this->user->company_id,
                'counter_offer' => true,
                'offer_accepted' => true,
                'payment_setup' => true,
            ])->each(fn (Consumer $consumer) => [
                ConsumerNegotiation::factory()
                    ->create([
                        'company_id' => $consumer->company_id,
                        'consumer_id' => $consumer->id,
                        'offer_accepted' => true,
                        'first_pay_date' => today()->addDay(),
                        'counter_offer_accepted' => false,
                    ]),
            ]);

        Livewire::actingAs($this->user)
            ->test(RecentlyCompletedNegotiations::class)
            ->assertOk()
            ->set('sortCol', 'sub-account-name')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $createdConsumers->first()->is($consumers->getCollection()->first())
                    : $createdConsumers->last()->is($consumers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_placement_date(string $direction): void
    {
        $consumersData = Consumer::factory()
            ->forEachSequence(
                ['placement_date' => now()->subDays(2)],
                ['placement_date' => now()->subDay()]
            )
            ->create([
                'company_id' => $this->user->company_id,
                'offer_accepted' => true,
                'counter_offer' => true,
                'payment_setup' => true,
            ])->each(function (Consumer $consumer) {
                ConsumerNegotiation::factory()->for($consumer)->create([
                    'company_id' => $consumer->company_id,
                    'offer_accepted' => true,
                    'first_pay_date' => today()->addDay(),
                    'counter_offer_accepted' => false,
                ]);
            });

        Livewire::actingAs($this->user)
            ->test(RecentlyCompletedNegotiations::class)
            ->assertOk()
            ->set('sortCol', 'placement-date')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $consumersData->first()->is($consumers->getCollection()->first())
                    : $consumersData->last()->is($consumers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_offer_type(string $direction): void
    {
        $consumersData = Consumer::factory(2)
            ->has(
                ConsumerNegotiation::factory()
                    ->state(fn (array $attributes, Consumer $consumer) => [
                        'company_id' => $consumer->company_id,
                        'offer_accepted' => true,
                        'first_pay_date' => today()->addDay(),
                        'counter_offer_accepted' => false,
                    ])
                    ->sequence(
                        ['negotiation_type' => NegotiationType::INSTALLMENT->value],
                        ['negotiation_type' => NegotiationType::PIF->value]
                    )
            )
            ->create([
                'company_id' => $this->user->company_id,
                'offer_accepted' => true,
                'counter_offer' => true,
                'payment_setup' => true,
            ]);

        Livewire::actingAs($this->user)
            ->test(RecentlyCompletedNegotiations::class)
            ->assertOk()
            ->set('sortCol', 'offer-type')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $consumersData->first()->is($consumers->getCollection()->first())
                    : $consumersData->last()->is($consumers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_beg_balance(string $direction): void
    {
        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'total_balance' => ($sequence->index * 100),
            ])
            ->create([
                'company_id' => $this->user->company_id,
                'counter_offer' => true,
                'offer_accepted' => true,
                'payment_setup' => true,
            ])->each(fn (Consumer $consumer) => [
                ConsumerNegotiation::factory()
                    ->create([
                        'company_id' => $consumer->company_id,
                        'consumer_id' => $consumer->id,
                        'offer_accepted' => true,
                        'first_pay_date' => today()->addDay(),
                        'counter_offer_accepted' => false,
                    ]),
            ]);

        Livewire::actingAs($this->user)
            ->test(RecentlyCompletedNegotiations::class)
            ->assertOk()
            ->set('sortCol', 'beg-balance')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $createdConsumers->first()->is($consumers->getCollection()->first())
                    : $createdConsumers->last()->is($consumers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_negotiation_pay_off_balance(string $direction): void
    {
        $consumersData = Consumer::factory(10)
            ->create([
                'company_id' => $this->user->company_id,
                'offer_accepted' => true,
                'counter_offer' => true,
                'payment_setup' => true,
            ])
            ->each(function (Consumer $consumer, int $index) {
                $negotiationType = fake()->randomElement([
                    NegotiationType::PIF,
                    NegotiationType::INSTALLMENT,
                ]);

                $offerAccepted = fake()->boolean();
                $counterOfferAccepted = ! $offerAccepted;

                ConsumerNegotiation::factory()->create([
                    'consumer_id' => $consumer->id,
                    'company_id' => $consumer->company_id,
                    'negotiation_type' => $negotiationType,
                    'offer_accepted' => $offerAccepted,
                    'counter_offer_accepted' => $counterOfferAccepted,
                    'one_time_settlement' => $index * 100,
                    'counter_one_time_amount' => $counterOfferAccepted && $negotiationType === NegotiationType::PIF
                        ? ($index * 100)
                        : null,
                    'negotiate_amount' => $index * 100,
                    'counter_negotiate_amount' => $counterOfferAccepted && $negotiationType === NegotiationType::INSTALLMENT
                        ? ($index * 100)
                        : null,
                    'first_pay_date' => today()->addDay(),
                    'counter_first_pay_date' => today()->addDay(),
                ]);
            });

        Livewire::actingAs($this->user)
            ->test(RecentlyCompletedNegotiations::class)
            ->assertOk()
            ->set('sortCol', 'pay-off-balance')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $consumersData->first()->is($consumers->getCollection()->first())
                    : $consumersData->last()->is($consumers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_amount(string $direction): void
    {
        $consumersData = Consumer::factory(10)
            ->create([
                'company_id' => $this->user->company_id,
                'offer_accepted' => true,
                'counter_offer' => true,
                'payment_setup' => true,
            ])
            ->each(function (Consumer $consumer, int $index) {
                ConsumerNegotiation::factory()->create([
                    'consumer_id' => $consumer->id,
                    'company_id' => $consumer->company_id,
                    'negotiation_type' => $negotiationType = fake()->randomElement([NegotiationType::PIF, NegotiationType::INSTALLMENT]),
                    'counter_offer_accepted' => $counterOfferAccepted = fake()->boolean(),
                    'offer_accepted' => ! $counterOfferAccepted,
                    'first_pay_date' => ! $counterOfferAccepted ? today()->addDay() : null,
                    'counter_first_pay_date' => $counterOfferAccepted ? today()->addDay() : null,
                    'one_time_settlement' => $amount = $index + 10,
                    'counter_one_time_amount' => $counterOfferAccepted && $negotiationType === NegotiationType::PIF
                        ? $amount : null,
                    'monthly_amount' => $amount,
                    'counter_monthly_amount' => $counterOfferAccepted && $negotiationType === NegotiationType::INSTALLMENT
                        ? $amount : null,
                ]);
            });

        Livewire::actingAs($this->user)
            ->test(RecentlyCompletedNegotiations::class)
            ->assertOk()
            ->set('sortCol', 'promise-amount')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $consumersData->first()->is($consumers->getCollection()->first())
                    : $consumersData->last()->is($consumers->getCollection()->first())
            );
    }

    #[Test]
    public function it_can_export_completed_negotiations_consumers(): void
    {
        Storage::fake();

        Consumer::factory(2)
            ->has(
                ConsumerNegotiation::factory()
                    ->state(fn (array $attributes, Consumer $consumer) => [
                        'company_id' => $consumer->company_id,
                        'offer_accepted' => true,
                        'first_pay_date' => today()->addDay(),
                        'counter_offer_accepted' => false,
                    ])
            )
            ->create([
                'company_id' => $this->user->company_id,
                'offer_accepted' => true,
                'counter_offer' => true,
                'payment_setup' => true,
            ]);

        Livewire::actingAs($this->user)
            ->test(RecentlyCompletedNegotiations::class)
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
}

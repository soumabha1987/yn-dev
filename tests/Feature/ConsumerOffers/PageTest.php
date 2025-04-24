<?php

declare(strict_types=1);

namespace Tests\Feature\ConsumerOffers;

use App\Enums\CompanyMembershipStatus;
use App\Enums\ConsumerStatus;
use App\Enums\NegotiationType;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\ConsumerOffers\Page;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PageTest extends TestCase
{
    use CreateConsumerOffers;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Model::preventLazyLoading();

        $this->user = $this->createConsumerOffers();

        $this->withoutVite()
            ->actingAs($this->user);
    }

    #[Test]
    public function it_can_render_the_consumer_offer_page(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        CompanyMembership::factory()
            ->for($this->user->company)
            ->create([
                'status' => CompanyMembershipStatus::ACTIVE,
                'current_plan_end' => now()->addMonth(),
            ]);

        $this->get(route('creditor.consumer-offers'))
            ->assertSeeLivewire(Page::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_the_consumer_offer_with_some_data(): void
    {
        $consumer = Consumer::query()
            ->with('consumerNegotiation')
            ->where('custom_offer', true)
            ->where('status', ConsumerStatus::PAYMENT_SETUP->value)
            ->first();

        Livewire::test(Page::class)
            ->assertViewIs('livewire.creditor.consumer-offers.page')
            ->assertSet('isRecentlyCompletedNegotiation', false)
            ->assertDontSee(__('No result found'))
            ->assertSee(Str::of($consumer->first_name . ' ' . $consumer->last_name)->title()->headline()->toString())
            ->assertSee($consumer->member_account_number)
            ->assertDontSee(__('Negotiated Balance'))
            ->assertSee(str($consumer->original_account_name)->title())
            ->assertSee($consumer->consumerNegotiation->negotiation_type->displayOfferBadge())
            ->assertSee($consumer->payment_setup ? __('Yes') : __('No'))
            ->assertSee(__('Action'))
            ->assertOk();
    }

    #[Test]
    public function it_can_show_only_recently_completed_negotiations(): void
    {
        $consumer = Consumer::query()
            ->with('consumerNegotiation')
            ->whereNot('status', ConsumerStatus::PAYMENT_SETUP->value)
            ->where('company_id', $this->user->company_id)
            ->first();

        $consumer->consumerNegotiation->update([
            'active_negotiation' => true,
            'company_id' => $this->user->company_id,
        ]);

        $consumerNegotiation = $consumer->consumerNegotiation;

        $negotiatedAmount = 0;

        if ($consumerNegotiation->negotiation_type === NegotiationType::PIF) {
            $negotiatedAmount = $consumerNegotiation->counter_one_time_amount ?? $consumerNegotiation->one_time_settlement ?? 0;
        } elseif ($consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT) {
            $negotiatedAmount = $consumerNegotiation->counter_negotiate_amount ?? $consumerNegotiation->negotiate_amount ?? 0;
        }

        Livewire::test(Page::class)
            ->set('isRecentlyCompletedNegotiation', true)
            ->assertDontSee(__('Action'))
            ->assertDontSee(__('No result found'))
            ->assertSee(Str::of($consumer->first_name . ' ' . $consumer->last_name)->title()->headline()->toString())
            ->assertSee($consumer->member_account_number)
            ->assertSee(__('Negotiated Balance'))
            ->assertSee(Number::currency((float) $negotiatedAmount))
            ->assertSee(str($consumer->original_account_name)->title())
            ->assertSee($consumer->consumerNegotiation->negotiation_type->displayOfferBadge())
            ->assertSee($consumer->payment_setup ? __('Yes') : __('No'))
            ->assertViewHas('offers', fn (LengthAwarePaginator $offers) => $offers->getCollection()->contains($consumer))
            ->assertOk();
    }

    #[Test]
    public function it_can_export_consumer_offers_data(): void
    {
        Storage::fake();

        Livewire::test(Page::class)
            ->call('export')
            ->assertFileDownloaded()
            ->assertOk();
    }

    #[Test]
    #[DataProvider('searchParams')]
    public function it_can_search_by_consumer_name(string $fieldName): void
    {
        $consumer = Consumer::query()
            ->with('consumerNegotiation')
            ->where('status', ConsumerStatus::PAYMENT_SETUP->value)
            ->where('company_id', $this->user->company_id)
            ->where('custom_offer', true)
            ->first();

        $searchBy = match ($fieldName) {
            'first_name' => $consumer->first_name,
            'member_account_number' => $consumer->member_account_number,
        };

        Livewire::withQueryParams(['search' => $searchBy])
            ->test(Page::class)
            ->assertViewHas('offers', fn (LengthAwarePaginator $offers) => $consumer->is($offers->getCollection()->first()))
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_offer_date(string $direction): void
    {
        Consumer::query()->delete();
        ConsumerNegotiation::query()->delete();

        $createdConsumers = Consumer::factory(10)
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'counter_offer' => false,
                'offer_accepted' => false,
                'custom_offer' => true,
                'status' => ConsumerStatus::PAYMENT_SETUP->value,
            ])->each(fn (Consumer $consumer, int $index) => [
                ConsumerNegotiation::factory()
                    ->create([
                        'consumer_id' => $consumer->id,
                        'company_id' => $consumer->company_id,
                        'active_negotiation' => true,
                        'offer_accepted' => false,
                        'created_at' => now()->addDays($index + 2),
                    ]),
            ]);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->assertOk()
            ->set('sortCol', 'offer_date')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'offers',
                fn (LengthAwarePaginator $offers) => $direction === 'ASC'
                    ? $createdConsumers->first()->is($offers->getCollection()->first())
                    : $createdConsumers->last()->is($offers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_consumer_name(string $direction): void
    {
        Consumer::query()->delete();
        ConsumerNegotiation::query()->delete();

        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'first_name' => range('A', 'Z')[$sequence->index + 2],
            ])
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'counter_offer' => false,
                'offer_accepted' => false,
                'custom_offer' => true,
                'status' => ConsumerStatus::PAYMENT_SETUP->value,
            ])->each(fn (Consumer $consumer) => [
                ConsumerNegotiation::factory()
                    ->create([
                        'consumer_id' => $consumer->id,
                        'company_id' => $consumer->company_id,
                        'active_negotiation' => true,
                        'offer_accepted' => false,
                    ]),
            ]);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->assertOk()
            ->set('sortCol', 'consumer_name')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'offers',
                fn (LengthAwarePaginator $offers) => $direction === 'ASC'
                    ? $createdConsumers->first()->is($offers->getCollection()->first())
                    : $createdConsumers->last()->is($offers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_account_number(string $direction): void
    {
        Consumer::query()->delete();
        ConsumerNegotiation::query()->delete();

        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'member_account_number' => $sequence->index + 2,
            ])
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'counter_offer' => false,
                'offer_accepted' => false,
                'custom_offer' => true,
                'status' => ConsumerStatus::PAYMENT_SETUP->value,
            ])->each(fn (Consumer $consumer) => [
                ConsumerNegotiation::factory()
                    ->create([
                        'consumer_id' => $consumer->id,
                        'company_id' => $consumer->company_id,
                        'active_negotiation' => true,
                        'offer_accepted' => false,
                    ]),
            ]);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->assertOk()
            ->set('sortCol', 'account_number')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'offers',
                fn (LengthAwarePaginator $offers) => $direction === 'ASC'
                    ? $createdConsumers->first()->is($offers->getCollection()->first())
                    : $createdConsumers->last()->is($offers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_original_account_name(string $direction): void
    {
        Consumer::query()->delete();
        ConsumerNegotiation::query()->delete();

        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'original_account_name' => range('A', 'Z')[$sequence->index + 5],
            ])
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'counter_offer' => false,
                'offer_accepted' => false,
                'custom_offer' => true,
                'status' => ConsumerStatus::PAYMENT_SETUP->value,
            ])->each(fn (Consumer $consumer) => [
                ConsumerNegotiation::factory()
                    ->create([
                        'consumer_id' => $consumer->id,
                        'company_id' => $consumer->company_id,
                        'active_negotiation' => true,
                        'offer_accepted' => false,
                    ]),
            ]);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->assertOk()
            ->set('sortCol', 'original_account_name')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'offers',
                fn (LengthAwarePaginator $offers) => $direction === 'ASC'
                    ? $createdConsumers->first()->is($offers->getCollection()->first())
                    : $createdConsumers->last()->is($offers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_original_sub_name(string $direction): void
    {
        Consumer::query()->delete();
        ConsumerNegotiation::query()->delete();

        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'subclient_name' => range('A', 'Z')[$sequence->index + 5],
            ])
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'counter_offer' => false,
                'offer_accepted' => false,
                'custom_offer' => true,
                'status' => ConsumerStatus::PAYMENT_SETUP->value,
            ])->each(fn (Consumer $consumer) => [
                ConsumerNegotiation::factory()
                    ->create([
                        'consumer_id' => $consumer->id,
                        'company_id' => $consumer->company_id,
                        'active_negotiation' => true,
                        'offer_accepted' => false,
                    ]),
            ]);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->assertOk()
            ->set('sortCol', 'sub_name')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'offers',
                fn (LengthAwarePaginator $offers) => $direction === 'ASC'
                    ? $createdConsumers->first()->is($offers->getCollection()->first())
                    : $createdConsumers->last()->is($offers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_original_placement_date(string $direction): void
    {
        Consumer::query()->delete();
        ConsumerNegotiation::query()->delete();

        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'placement_date' => today()->subDays($sequence->index + 2),
            ])
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'counter_offer' => false,
                'offer_accepted' => false,
                'custom_offer' => true,
                'status' => ConsumerStatus::PAYMENT_SETUP->value,
            ])->each(fn (Consumer $consumer) => [
                ConsumerNegotiation::factory()
                    ->create([
                        'consumer_id' => $consumer->id,
                        'company_id' => $consumer->company_id,
                        'active_negotiation' => true,
                        'offer_accepted' => false,
                    ]),
            ]);

        Livewire::actingAs($this->user)
            ->test(page::class)
            ->assertOk()
            ->set('sortCol', 'placement_date')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'offers',
                fn (LengthAwarePaginator $offers) => $direction === 'ASC'
                    ? $createdConsumers->last()->is($offers->getCollection()->first())
                    : $createdConsumers->first()->is($offers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_offer_type(string $direction): void
    {
        Consumer::query()->delete();
        ConsumerNegotiation::query()->delete();

        $createdConsumers = Consumer::factory(2)
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'counter_offer' => false,
                'offer_accepted' => false,
                'custom_offer' => true,
                'status' => ConsumerStatus::PAYMENT_SETUP->value,
            ])->each(fn (Consumer $consumer, int $index) => [
                ConsumerNegotiation::factory()
                    ->create([
                        'consumer_id' => $consumer->id,
                        'company_id' => $consumer->company_id,
                        'offer_accepted' => false,
                        'active_negotiation' => true,
                        'negotiation_type' => $index % 2 ? NegotiationType::PIF : NegotiationType::INSTALLMENT,
                    ]),
            ]);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->assertOk()
            ->set('sortCol', 'offer_type')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'offers',
                fn (LengthAwarePaginator $offers) => $direction === 'ASC'
                    ? $createdConsumers->first()->is($offers->getCollection()->first())
                    : $createdConsumers->last()->is($offers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_original_payment_profile(string $direction): void
    {
        Consumer::query()->delete();
        ConsumerNegotiation::query()->delete();

        $createdConsumers = Consumer::factory()
            ->forEachSequence(
                ['payment_setup' => 0],
                ['payment_setup' => 1],
            )
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'counter_offer' => false,
                'offer_accepted' => false,
                'custom_offer' => true,
                'status' => ConsumerStatus::PAYMENT_SETUP->value,
            ])->each(fn (Consumer $consumer) => [
                ConsumerNegotiation::factory()
                    ->create([
                        'consumer_id' => $consumer->id,
                        'company_id' => $consumer->company_id,
                        'active_negotiation' => true,
                        'offer_accepted' => false,
                    ]),
            ]);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->assertOk()
            ->set('sortCol', 'payment_profile')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'offers',
                fn (LengthAwarePaginator $offers) => $direction === 'ASC'
                    ? $createdConsumers->first()->is($offers->getCollection()->first())
                    : $createdConsumers->last()->is($offers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_consumer_last_offer(string $direction): void
    {
        Consumer::query()->delete();
        ConsumerNegotiation::query()->delete();

        $createdConsumers = Consumer::factory(10)
            ->create([
                'company_id' => $this->user->company_id,
                'offer_accepted' => false,
                'counter_offer' => false,
                'subclient_id' => null,
                'custom_offer' => true,
                'status' => ConsumerStatus::PAYMENT_SETUP->value,
            ])
            ->each(function (Consumer $consumer, int $index) {
                $negotiationType = fake()->randomElement([NegotiationType::PIF, NegotiationType::INSTALLMENT]);

                ConsumerNegotiation::factory()->create([
                    'consumer_id' => $consumer->id,
                    'company_id' => $consumer->company_id,
                    'negotiation_type' => $negotiationType,
                    'offer_accepted' => false,
                    'counter_offer_accepted' => false,
                    'active_negotiation' => true,
                    'one_time_settlement' => $negotiationType === NegotiationType::PIF ? ($index * 100) : null,
                    'monthly_amount' => $negotiationType === NegotiationType::INSTALLMENT ? ($index * 100) : null,
                ]);
            });

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->assertOk()
            ->set('sortCol', 'consumer_last_offer')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'offers',
                fn (LengthAwarePaginator $offers) => $direction === 'ASC'
                    ? $createdConsumers->first()->is($offers->getCollection()->first())
                    : $createdConsumers->last()->is($offers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_status(string $direction): void
    {
        Consumer::query()->delete();
        ConsumerNegotiation::query()->delete();

        $createdConsumers = Consumer::factory()
            ->forEachSequence(
                ['counter_offer' => 0],
                ['counter_offer' => 1],
            )
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'offer_accepted' => false,
                'custom_offer' => true,
                'status' => ConsumerStatus::PAYMENT_SETUP->value,
            ])->each(fn (Consumer $consumer) => [
                ConsumerNegotiation::factory()
                    ->create([
                        'consumer_id' => $consumer->id,
                        'company_id' => $consumer->company_id,
                        'active_negotiation' => true,
                        'offer_accepted' => false,
                    ]),
            ]);

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->assertOk()
            ->set('sortCol', 'status')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'offers',
                fn (LengthAwarePaginator $offers) => $direction === 'ASC'
                    ? $createdConsumers->last()->is($offers->getCollection()->first())
                    : $createdConsumers->first()->is($offers->getCollection()->first())
            );
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

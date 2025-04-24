<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Enums\CompanyMembershipStatus;
use App\Enums\CompanyStatus;
use App\Enums\ConsumerFields;
use App\Enums\ConsumerStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\CustomContentType;
use App\Enums\NegotiationType;
use App\Enums\Role as EnumsRole;
use App\Livewire\Creditor\Dashboard\OpenNegotiations;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\CsvHeader;
use App\Models\CustomContent;
use App\Models\Merchant;
use App\Models\Subclient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Number;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OpenNegotiationsTest extends TestCase
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
            ->test(OpenNegotiations::class)
            ->assertViewIs('livewire.creditor.dashboard.open-negotiation')
            ->assertViewHas('consumers', fn (LengthAwarePaginator $consumers) => $consumers->getCollection()->isEmpty())
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view_and_renders(): void
    {
        $consumer = Consumer::factory()->create([
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'counter_offer' => false,
            'offer_accepted' => false,
            'status' => ConsumerStatus::PAYMENT_SETUP->value,
        ]);

        ConsumerNegotiation::factory()->create([
            'consumer_id' => $consumer->id,
            'company_id' => $consumer->company_id,
            'offer_accepted' => false,
            'counter_offer_accepted' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(OpenNegotiations::class)
            ->assertViewIs('livewire.creditor.dashboard.open-negotiation')
            ->assertViewHas('consumers', fn (LengthAwarePaginator $consumers): bool => $consumer->is($consumers->getCollection()->first()))
            ->assertSee($consumer->member_account_number)
            ->assertSee(str($consumer->first_name . ' ' . $consumer->last_name)->title())
            ->assertSee($consumer->original_account_name ?? 'N/A')
            ->assertSee($consumer->subclient_name ? str($consumer->subclient_name . '/' . $consumer->subclient_account_number) : 'N/A')
            ->assertSee($consumer->placement_date ? $consumer->placement_date->format('M d, Y') : 'N/A')
            ->assertSee(Number::currency((float) $consumer->current_balance ?? 0))
            ->assertSee($consumer->payment_setup ? __('Yes') : __('No'))
            ->assertSee($consumer->counter_offer ? __('Pending Consumer Response') : __('New Offer!'))
            ->assertOk();
    }

    #[Test]
    public function visit_the_dashboard_and_it_will_redirect_to_setup_wizard_page_if_role_is_creditor_and_incomplete_require_steps(): void
    {
        $role = Role::query()->create(['name' => EnumsRole::CREDITOR]);
        $this->user->assignRole($role);

        $this->withoutVite()
            ->actingAs($this->user)
            ->get(route('home'))
            ->assertRedirect(route('creditor.setup-wizard'))
            ->assertStatus(Response::HTTP_FOUND);
    }

    #[Test]
    public function visit_the_open_negotiation_page_and_it_will_redirect_to_dashboard_if_role_is_creditor_and_complete_require_setup_wizard(): void
    {
        $role = Role::query()->create(['name' => EnumsRole::CREDITOR]);
        $this->user->assignRole($role);

        $this->user->update(['subclient_id' => null]);

        $this->user->company()->update(['status' => CompanyStatus::ACTIVE]);

        CustomContent::factory()
            ->forEachSequence(
                ['type' => CustomContentType::ABOUT_US],
                ['type' => CustomContentType::TERMS_AND_CONDITIONS]
            )
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
            ]);

        Merchant::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'verified_at' => now(),
            ]);

        CsvHeader::query()->create([
            'name' => fake()->word(),
            'subclient_id' => null,
            'company_id' => $this->user->company_id,
            'is_mapped' => true,
            'headers' => [
                'EMAIL_ID' => ConsumerFields::CONSUMER_EMAIL->value,
            ],
        ]);

        $this->withoutVite()
            ->actingAs($this->user)
            ->get(route('home'))
            ->assertRedirect(route('creditor.dashboard'))
            ->assertStatus(Response::HTTP_FOUND);
    }

    #[Test]
    public function visit_the_open_negotiation_page_and_it_will_redirect_to_dashboard_if_role_is_superadmin(): void
    {
        $role = Role::query()->create(['name' => EnumsRole::SUPERADMIN]);
        $this->user->assignRole($role);

        $this->withoutVite()
            ->actingAs($this->user)
            ->get(route('home'))
            ->assertRedirect(route('super-admin.manage-creditors'))
            ->assertStatus(Response::HTTP_FOUND);
    }

    #[Test]
    public function it_can_not_allow_user_to_visit_the_component_because_user_needs_role_of_creditor(): void
    {
        $this->withoutVite()
            ->actingAs($this->user)
            ->get(route('creditor.dashboard'))
            ->assertForbidden();
    }

    #[Test]
    public function it_can_allow_to_visit_the_dashboard(): void
    {
        $role = Role::query()->create(['name' => EnumsRole::CREDITOR]);
        $this->user->assignRole($role);

        $this->user->company()->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        CompanyMembership::factory()
            ->for($this->user->company)
            ->create([
                'status' => CompanyMembershipStatus::ACTIVE->value,
                'current_plan_end' => now()->addMonth(),
            ]);

        $this->withoutVite()
            ->actingAs($this->user)
            ->get(route('creditor.dashboard'))
            ->assertSeeLivewire(OpenNegotiations::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view_and_renders_for_subclient(): void
    {
        $subclient = Subclient::factory()->for($this->user->company)->create();

        $this->user->update(['subclient_id' => $subclient->id]);

        $consumer = Consumer::factory()->create([
            'company_id' => $this->user->company_id,
            'subclient_id' => $subclient->id,
            'counter_offer' => false,
            'status' => ConsumerStatus::PAYMENT_SETUP,
            'offer_accepted' => false,
        ]);

        ConsumerNegotiation::factory()->create([
            'consumer_id' => $consumer->id,
            'company_id' => $consumer->company_id,
            'offer_accepted' => false,
            'counter_offer_accepted' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(OpenNegotiations::class)
            ->assertViewIs('livewire.creditor.dashboard.open-negotiation')
            ->assertViewHas('consumers', fn (LengthAwarePaginator $consumers): bool => $consumer->is($consumers->getCollection()->first()))
            ->assertSee($consumer->member_account_number)
            ->assertSee(Number::currency((float) $consumer->current_balance))
            ->assertOk();
    }

    #[Test]
    public function it_can_set_the_default_data_of_the_open_negotiation_offer(): void
    {
        Livewire::actingAs($this->user)
            ->test(OpenNegotiations::class)
            ->assertViewIs('livewire.creditor.dashboard.open-negotiation')
            ->assertSet('counterOffer.offer.discounted_pif_amount', 0)
            ->assertSet('counterOffer.offer.discounted_settlement_amount', 0)
            ->assertSet('counterOffer.offer.minimum_monthly_payment', 0)
            ->assertSet('counterOffer.offer.first_payment_date', null)
            ->assertSet('counterOffer.offer.note', null)
            ->assertSet('counterOffer.counter_offer.discounted_pif_amount', 0)
            ->assertSet('counterOffer.counter_offer.discounted_settlement_amount', 0)
            ->assertSet('counterOffer.counter_offer.minimum_monthly_payment', 0)
            ->assertSet('counterOffer.counter_offer.first_payment_date', null)
            ->assertSet('counterOffer.counter_offer.note', null)
            ->assertOk();
    }

    #[Test]
    public function it_exports_open_negotiation_offer_data(): void
    {
        Consumer::factory(5)->create([
            'company_id' => $this->user->company_id,
            'counter_offer' => true,
            'offer_accepted' => false,
            'status' => ConsumerStatus::PAYMENT_SETUP->value,
        ])
            ->each(function (Consumer $consumer) {
                ConsumerNegotiation::factory()->create([
                    'consumer_id' => $consumer->id,
                    'company_id' => $consumer->company_id,
                    'active_negotiation' => true,
                    'offer_accepted' => false,
                    'counter_offer_accepted' => false,
                ]);
            });

        Livewire::actingAs($this->user)
            ->test(OpenNegotiations::class)
            ->call('export')
            ->assertFileDownloaded()
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_offer_date(string $direction): void
    {
        $createdConsumers = Consumer::factory(10)
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'counter_offer' => false,
                'offer_accepted' => false,
                'status' => ConsumerStatus::PAYMENT_SETUP->value,
            ])->each(fn (Consumer $consumer, int $index) => [
                ConsumerNegotiation::factory()
                    ->create([
                        'consumer_id' => $consumer->id,
                        'company_id' => $consumer->company_id,
                        'offer_accepted' => false,
                        'counter_offer_accepted' => false,
                        'created_at' => now()->addDays($index + 2),
                    ]),
            ]);

        Livewire::actingAs($this->user)
            ->test(OpenNegotiations::class)
            ->assertOk()
            ->set('sortCol', 'offer_date')
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
    public function it_can_order_by_consumer_name(string $direction): void
    {
        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'first_name' => range('A', 'Z')[$sequence->index + 2],
            ])
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'counter_offer' => false,
                'offer_accepted' => false,
                'status' => ConsumerStatus::PAYMENT_SETUP->value,
            ])->each(fn (Consumer $consumer) => [
                ConsumerNegotiation::factory()
                    ->create([
                        'consumer_id' => $consumer->id,
                        'company_id' => $consumer->company_id,
                        'offer_accepted' => false,
                        'counter_offer_accepted' => false,
                    ]),
            ]);

        Livewire::actingAs($this->user)
            ->test(OpenNegotiations::class)
            ->assertOk()
            ->set('sortCol', 'consumer_name')
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
    public function it_can_order_by_account_number(string $direction): void
    {
        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'member_account_number' => $sequence->index + 2,
            ])
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'counter_offer' => false,
                'offer_accepted' => false,
                'status' => ConsumerStatus::PAYMENT_SETUP->value,
            ])->each(fn (Consumer $consumer) => [
                ConsumerNegotiation::factory()
                    ->create([
                        'consumer_id' => $consumer->id,
                        'company_id' => $consumer->company_id,
                        'offer_accepted' => false,
                        'counter_offer_accepted' => false,
                    ]),
            ]);

        Livewire::actingAs($this->user)
            ->test(OpenNegotiations::class)
            ->assertOk()
            ->set('sortCol', 'account_number')
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
    public function it_can_order_by_original_account_name(string $direction): void
    {
        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'original_account_name' => range('A', 'Z')[$sequence->index + 5],
            ])
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'counter_offer' => false,
                'offer_accepted' => false,
                'status' => ConsumerStatus::PAYMENT_SETUP->value,
            ])->each(fn (Consumer $consumer) => [
                ConsumerNegotiation::factory()
                    ->create([
                        'consumer_id' => $consumer->id,
                        'company_id' => $consumer->company_id,
                        'offer_accepted' => false,
                        'counter_offer_accepted' => false,
                    ]),
            ]);

        Livewire::actingAs($this->user)
            ->test(OpenNegotiations::class)
            ->assertOk()
            ->set('sortCol', 'original_account_name')
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
    public function it_can_order_by_original_sub_name(string $direction): void
    {
        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'subclient_name' => range('A', 'Z')[$sequence->index + 5],
            ])
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'counter_offer' => false,
                'offer_accepted' => false,
                'status' => ConsumerStatus::PAYMENT_SETUP->value,
            ])->each(fn (Consumer $consumer) => [
                ConsumerNegotiation::factory()
                    ->create([
                        'consumer_id' => $consumer->id,
                        'company_id' => $consumer->company_id,
                        'offer_accepted' => false,
                        'counter_offer_accepted' => false,
                    ]),
            ]);

        Livewire::actingAs($this->user)
            ->test(OpenNegotiations::class)
            ->assertOk()
            ->set('sortCol', 'sub_name')
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
    public function it_can_order_by_original_placement_date(string $direction): void
    {
        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'placement_date' => today()->subDays($sequence->index + 2),
            ])
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'counter_offer' => false,
                'offer_accepted' => false,
                'status' => ConsumerStatus::PAYMENT_SETUP->value,
            ])->each(fn (Consumer $consumer) => [
                ConsumerNegotiation::factory()
                    ->create([
                        'consumer_id' => $consumer->id,
                        'company_id' => $consumer->company_id,
                        'offer_accepted' => false,
                        'counter_offer_accepted' => false,
                    ]),
            ]);

        Livewire::actingAs($this->user)
            ->test(OpenNegotiations::class)
            ->assertOk()
            ->set('sortCol', 'placement_date')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $createdConsumers->last()->is($consumers->getCollection()->first())
                    : $createdConsumers->first()->is($consumers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_original_payment_profile(string $direction): void
    {
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
                'status' => ConsumerStatus::PAYMENT_SETUP->value,
            ])->each(fn (Consumer $consumer) => [
                ConsumerNegotiation::factory()
                    ->create([
                        'consumer_id' => $consumer->id,
                        'company_id' => $consumer->company_id,
                        'offer_accepted' => false,
                        'counter_offer_accepted' => false,
                    ]),
            ]);

        Livewire::actingAs($this->user)
            ->test(OpenNegotiations::class)
            ->assertOk()
            ->set('sortCol', 'payment_profile')
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
    public function it_can_sort_by_consumer_last_offer(string $direction): void
    {
        $createdConsumers = Consumer::factory(10)
            ->create([
                'company_id' => $this->user->company_id,
                'offer_accepted' => false,
                'counter_offer' => false,
                'subclient_id' => null,
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
                    'one_time_settlement' => $negotiationType === NegotiationType::PIF ? ($index * 100) : null,
                    'monthly_amount' => $negotiationType === NegotiationType::INSTALLMENT ? ($index * 100) : null,
                ]);
            });

        Livewire::actingAs($this->user)
            ->test(OpenNegotiations::class)
            ->assertOk()
            ->set('sortCol', 'consumer_last_offer')
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
    public function it_can_order_by_original_counter_offer(string $direction): void
    {
        $createdConsumers = Consumer::factory()
            ->forEachSequence(
                ['counter_offer' => 0],
                ['counter_offer' => 1],
            )
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'offer_accepted' => false,
                'status' => ConsumerStatus::PAYMENT_SETUP->value,
            ])->each(fn (Consumer $consumer) => [
                ConsumerNegotiation::factory()
                    ->create([
                        'consumer_id' => $consumer->id,
                        'company_id' => $consumer->company_id,
                        'offer_accepted' => false,
                        'counter_offer_accepted' => false,
                    ]),
            ]);

        Livewire::actingAs($this->user)
            ->test(OpenNegotiations::class)
            ->assertOk()
            ->set('sortCol', 'status')
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
    public function it_can_order_by_offer_type(string $direction): void
    {
        $createdConsumers = Consumer::factory(2)
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'counter_offer' => false,
                'offer_accepted' => false,
                'status' => ConsumerStatus::PAYMENT_SETUP->value,
            ])->each(fn (Consumer $consumer, int $index) => [
                ConsumerNegotiation::factory()
                    ->create([
                        'consumer_id' => $consumer->id,
                        'company_id' => $consumer->company_id,
                        'offer_accepted' => false,
                        'counter_offer_accepted' => false,
                        'negotiation_type' => $index % 2 ? NegotiationType::PIF : NegotiationType::INSTALLMENT,
                    ]),
            ]);

        Livewire::actingAs($this->user)
            ->test(OpenNegotiations::class)
            ->assertOk()
            ->set('sortCol', 'offer_type')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $createdConsumers->first()->is($consumers->getCollection()->first())
                    : $createdConsumers->last()->is($consumers->getCollection()->first())
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

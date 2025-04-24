<?php

declare(strict_types=1);

namespace Tests\Feature\ManageConsumers;

use App\Enums\CompanyStatus;
use App\Enums\ConsumerStatus;
use App\Enums\NegotiationType;
use App\Enums\Role as EnumRole;
use App\Enums\SubclientStatus;
use App\Livewire\Creditor\ManageConsumers\ListPage;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\MembershipPaymentProfile;
use App\Models\Subclient;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class ListPageTest extends AuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->user->update(['subclient_id' => null]);
    }

    #[Test]
    public function it_can_render_livewire_component(): void
    {
        $this->get(route('manage-consumers'))
            ->assertSeeLivewire(ListPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_display_only_those_consumer_which_is_under_the_company(): void
    {
        $underCompanyConsumer = Consumer::factory()->create([
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'status' => ConsumerStatus::UPLOADED->value,
        ]);

        Consumer::factory()->create([
            'status' => ConsumerStatus::PAYMENT_ACCEPTED->value,
            'first_name' => 'John Doe',
        ]);

        Livewire::test(ListPage::class)
            ->assertViewIs('livewire.creditor.manage-consumers.list-page')
            ->assertDontSee('John Doe')
            ->assertSee($underCompanyConsumer->first_name)
            ->assertSee($underCompanyConsumer->last_name)
            ->assertSee($underCompanyConsumer->dob->format('M d, Y'))
            ->assertSee(Number::currency((float) $underCompanyConsumer->current_balance))
            ->assertSee($underCompanyConsumer->original_account_name ? str($underCompanyConsumer->original_account_name)->title() : 'N/A')
            ->assertSee($underCompanyConsumer->subclient_name ? str($underCompanyConsumer->subclient_name . '/' . $underCompanyConsumer->subclient_account_number)->title() : 'N/A')
            ->assertSee($underCompanyConsumer->placement_date ? $underCompanyConsumer->placement_date->format('M d, Y') : 'N/A')
            ->assertSee(in_array($underCompanyConsumer->status, [ConsumerStatus::DEACTIVATED, ConsumerStatus::DISPUTE, ConsumerStatus::NOT_PAYING]) ? __('Removed') : __('Active'))
            ->assertSee(__('Offer Delivered'));
    }

    #[Test]
    public function it_can_display_all_consumers_when_role_super_admin(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        $underCompanyConsumer = Consumer::factory()->create([
            'company_id' => $this->user->company_id,
            'status' => ConsumerStatus::UPLOADED,
        ]);

        MembershipPaymentProfile::factory()->for($company = Company::factory()->create())->create();

        $consumer = Consumer::factory()->for($company)->create(['status' => ConsumerStatus::PAYMENT_ACCEPTED]);

        ConsumerNegotiation::factory()->for($consumer)->create();

        Livewire::test(ListPage::class)
            ->assertSee($consumer->first_name)
            ->assertSee($consumer->company->company_name)
            ->assertSee($underCompanyConsumer->first_name)
            ->assertSee($underCompanyConsumer->last_name);
    }

    #[Test]
    public function it_can_render_deleted_company_consumer_display(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        $consumer = Consumer::factory()
            ->for(Company::factory()->create(['deleted_at' => now()]))
            ->create(['status' => ConsumerStatus::JOINED]);

        Livewire::test(ListPage::class)
            ->assertSee($consumer->first_name)
            ->assertSee($consumer->last4ssn)
            ->assertSee($consumer->last_name);
    }

    #[Test]
    public function it_can_fetch_consumers_of_current_authenticated_user_company(): void
    {
        Livewire::test(ListPage::class)
            ->assertDontSee('Company Name')
            ->assertSee(__('No result found'));
    }

    #[Test]
    public function if_super_admin_is_logged_in_then_its_display_company_column(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        Livewire::test(ListPage::class)
            ->assertSee('Company Name')
            ->assertSee(__('No result found'));
    }

    #[Test]
    public function it_can_pass_the_consumers_with_view(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        $consumer = Consumer::factory()->create(['status' => ConsumerStatus::JOINED]);

        $companyWithoutCompleteStep = Company::factory()->create(['status' => CompanyStatus::CREATED]);

        MembershipPaymentProfile::factory()->create(['company_id' => $consumer->company_id]);

        Livewire::test(ListPage::class)
            ->assertOk()
            ->assertViewHas('consumers', function (LengthAwarePaginator $consumers) use ($consumer) {
                $passedConsumer = $consumers->getCollection()->first();

                return $passedConsumer->id === $consumer->id;
            })
            ->assertSet('subclient', null)
            ->assertViewHas('subclients', [])
            ->assertViewHas(
                'companies',
                fn (array $companies) => in_array($consumer->company->company_name, $companies)
                    && ! in_array($companyWithoutCompleteStep->company_name, $companies)
            );
    }

    #[Test]
    public function it_can_pass_the_consumers_with_view_creditor(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        $consumer = Consumer::factory()
            ->for($this->user->company)
            ->for(Subclient::factory()->create([
                'company_id' => $this->user->company_id,
                'status' => SubclientStatus::ACTIVE,
            ]))
            ->create(['status' => ConsumerStatus::JOINED->value]);

        Livewire::test(ListPage::class)
            ->assertOk()
            ->assertViewHas('consumers', function (LengthAwarePaginator $consumers) use ($consumer) {
                $passedConsumer = $consumers->getCollection()->first();

                return $passedConsumer->id === $consumer->id;
            })
            ->assertSet('subclient', null)
            ->assertViewHas('subclients', fn (array $subclients) => in_array($consumer->subclient->subclient_name, $subclients))
            ->assertViewHas('companies', []);
    }

    #[Test]
    public function it_can_pass_the_consumers_subclient_filter(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        $consumer = Consumer::factory()
            ->for($this->user->company)
            ->create([
                'status' => ConsumerStatus::JOINED->value,
                'subclient_id' => $this->user->subclient_id,
            ]);

        Livewire::withUrlParams(['subclient' => $this->user->subclient_id])
            ->test(ListPage::class)
            ->assertOk()
            ->assertViewHas('consumers', function (LengthAwarePaginator $consumers) use ($consumer) {
                $passedConsumer = $consumers->getCollection()->first();

                return $passedConsumer->id === $consumer->id;
            })
            ->assertViewHas('companies', []);
    }

    #[Test]
    public function it_can_filter_by_company(): void
    {
        Consumer::factory()
            ->create([
                'first_name' => $otherConsumerName = 'dispute_consumer',
                'status' => ConsumerStatus::DISPUTE,
            ]);

        $consumer = Consumer::factory()->create([
            'status' => ConsumerStatus::JOINED->value,
            'first_name' => $consumerName = 'joined_consumer',
            'company_id' => $this->user->company_id,
        ]);

        Livewire::test(ListPage::class)
            ->set('company', $consumer->company_id)
            ->assertViewHas('consumers')
            ->assertSee($consumerName)
            ->assertDontSee($otherConsumerName);
    }

    #[Test]
    public function it_can_filter_by_status_of_agreed_settlement_pending_payment(): void
    {
        $consumerNegotiations = ConsumerNegotiation::factory()
            ->forEachSequence(
                [
                    'consumer_id' => Consumer::factory()->state([
                        'company_id' => $this->user->company_id,
                        'first_name' => $agreedSettlement = 'filter_of_agreed_settlement_pending_payment',
                        'status' => ConsumerStatus::PAYMENT_ACCEPTED->value,
                        'payment_setup' => false,
                        'offer_accepted' => true,
                    ]),
                    'negotiation_type' => NegotiationType::PIF,
                ],
                [
                    'consumer_id' => Consumer::factory()->state([
                        'company_id' => $this->user->company_id,
                        'first_name' => $agreedPaymentPlan = 'filter_of_agreed_payment_plan_pending_payment',
                        'status' => ConsumerStatus::PAYMENT_ACCEPTED->value,
                        'payment_setup' => false,
                        'offer_accepted' => true,
                    ]),
                    'negotiation_type' => NegotiationType::INSTALLMENT,
                ]
            )
            ->create(['company_id' => $this->user->company_id]);

        Livewire::test(ListPage::class)
            ->set('company', $this->user->company_id)
            ->set('status', 'agreed_settlement_pending_payment')
            ->assertViewHas('consumers')
            ->assertSee($agreedSettlement)
            ->assertDontSee($agreedPaymentPlan);
    }

    #[Test]
    public function it_can_filter_by_status_of_agreed_payment_plan_pending_payment(): void
    {
        $consumerNegotiations = ConsumerNegotiation::factory()
            ->forEachSequence(
                [
                    'consumer_id' => Consumer::factory()->state([
                        'company_id' => $this->user->company_id,
                        'first_name' => $agreedSettlement = 'filter_of_agreed_settlement_pending_payment',
                        'status' => ConsumerStatus::PAYMENT_ACCEPTED->value,
                        'payment_setup' => false,
                        'offer_accepted' => true,
                    ]),
                    'negotiation_type' => NegotiationType::PIF,
                ],
                [
                    'consumer_id' => Consumer::factory()->state([
                        'company_id' => $this->user->company_id,
                        'first_name' => $agreedPaymentPlan = 'filter_of_agreed_payment_plan_pending_payment',
                        'status' => ConsumerStatus::PAYMENT_ACCEPTED->value,
                        'payment_setup' => false,
                        'offer_accepted' => true,
                    ]),
                    'negotiation_type' => NegotiationType::INSTALLMENT,
                ]
            )
            ->create(['company_id' => $this->user->company_id]);

        Livewire::test(ListPage::class)
            ->set('company', $this->user->company_id)
            ->set('status', 'agreed_payment_plan_pending_payment')
            ->assertViewHas('consumers')
            ->assertDontSee($agreedSettlement)
            ->assertSee($agreedPaymentPlan);
    }

    #[Test]
    public function it_can_export_consumers(): void
    {
        Storage::fake();

        Consumer::factory()->create([
            'status' => ConsumerStatus::JOINED->value,
            'company_id' => $this->user->company_id,
        ]);

        Livewire::test(ListPage::class)
            ->call('export')
            ->assertFileDownloaded()
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_account_number(string $direction): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $this->user->assignRole($role);

        $createdConsumers = Consumer::factory(8)
            ->sequence(fn (Sequence $sequence) => ['member_account_number' => range(1, 8)[$sequence->index]])
            ->for($this->company)
            ->create([
                'status' => ConsumerStatus::UPLOADED,
                'subclient_id' => null,
            ]);

        Livewire::withQueryParams(['direction' => $direction === 'ASC'])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'master_account_number')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $createdConsumers->first()->is($consumers->getCollection()->first())
                    : $createdConsumers->last()->is($consumers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_company_name(string $direction): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $this->user->assignRole($role);

        $createdConsumers = Consumer::factory(22)
            ->sequence(fn (Sequence $sequence) => [
                'company_id' => Company::factory()
                    ->state([
                        'company_name' => range('A', 'Z')[$sequence->index],
                    ]),
            ])
            ->create(['status' => ConsumerStatus::UPLOADED]);

        Livewire::withQueryParams([
            'sort' => 'company_name',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'company_name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $createdConsumers->first()->is($consumers->getCollection()->first())
                    : $createdConsumers->last()->is($consumers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_name(string $direction): void
    {
        $createdConsumers = Consumer::factory(3)
            ->sequence(
                ['first_name' => '', 'last_name' => 'Anderson'],
                ['first_name' => null, 'last_name' => 'Bachan'],
                ['first_name' => 'Caleb', 'last_name' => 'Porzio'],
            )
            ->for($this->company)
            ->create(['status' => ConsumerStatus::UPLOADED]);

        Livewire::test(ListPage::class)
            ->assertOk()
            ->set('sortCol', 'name')
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
    public function it_can_order_by_status(string $direction): void
    {
        $createdConsumers = Consumer::factory(3)
            ->sequence(
                ['status' => ConsumerStatus::UPLOADED],
                ['status' => ConsumerStatus::JOINED],
                ['status' => ConsumerStatus::SETTLED],
            )
            ->for($this->company)
            ->create();

        Livewire::test(ListPage::class)
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
    public function it_can_order_by_account_name(string $direction): void
    {
        $createdConsumers = Consumer::factory(10)
            ->sequence(fn (Sequence $sequence) => [
                'original_account_name' => range('A', 'Z')[$sequence->index + 2],
            ])
            ->for($this->company)
            ->create(['status' => ConsumerStatus::UPLOADED]);

        Livewire::test(ListPage::class)
            ->assertOk()
            ->set('sortCol', 'account_name')
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
    public function it_can_order_by_subclient_name(string $direction): void
    {
        $createdConsumers = Consumer::factory(10)
            ->sequence(fn (Sequence $sequence) => [
                'subclient_name' => range('A', 'Z')[$sequence->index + 2],
            ])
            ->for($this->company)
            ->create(['status' => ConsumerStatus::UPLOADED]);

        Livewire::test(ListPage::class)
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
    public function it_can_order_by_placement_date(string $direction): void
    {
        $createdConsumers = Consumer::factory(10)
            ->sequence(fn (Sequence $sequence) => [
                'placement_date' => today()->subDays($sequence->index + 2),
            ])
            ->for($this->company)
            ->create(['status' => ConsumerStatus::UPLOADED]);

        Livewire::test(ListPage::class)
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
    public function it_can_order_by_placement_account_status(string $direction): void
    {
        $createdConsumers = Consumer::factory()
            ->forEachSequence(
                ['status' => ConsumerStatus::NOT_PAYING],
                ['status' => ConsumerStatus::JOINED],
                ['status' => ConsumerStatus::DEACTIVATED],
                ['status' => ConsumerStatus::UPLOADED],
            )
            ->for($this->company)
            ->create();

        Livewire::test(ListPage::class)
            ->assertOk()
            ->set('sortCol', 'account_status')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $createdConsumers->first()->is($consumers->getCollection()->first())
                    : $createdConsumers->last()->is($consumers->getCollection()->first())
            );
    }

    #[Test]
    public function it_can_allow_search_by_consumer_first_name_and_last_name(): void
    {
        Consumer::factory()
            ->forEachSequence(
                ['first_name' => 'John', 'last_name' => 'Doe'],
                ['first_name' => 'Jane', 'last_name' => 'Doe'],
                ['first_name' => 'John', 'last_name' => 'Smith'],
            )
            ->for($this->company)
            ->create(['status' => ConsumerStatus::UPLOADED]);

        Livewire::test(ListPage::class)
            ->assertOk()
            ->tap(function () {
                Session::put('search', 'John');
            })
            ->set('search', 'John')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $consumers->getCollection()->count() === 2
            )
            ->tap(function () {
                Session::put('search', 'Doe');
            })
            ->set('search', 'Doe')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $consumers->getCollection()->count() === 2
            )
            ->tap(function () {
                Session::put('search', 'Smith');
            })
            ->set('search', 'Smith')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $consumers->getCollection()->count() === 1
            );
    }

    #[Test]
    public function it_can_search_by_consumer_member_account_number(): void
    {
        Consumer::factory()
            ->forEachSequence(
                ['member_account_number' => '123456'],
                ['member_account_number' => '654321'],
                ['member_account_number' => '123456'],
            )
            ->for($this->company)
            ->create(['status' => ConsumerStatus::UPLOADED]);

        Livewire::test(ListPage::class)
            ->assertOk()
            ->tap(function () {
                Session::put('search', '123456');
            })
            ->set('search', '123456')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $consumers->getCollection()->count() === 2
            )
            ->tap(function () {
                Session::put('search', '654321');
            })
            ->set('search', '654321')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $consumers->getCollection()->count() === 1
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

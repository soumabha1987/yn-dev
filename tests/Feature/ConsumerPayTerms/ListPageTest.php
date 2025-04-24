<?php

declare(strict_types=1);

namespace Tests\Feature\ConsumerPayTerms;

use App\Enums\CompanyMembershipStatus;
use App\Enums\CompanyStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\Role as EnumsRole;
use App\Livewire\Creditor\ConsumerPayTerms\ListPage;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ListPageTest extends TestCase
{
    protected User $user;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create([
            'status' => CompanyStatus::ACTIVE,
            'current_step' => CreditorCurrentStep::COMPLETED,
        ]);

        $this->user = User::factory()->for($this->company)->create(['subclient_id' => null]);

        $role = Role::query()->create(['name' => EnumsRole::CREDITOR]);
        $this->user->assignRole($role);

        $this->actingAs($this->user);
    }

    #[Test]
    public function it_can_render_livewire_component(): void
    {
        CompanyMembership::factory()
            ->for($this->company)
            ->create([
                'status' => CompanyMembershipStatus::ACTIVE,
                'current_plan_end' => now()->addMonth(),
            ]);

        $this->withoutVite()
            ->get(route('creditor.consumer-pay-terms'))
            ->assertSeeLivewire(ListPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_view_page_with_data(): void
    {
        $consumer = Consumer::factory()->for($this->company)->create();

        Livewire::test(ListPage::class)
            ->assertViewIs('livewire.creditor.consumer-pay-terms.list-page')
            ->assertViewHas('consumers', fn (LengthAwarePaginator $consumers) => $consumer->is($consumers->getCollection()->first()))
            ->assertSee($consumer->member_account_number ?? 'N/A')
            ->assertSee(str($consumer->first_name . ' ' . $consumer->last_name)->title())
            ->assertSee($consumer->subclient_name ? str($consumer->subclient_name . '/' . $consumer->subclient_account_number)->title() : 'N/A')
            ->assertSee(Number::currency((float) $consumer->total_balance ?? 0))
            ->assertSee($consumer->pif_discount_percent ? Number::percentage($consumer->pif_discount_percent, 2) : 'N/A')
            ->assertSee($consumer->pay_setup_discount_percent ? Number::percentage($consumer->pay_setup_discount_percent, 2) : 'N/A')
            ->assertSee($consumer->max_days_first_pay ?? 'N/A')
            ->assertOk();
    }

    #[Test]
    public function it_can_search_by_account_number(): void
    {
        $createdConsumers = Consumer::factory()
            ->forEachSequence(
                ['member_account_number' => '12345'],
                ['member_account_number' => '67890']
            )
            ->for($this->company)
            ->create();

        Livewire::withQueryParams(['search' => '123'])
            ->test(ListPage::class)
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $consumers->getCollection()->contains($createdConsumers->first())
                    && $consumers->getCollection()->doesntContain($createdConsumers->last())
            )
            ->assertOk();
    }

    #[Test]
    public function it_can_search_by_consumer_name(): void
    {
        $createdConsumers = Consumer::factory()
            ->forEachSequence(
                ['first_name' => 'jom'],
                ['first_name' => 'tom']
            )
            ->for($this->company)
            ->create(['last_name' => 'test']);

        Livewire::withQueryParams(['search' => 'tom'])
            ->test(ListPage::class)
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $consumers->getCollection()->contains($createdConsumers->last())
                    && $consumers->getCollection()->doesntContain($createdConsumers->first())
            )
            ->assertOk();
    }

    #[Test]
    public function it_can_display_consumers_offer_fields_nullable(): void
    {
        $createdConsumers = Consumer::factory()
            ->forEachSequence(
                [
                    'pif_discount_percent' => null,
                    'pay_setup_discount_percent' => null,
                    'min_monthly_pay_percent' => null,
                    'max_days_first_pay' => null,
                ],
                [
                    'pif_discount_percent' => null,
                    'pay_setup_discount_percent' => null,
                    'min_monthly_pay_percent' => null,
                    'max_days_first_pay' => fake()->numberBetween(1, 1000),
                ]
            )
            ->for($this->company)
            ->create();

        Livewire::test(ListPage::class)
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $consumers->getCollection()->contains($createdConsumers->last())
                    && $consumers->getCollection()->doesntContain($createdConsumers->first())
            )
            ->assertOk();
    }

    #[Test]
    public function it_can_export_consumers(): void
    {
        Storage::fake();

        Consumer::factory()->for($this->company)->create();

        Livewire::test(ListPage::class)
            ->call('export')
            ->assertFileDownloaded()
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_member_account_number(string $direction): void
    {
        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'member_account_number' => $sequence->index * 10,
            ])
            ->for($this->company)
            ->create();

        Livewire::test(ListPage::class)
            ->assertOk()
            ->set('sortCol', 'member-account-number')
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
    public function it_can_sort_by_consumer_name(string $direction): void
    {
        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'first_name' => range('A', 'Z')[$sequence->index + 2],
            ])
            ->for($this->company)
            ->create();

        Livewire::test(ListPage::class)
            ->assertOk()
            ->set('sortCol', 'consumer-name')
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
    public function it_can_sort_by_subclient_name(string $direction): void
    {
        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'subclient_name' => range('A', 'Z')[$sequence->index + 5],
            ])
            ->for($this->company)
            ->create();

        Livewire::test(ListPage::class)
            ->assertOk()
            ->set('sortCol', 'sub-name')
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
    public function it_can_sort_by_current_balance(string $direction): void
    {
        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'total_balance' => $sequence->index * 10,
            ])
            ->for($this->company)
            ->create();

        Livewire::test(ListPage::class)
            ->assertOk()
            ->set('sortCol', 'current-balance')
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
    public function it_can_sort_by_settlement_offer(string $direction): void
    {
        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'pif_discount_percent' => $sequence->index * 3,
            ])
            ->for($this->company)
            ->create();

        Livewire::test(ListPage::class)
            ->assertOk()
            ->set('sortCol', 'settlement-offer')
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
    public function it_can_sort_by_plan_balance_offer(string $direction): void
    {
        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'pay_setup_discount_percent' => $sequence->index * 3,
            ])
            ->for($this->company)
            ->create();

        Livewire::test(ListPage::class)
            ->assertOk()
            ->set('sortCol', 'plan-balance-offer')
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
    public function it_can_sort_by_min_monthly_payment(string $direction): void
    {
        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'total_balance' => $sequence->index * 10,
                'pay_setup_discount_percent' => $sequence->index * 3,
                'min_monthly_pay_percent' => $sequence->index * 2,
            ])
            ->for($this->company)
            ->create();

        Livewire::test(ListPage::class)
            ->assertOk()
            ->set('sortCol', 'min-monthly-payment')
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
    public function it_can_sort_by_max_days_first_pay(string $direction): void
    {
        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'max_days_first_pay' => $sequence->index * 5,
            ])
            ->for($this->company)
            ->create();

        Livewire::test(ListPage::class)
            ->assertOk()
            ->set('sortCol', 'max-days-first-pay')
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

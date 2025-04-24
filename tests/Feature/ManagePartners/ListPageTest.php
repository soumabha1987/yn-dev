<?php

declare(strict_types=1);

namespace Tests\Feature\ManagePartners;

use App\Enums\MembershipTransactionStatus;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\ManagePartners\ListPage;
use App\Models\Company;
use App\Models\Partner;
use App\Models\YnTransaction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class ListPageTest extends AuthTestCase
{
    #[Test]
    public function access_forbidden_for_non_super_admin_user(): void
    {
        $this->get(route('super-admin.manage-partners'))
            ->assertDontSeeLivewire(ListPage::class)
            ->assertForbidden();
    }

    #[Test]
    public function it_can_render_livewire_component(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        $this->user->company->update(['is_super_admin_company' => true]);

        $this->get(route('super-admin.manage-partners'))
            ->assertSeeLivewire(ListPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_view_page_with_no_data(): void
    {
        Livewire::test(ListPage::class)
            ->assertViewIs('livewire.creditor.manage-partners.list-page')
            ->assertViewHas('partners', fn (LengthAwarePaginator $partners): bool => $partners->getCollection()->isEmpty())
            ->assertSee(__('No result found'))
            ->assertDontSee(__('Export'))
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_view_page_with_data(): void
    {
        Company::factory()
            ->for($partner = Partner::factory()->create())
            ->create();

        Livewire::test(ListPage::class)
            ->assertViewIs('livewire.creditor.manage-partners.list-page')
            ->assertViewHas('partners', fn (LengthAwarePaginator $partners): bool => $partners->getCollection()->contains($partner))
            ->assertSee(Str($partner->name)->title())
            ->assertSee(Str($partner->contact_first_name)->title())
            ->assertSee(Str($partner->contact_last_name ?? 'N/A')->title())
            ->assertSee($partner->contact_email)
            ->assertSee($partner->contact_phone)
            ->assertSee(Number::percentage($partner->revenue_share ?? 0, 2))
            ->assertSee(Number::format($partner->creditors_quota ?? 0))
            ->assertSee(Number::percentage(100 / $partner->creditors_quota, 2))
            ->assertSee(__('Create'))
            ->assertSee(__('Export'))
            ->assertSee(__('Copy Partnership Link'))
            ->assertSee(__('List of Current Members'))
            ->assertSee(__('Edit'))
            ->assertDontSee(__('No result found'))
            ->assertOk();
    }

    #[Test]
    public function it_can_render_export_partners_list(): void
    {
        Storage::fake();

        Company::factory()->for(Partner::factory()->create())->create();

        Livewire::test(ListPage::class)
            ->call('export')
            ->assertOk()
            ->assertFileDownloaded();
    }

    #[Test]
    public function it_can_render_export_members(): void
    {
        Storage::fake();

        Company::factory()->for($partner = Partner::factory()->create())->create();

        Livewire::test(ListPage::class)
            ->call('exportMembers', $partner->id)
            ->assertOk()
            ->assertFileDownloaded();
    }

    #[Test]
    public function it_can_render_export_members_but_there_have_no_member(): void
    {
        Storage::fake();

        /** @var Partner $partner */
        $partner = Partner::factory()->create();

        Livewire::test(ListPage::class)
            ->call('exportMembers', $partner->id)
            ->assertOk()
            ->assertDispatched('close-menu-item')
            ->assertNoFileDownloaded();

        Notification::assertNotified(__('Sorry, this partner currently has no members.'));
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_partner_name(string $direction): void
    {
        $createdPartners = Partner::factory(10)
            ->sequence(fn (Sequence $sequence): array => ['name' => range('A', 'Z')[$sequence->index]])
            ->create();

        Livewire::withQueryParams([
            'sort' => 'company-name',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertSet('sortCol', 'company-name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'partners',
                fn (LengthAwarePaginator $partners): bool => $direction === 'ASC'
                    ? $createdPartners->first()->is($partners->getCollection()->first())
                    : $createdPartners->last()->is($partners->getCollection()->first())
            )
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_contact_first_name(string $direction): void
    {
        $createdPartners = Partner::factory(10)
            ->sequence(fn (Sequence $sequence): array => ['contact_first_name' => range('A', 'Z')[$sequence->index]])
            ->create();

        Livewire::withQueryParams([
            'sort' => 'contact-first-name',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertSet('sortCol', 'contact-first-name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'partners',
                fn (LengthAwarePaginator $partners): bool => $direction === 'ASC'
                    ? $createdPartners->first()->is($partners->getCollection()->first())
                    : $createdPartners->last()->is($partners->getCollection()->first())
            )
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_contact_last_name(string $direction): void
    {
        $createdPartners = Partner::factory(10)
            ->sequence(fn (Sequence $sequence): array => ['contact_last_name' => range('A', 'Z')[$sequence->index]])
            ->create();

        Livewire::withQueryParams([
            'sort' => 'contact-last-name',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertSet('sortCol', 'contact-last-name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'partners',
                fn (LengthAwarePaginator $partners): bool => $direction === 'ASC'
                    ? $createdPartners->first()->is($partners->getCollection()->first())
                    : $createdPartners->last()->is($partners->getCollection()->first())
            )
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_contact_email(string $direction): void
    {
        $createdPartners = Partner::factory(10)
            ->sequence(fn (Sequence $sequence): array => ['contact_email' => 'test_' . $sequence->index . '@test.com'])
            ->create();

        Livewire::withQueryParams([
            'sort' => 'contact-email',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertSet('sortCol', 'contact-email')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'partners',
                fn (LengthAwarePaginator $partners): bool => $direction === 'ASC'
                    ? $createdPartners->first()->is($partners->getCollection()->first())
                    : $createdPartners->last()->is($partners->getCollection()->first())
            )
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_contact_phone(string $direction): void
    {
        $createdPartners = Partner::factory(5)
            ->sequence(fn (Sequence $sequence): array => ['contact_phone' => '900909009' . $sequence->index])
            ->create();

        Livewire::withQueryParams([
            'sort' => 'contact-phone',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertSet('sortCol', 'contact-phone')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'partners',
                fn (LengthAwarePaginator $partners): bool => $direction === 'ASC'
                    ? $createdPartners->first()->is($partners->getCollection()->first())
                    : $createdPartners->last()->is($partners->getCollection()->first())
            )
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_revenue_share(string $direction): void
    {
        $createdPartners = Partner::factory(5)
            ->sequence(fn (Sequence $sequence): array => ['revenue_share' => $sequence->index * 10])
            ->create();

        Livewire::withQueryParams([
            'sort' => 'revenue-share',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertSet('sortCol', 'revenue-share')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'partners',
                fn (LengthAwarePaginator $partners): bool => $direction === 'ASC'
                    ? $createdPartners->first()->is($partners->getCollection()->first())
                    : $createdPartners->last()->is($partners->getCollection()->first())
            )
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_creditors_quota(string $direction): void
    {
        $createdPartners = Partner::factory(10)
            ->sequence(fn (Sequence $sequence): array => ['creditors_quota' => $sequence->index * 20])
            ->create();

        Livewire::withQueryParams([
            'sort' => 'creditors-quota',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertSet('sortCol', 'creditors-quota')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'partners',
                fn (LengthAwarePaginator $partners): bool => $direction === 'ASC'
                    ? $createdPartners->first()->is($partners->getCollection()->first())
                    : $createdPartners->last()->is($partners->getCollection()->first())
            )
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_companies_count(string $direction): void
    {
        Company::factory(10)->for($firstPartner = Partner::factory()->create())->create();
        Company::factory(20)->for($secondPartner = Partner::factory()->create())->create();

        Livewire::withQueryParams([
            'sort' => 'joined',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertSet('sortCol', 'joined')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'partners',
                fn (LengthAwarePaginator $partners): bool => $direction === 'ASC'
                    ? $firstPartner->is($partners->getCollection()->first())
                    : $secondPartner->is($partners->getCollection()->first())
            )
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_quota_percentage(string $direction): void
    {
        Company::factory(10)
            ->for($firstPartner = Partner::factory()->create(['creditors_quota' => 100]))
            ->create();

        Company::factory(20)
            ->for($secondPartner = Partner::factory()->create(['creditors_quota' => 100]))
            ->create();

        Livewire::withQueryParams([
            'sort' => 'quota-percentage',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertSet('sortCol', 'quota-percentage')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'partners',
                fn (LengthAwarePaginator $partners): bool => $direction === 'ASC'
                    ? $firstPartner->is($partners->getCollection()->first())
                    : $secondPartner->is($partners->getCollection()->first())
            )
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_yn_total_revenue(string $direction): void
    {
        YnTransaction::factory()
            ->forEachSequence(
                [
                    'company_id' => Company::factory()->for($firstPartner = Partner::factory()->create())->create(),
                    'amount' => 200,
                ],
                [
                    'company_id' => Company::factory()->for(Partner::factory()->create())->create(),
                    'amount' => 300,
                ],
                [
                    'company_id' => Company::factory()->for($thirdPartner = Partner::factory()->create())->create(),
                    'amount' => 400,
                ],
            )
            ->create([
                'status' => MembershipTransactionStatus::SUCCESS,
                'created_at' => today()->subDays(fake()->numberBetween(1, 1000)),
            ]);

        Livewire::withQueryParams([
            'sort' => 'yn-total-revenue',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertSet('sortCol', 'yn-total-revenue')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'partners',
                fn (LengthAwarePaginator $partners): bool => $direction === 'ASC'
                    ? $firstPartner->is($partners->getCollection()->first())
                    : $thirdPartner->is($partners->getCollection()->first())
            )
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_partner_revenue_share(string $direction): void
    {
        YnTransaction::factory()
            ->forEachSequence(
                [
                    'company_id' => Company::factory()->for($firstPartner = Partner::factory()->create())->create(),
                    'partner_revenue_share' => 200,
                ],
                [
                    'company_id' => Company::factory()->for(Partner::factory()->create())->create(),
                    'partner_revenue_share' => 300,
                ],
                [
                    'company_id' => Company::factory()->for($thirdPartner = Partner::factory()->create())->create(),
                    'partner_revenue_share' => 400,
                ],
            )
            ->create([
                'status' => MembershipTransactionStatus::SUCCESS,
                'created_at' => today()->subDays(fake()->numberBetween(1, 1000)),
            ]);

        Livewire::withQueryParams([
            'sort' => 'partner-total-revenue',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertSet('sortCol', 'partner-total-revenue')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'partners',
                fn (LengthAwarePaginator $partners): bool => $direction === 'ASC'
                    ? $firstPartner->is($partners->getCollection()->first())
                    : $thirdPartner->is($partners->getCollection()->first())
            )
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_yn_net_revenue(string $direction): void
    {
        YnTransaction::factory()
            ->forEachSequence(
                [
                    'company_id' => Company::factory()->for(Partner::factory()->create())->create(),
                    'partner_revenue_share' => 20,
                    'amount' => 500,
                ],
                [
                    'company_id' => Company::factory()->for($secondPartner = Partner::factory()->create())->create(),
                    'partner_revenue_share' => 10,
                    'amount' => 500,
                ],
                [
                    'company_id' => Company::factory()->for($thirdPartner = Partner::factory()->create())->create(),
                    'partner_revenue_share' => 40,
                    'amount' => 500,
                ],
            )
            ->create([
                'status' => MembershipTransactionStatus::SUCCESS,
                'created_at' => today()->subDays(fake()->numberBetween(1, 1000)),
            ]);

        Livewire::withQueryParams([
            'sort' => 'partner-total-revenue',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertSet('sortCol', 'partner-total-revenue')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'partners',
                fn (LengthAwarePaginator $partners): bool => $direction === 'ASC'
                    ? $secondPartner->is($partners->getCollection()->first())
                    : $thirdPartner->is($partners->getCollection()->first())
            )
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

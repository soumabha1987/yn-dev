<?php

declare(strict_types=1);

namespace Tests\Feature\ManageCreditors;

use App\Enums\CompanyBusinessCategory;
use App\Enums\CompanyStatus;
use App\Enums\ConsumerStatus;
use App\Enums\Role as EnumRole;
use App\Exports\ConsumersExport;
use App\Livewire\Creditor\ManageCreditors\ListPage;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\ScheduleExport;
use App\Models\Subclient;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class ListPageTest extends AuthTestCase
{
    #[Test]
    public function access_forbidden_for_non_super_admin_user(): void
    {
        $this->get(route('super-admin.manage-creditors'))
            ->assertDontSeeLivewire(ListPage::class)
            ->assertForbidden();
    }

    #[Test]
    public function it_can_render_companies_list(): void
    {
        $this->superAdminAuthenticationAccess();

        Livewire::test(ListPage::class)
            ->set('perPage', 10)
            ->assertSee(['Company_1', 'Company_2', 'Company_9'])
            ->assertDontSee(['Company_15'])
            ->assertSuccessful();
    }

    #[Test]
    public function it_can_not_render_deleted_company(): void
    {
        $this->superAdminAuthenticationAccess();

        Livewire::test(ListPage::class)
            ->set('onlyTrashed', false)
            ->assertSee(['Company_1', 'Company_2'])
            ->assertDontSee('Company_11');
    }

    #[Test]
    public function it_can_display_only_deleted_company_if_we_are_passing_flag(): void
    {
        $this->superAdminAuthenticationAccess();

        Livewire::test(ListPage::class)
            ->set('onlyTrashed', true)
            ->assertDontSee(['Company_2'])
            ->assertSee(['Company_12']);
    }

    #[Test]
    public function it_can_delete_company(): void
    {
        $this->superAdminAuthenticationAccess();

        $company = Company::firstWhere('company_name', 'Company_9');

        Consumer::factory()->for($company)
            ->create(['status' => ConsumerStatus::JOINED]);

        $this->assertDatabaseHas(Consumer::class, [
            'company_id' => $company->id,
            'status' => ConsumerStatus::JOINED,
        ]);

        ScheduleExport::factory(5)->for($company)->create();

        $this->assertDatabaseCount(ScheduleExport::class, 5);

        $childOfEmail = 'child_of@test.com';

        $childOfUser = User::factory()
            ->for($this->user, 'parent')
            ->create([
                'company_id' => $company->id,
                'email' => $childOfEmail,
            ]);

        $childOfChildEmail = 'child_of_child@test.com';

        $childOfChildOfUser = User::factory()
            ->for($childOfUser, 'parent')
            ->create([
                'company_id' => $company->id,
                'email' => $childOfChildEmail,
            ]);

        Livewire::test(ListPage::class)
            ->call('delete', $company->id)
            ->assertDontSee('Company_11')
            ->assertDispatched('membership-inquiry-count-updated');

        $this->assertNotEquals($childOfUser->refresh()->email, $childOfEmail);
        $this->assertNotEquals($childOfChildOfUser->refresh()->email, $childOfChildEmail);

        $this->assertSoftDeleted($company);
        $this->assertSoftDeleted($childOfUser);
        $this->assertSoftDeleted($childOfChildOfUser);

        $this->assertDatabaseCount(ScheduleExport::class, 0);
        $this->assertDatabaseHas(Consumer::class, [
            'company_id' => $company->id,
            'status' => ConsumerStatus::DEACTIVATED,
        ]);
    }

    #[Test]
    public function it_can_not_delete_company_for_consumer_status_payment_accepted(): void
    {
        $this->superAdminAuthenticationAccess();

        $company = Company::firstWhere('company_name', 'Company_9');

        Consumer::factory()->for($company)->create(['status' => ConsumerStatus::PAYMENT_ACCEPTED->value]);

        Livewire::test(ListPage::class)
            ->call('delete', $company->id)
            ->assertOk()
            ->assertSee('Company_9')
            ->assertDispatched('close-confirmation-box')
            ->assertNotDispatched('membership-inquiry-count-updated')
            ->assertDontSee('Company_11');

        $this->assertNotSoftDeleted($company);
    }

    #[Test]
    public function it_can_export_consumers_as_csv(): void
    {
        $this->travelTo(now()->addMinutes(10));

        $this->superAdminAuthenticationAccess();

        Excel::fake();

        $company = Company::firstWhere('company_name', 'company_6');

        $consumer = Consumer::factory()->create([
            'company_id' => $company->id,
            'status' => ConsumerStatus::JOINED->value,
        ]);

        Livewire::test(ListPage::class)
            ->call('exportConsumers', $company->id)
            ->assertDispatched('close-notification-' . $company->id);

        $fileName = Str::of($company->company_name)->slug('_')
            ->append('_', now()->format('Y_m_d_H_i_s'), '.')
            ->append('csv')
            ->toString();

        Excel::assertDownloaded($fileName, fn (ConsumersExport $consumersExport): bool => $consumersExport->collection()->contains('account_number', $consumer->member_account_number));
    }

    #[Test]
    public function it_can_login_without_spatie_login_link(): void
    {
        DB::table('companies')->update(['is_super_admin_company' => true]);

        $company = Company::factory()->create(['is_super_admin_company' => true]);

        $subclient = Subclient::factory()->for($company)->create();

        $user = User::factory()->for($subclient)->create();
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $user->assignRole($role);

        Livewire::test(ListPage::class)
            ->call('login', $user->company)
            ->assertDispatched('close-menu-item')
            ->assertOk();

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function can_not_seen_in_if_user_have_no_creditor_role(): void
    {
        DB::table('companies')->update(['is_super_admin_company' => true]);

        $company = Company::factory()->create(['is_super_admin_company' => true]);

        $subclient = Subclient::factory()->for($company)->create();

        $user = User::factory()->for($subclient)->create();

        Livewire::test(ListPage::class)
            ->assertViewHas('companies', fn (LengthAwarePaginator $companies) => $companies->isEmpty())
            ->assertOk();

        $this->assertAuthenticatedAs($this->user);
    }

    #[Test]
    public function can_not_logged_in_if_company_is_banned(): void
    {
        DB::table('companies')->update(['is_super_admin_company' => true]);

        $company = Company::factory()->create(['is_super_admin_company' => true]);

        $subclient = Subclient::factory()->for($company)->create();

        $user = User::factory()->for($subclient)->create();
        $role = Role::query()->create(['name' => EnumRole::CREDITOR->value]);
        $user->assignRole($role);

        $user->company()->update(['is_deactivate' => true]);

        Livewire::test(ListPage::class)
            ->call('login', $user->company)
            ->assertDispatched('close-menu-item');

        $this->assertAuthenticatedAs($this->user);
    }

    #[Test]
    public function it_can_toggle_company_block_unblock(): void
    {
        DB::table('companies')->update(['is_super_admin_company' => true]);

        $company = Company::factory()->create(['is_super_admin_company' => true]);

        $subclient = Subclient::factory()->for($company)->create();

        $user = User::factory()->for($subclient)->create();

        $creditorRole = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $user->assignRole($creditorRole);

        $user->company()->update(['is_deactivate' => $status = fake()->boolean()]);

        Livewire::test(ListPage::class)
            ->call('switchBlockStatus', $user->company)
            ->assertDispatched('close-menu-item')
            ->assertOk();

        $user->company->refresh();
        $this->assertEquals(! $status, $user->company->is_deactivate);
    }

    #[Test]
    public function it_can_display_company_which_doesnt_have_user_when_only_trashed_flag_is_passed(): void
    {
        $company = Company::factory()->create(['deleted_at' => now()]);

        Livewire::test(ListPage::class)
            ->set('onlyTrashed', true)
            ->assertOk()
            ->assertViewIs('livewire.creditor.manage-creditors.list-page')
            ->assertViewHas('hasH2HUser', false)
            ->assertViewHas('companies', fn (LengthAwarePaginator $companies) => $company->is($companies->getCollection()->first()));
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_render_order_by_created_at(string $direction): void
    {
        $this->company->update(['is_super_admin_company' => true]);
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $creditorRole = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        $users = User::factory(13)->create([
            'subclient_id' => null,
            'parent_id' => null,
            'blocked_at' => null,
            'blocker_user_id' => null,
        ]);

        $users->each(function (User $user, int $key) use ($creditorRole): void {
            $user->company->forceFill(['created_at' => now()->addDays($key)]);
            $user->company->save();

            $user->assignRole($creditorRole);
        });

        Livewire::withQueryParams(['direction' => $direction === 'ASC'])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'created_on')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'companies',
                fn (LengthAwarePaginator $companies) => $direction === 'ASC'
                    ? $users->first()->company->is($companies->first())
                    : $users->last()->company->is($companies->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_render_order_by_company_name(string $direction): void
    {
        $this->company->update(['is_super_admin_company' => true]);
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $creditorRole = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        $users = User::factory(15)->create([
            'subclient_id' => null,
            'parent_id' => null,
            'blocked_at' => null,
            'blocker_user_id' => null,
        ]);

        $users->each(function (User $user, int $index) use ($creditorRole): void {
            $user->company->update(['company_name' => range('A', 'Z')[$index]]);

            $user->assignRole($creditorRole);
        });

        Livewire::withQueryParams([
            'sort' => 'name',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'companies',
                fn (LengthAwarePaginator $companies) => $direction === 'ASC'
                    ? $users->first()->company->is($companies->first())
                    : $users->last()->company->is($companies->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_render_order_by_company_owner_full_name(string $direction): void
    {
        $this->company->update(['is_super_admin_company' => true]);
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $creditorRole = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        $users = User::factory(15)->create([
            'subclient_id' => null,
            'parent_id' => null,
            'blocked_at' => null,
            'blocker_user_id' => null,
        ]);

        $users->each(function (User $user, int $index) use ($creditorRole): void {
            $user->company->update(['owner_full_name' => range('A', 'Z')[$index]]);

            $user->assignRole($creditorRole);
        });

        Livewire::withQueryParams([
            'sort' => 'owner_full_name',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'owner_full_name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'companies',
                fn (LengthAwarePaginator $companies) => $direction === 'ASC'
                    ? $users->first()->company->is($companies->first())
                    : $users->last()->company->is($companies->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_render_order_by_company_category(string $direction): void
    {
        $this->company->update(['is_super_admin_company' => true]);
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $creditorRole = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        $users = User::factory(3)
            ->create([
                'subclient_id' => null,
                'parent_id' => null,
                'blocked_at' => null,
                'blocker_user_id' => null,
            ]);

        $businessCategories = [
            CompanyBusinessCategory::AR_OUT_SOURCE,
            CompanyBusinessCategory::AUTO_REPOSSESSION,
            CompanyBusinessCategory::THIRD_PARTY_DEBT_SERVICE,
        ];

        $users->each(function (User $user, int $index) use ($creditorRole, $businessCategories): void {
            $user->company->update(['business_category' => $businessCategories[$index]]);
            $user->assignRole($creditorRole);
        });

        Livewire::withQueryParams([
            'sort' => 'category',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'category')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'companies',
                fn (LengthAwarePaginator $companies) => $direction === 'ASC'
                    ? $users->first()->company->is($companies->first())
                    : $users->last()->company->is($companies->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_status(string $direction): void
    {
        $this->company->update(['is_super_admin_company' => true]);
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $creditorRole = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        $users = User::factory(3)->create([
            'subclient_id' => null,
            'parent_id' => null,
            'blocked_at' => null,
            'blocker_user_id' => null,
        ]);

        $statuses = [CompanyStatus::ACTIVE, CompanyStatus::REJECTED, CompanyStatus::SUBMITTED];

        $users->each(function (User $user, int $index) use ($creditorRole, $statuses): void {
            $user->company->update(['status' => $statuses[$index]]);

            $user->assignRole($creditorRole);
        });

        Livewire::withQueryParams([
            'sort' => $status = fake()->randomElement(['status', 'merchant_status']),
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', $status)
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'companies',
                fn (LengthAwarePaginator $companies) => $direction === 'ASC'
                    ? $users->first()->company->is($companies->first())
                    : $users->last()->company->is($companies->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_consumers_count(string $direction): void
    {
        $this->company->update(['is_super_admin_company' => true]);
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $creditorRole = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        $users = User::factory(10)
            ->create([
                'subclient_id' => null,
                'parent_id' => null,
                'blocked_at' => null,
                'blocker_user_id' => null,
            ])
            ->each(function (User $user, int $index) use ($creditorRole): void {
                Consumer::factory($index + 5)
                    ->create([
                        'status' => ConsumerStatus::JOINED,
                        'company_id' => $user->company_id,
                    ]);

                $user->assignRole($creditorRole);
            });

        Livewire::withQueryParams([
            'sort' => 'consumers_count',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertSee(Number::format($users->first()->company->consumers->count()))
            ->assertOk()
            ->assertSet('sortCol', 'consumers_count')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'companies',
                fn (LengthAwarePaginator $companies) => $direction === 'ASC'
                    ? $users->first()->company->is($companies->getCollection()->first())
                    : $users->last()->company->is($companies->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_total_balance(string $direction): void
    {
        $this->company->update(['is_super_admin_company' => true]);
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $creditorRole = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        $users = User::factory(10)
            ->create([
                'subclient_id' => null,
                'parent_id' => null,
                'blocked_at' => null,
                'blocker_user_id' => null,
            ])
            ->each(function (User $user, int $index) use ($creditorRole): void {
                Consumer::factory($index + 5)
                    ->create([
                        'status' => ConsumerStatus::JOINED,
                        'current_balance' => $index + 10,
                        'company_id' => $user->company_id,
                    ]);

                $user->assignRole($creditorRole);
            });

        Livewire::withQueryParams([
            'sort' => 'total_balance',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'total_balance')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'companies',
                fn (LengthAwarePaginator $companies) => $direction === 'ASC'
                    ? $users->first()->company->is($companies->getCollection()->first())
                    : $users->last()->company->is($companies->getCollection()->first())
            );
    }

    public static function sortDirection(): array
    {
        return [
            ['ASC'],
            ['DESC'],
        ];
    }

    private function superAdminAuthenticationAccess(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        DB::table('companies')->update(['is_super_admin_company' => true]);

        $company = Company::factory()->create(['is_super_admin_company' => true]);

        $subclient = Subclient::factory()->for($company)->create();

        $creditorRole = Role::query()->create(['name' => EnumRole::CREDITOR]);

        User::factory(15)
            ->for($subclient)
            ->create()
            ->each(function (User $user, $key) use ($creditorRole) {
                $user->company->update([
                    'company_name' => 'company_' . $key + 1,
                    'deleted_at' => $key > 9 ? now() : null,
                ]);

                $user->assignRole($creditorRole);
            });
    }
}

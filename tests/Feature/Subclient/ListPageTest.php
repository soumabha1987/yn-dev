<?php

declare(strict_types=1);

namespace Tests\Feature\Subclient;

use App\Enums\CompanyStatus;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\Subclient\ListPage;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\MembershipPaymentProfile;
use App\Models\Subclient;
use App\Rules\AddressSingleSpace;
use App\Rules\SingleSpace;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class ListPageTest extends AuthTestCase
{
    #[Test]
    public function it_can_render_livewire_component(): void
    {
        $this->get(route('manage-subclients'))
            ->assertSeeLivewire(ListPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_super_admin_view_with_data(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        $this->user->subclient->company()->update(['status' => CompanyStatus::ACTIVE]);
        $this->user->company()->update(['status' => CompanyStatus::ACTIVE]);

        $createdSubclients = Subclient::factory()
            ->for(Company::factory()->state(['status' => CompanyStatus::ACTIVE]))
            ->forEachSequence(
                ['subclient_name' => 'subclient_1'],
                ['subclient_name' => 'subclient_2']
            )
            ->create()
            ->each(fn (Subclient $subclient): MembershipPaymentProfile => MembershipPaymentProfile::factory()->create(['company_id' => $subclient->company_id]));

        MembershipPaymentProfile::factory()
            ->forEachSequence(
                ['company_id' => $this->user->company_id],
                ['company_id' => $this->user->subclient->company_id],
            )
            ->create();

        Livewire::test(ListPage::class)
            ->assertViewIs('livewire.creditor.subclient.list-page')
            ->assertViewHas('subclients', fn (LengthAwarePaginator $subclients): bool => $subclients->total() === 3)
            ->assertViewHas('companies', fn (array $companies): bool => $companies === [
                $this->user->subclient->company_id => $this->user->subclient->company->company_name,
                $this->user->company_id => $this->user->company->company_name,
                ...$createdSubclients->pluck('company.company_name', 'company_id')->all(),
            ])
            ->assertSet('dialogOpen', false)
            ->assertDontSee(__('Pay Offers'))
            ->assertSee(__('Enter up to 160 characters'))
            ->assertSee(__('Company'))
            ->assertSee('subclient_1')
            ->assertSee('subclient_2')
            ->assertOk();
    }

    #[Test]
    public function it_can_render_creditor_view_with_data(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        Subclient::factory()
            ->for($this->user->company)
            ->forEachSequence(
                ['subclient_name' => 'subclient_1'],
                ['subclient_name' => 'subclient_2']
            )
            ->create();

        Livewire::test(ListPage::class)
            ->assertViewIs('livewire.creditor.subclient.list-page')
            ->assertViewHas('subclients', fn (LengthAwarePaginator $subclients) => $subclients->isNotEmpty() && $subclients->getCollection()->count() === 3)
            ->assertViewHas('companies', null)
            ->assertSet('dialogOpen', false)
            ->assertDontSee(__('Company'))
            ->assertOk();
    }

    #[Test]
    public function it_can_render_deleted_company_subclient_display(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        $subclient = Subclient::factory()
            ->for(Company::factory()->create(['deleted_at' => now()]))
            ->create();

        Livewire::test(ListPage::class)
            ->assertViewIs('livewire.creditor.subclient.list-page')
            ->assertViewHas('subclients', fn (LengthAwarePaginator $subclients) => $subclients->getCollection()->doesntContain($subclient))
            ->assertOk();
    }

    #[Test]
    public function it_can_render_search_by_subclient_name(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        Subclient::factory()
            ->for($this->user->company)
            ->forEachSequence(
                ['subclient_name' => 'subclient_1'],
                ['subclient_name' => 'subclient_2']
            )
            ->create();

        Livewire::withUrlParams(['search' => 'subclient_2'])
            ->test(ListPage::class)
            ->assertViewHas('subclients', fn (LengthAwarePaginator $subclients): bool => $subclients->count() === 1 && ($subclients->first()->subclient_name === 'subclient_2'))
            ->assertSee('subclient_2')
            ->assertOk();
    }

    #[Test]
    public function it_can_call_delete(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        $subclient = Subclient::factory()->for($this->user->company)->create();

        $consumer = Consumer::factory()->for($subclient)->create();

        Livewire::test(ListPage::class)
            ->call('delete', $subclient->id)
            ->assertOk();

        $this->assertSoftDeleted($subclient);

        $this->assertNull($consumer->refresh()->subclient_id);
    }

    #[Test]
    #[DataProvider('requestRole')]
    public function it_can_required_validation_on_subclient_create(string $requestRole): void
    {
        $role = Role::query()->create(['name' => $requestRole]);

        $this->user->assignRole($role);

        $assertion = [
            'form.subclient_name' => ['required'],
            'form.unique_identification_number' => ['required'],
        ];

        if ($this->user->hasRole(EnumRole::SUPERADMIN->value)) {
            $assertion['form.company_id'] = ['required'];
        }

        Livewire::test(ListPage::class)
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasErrors($assertion);
    }

    #[Test]
    #[DataProvider('requestRole')]
    public function it_can_non_required_validation_on_subclient_create(string $requestRole): void
    {
        $role = Role::query()->create(['name' => $requestRole]);

        $this->user->assignRole($role);

        $assertion = [
            'form.subclient_name' => ['max:255'],
            'form.unique_identification_number' => ['max:255'],
        ];

        if ($this->user->hasRole(EnumRole::SUPERADMIN)) {
            $assertion['form.company_id'] = ['exists'];
        }

        Livewire::test(ListPage::class)
            ->set('form.company_id', $requestRole === EnumRole::SUPERADMIN->value ? fake()->word() : '')
            ->set('form.subclient_name', Str::repeat('A', 300))
            ->set('form.unique_identification_number', Str::repeat('A', 300))
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasErrors($assertion);
    }

    #[Test]
    #[DataProvider('requestRole')]
    public function it_can_unique_validation_on_subclient_create(string $requestRole): void
    {
        $role = Role::query()->create(['name' => $requestRole]);

        $this->user->assignRole($role);

        $subclient = Subclient::factory()
            ->create([
                'subclient_name' => Str::limit(fake()->firstName(), 25),
                'unique_identification_number' => 'sss-001',
            ]);

        if ($this->user->hasRole(EnumRole::CREDITOR)) {
            $subclient->update(['company_id' => $this->user->company_id]);
        }

        $assertion = [
            'form.subclient_name' => ['unique'],
            'form.unique_identification_number' => ['unique'],
        ];

        Livewire::test(ListPage::class)
            ->set('form.company_id', $subclient->company_id)
            ->set('form.subclient_name', $subclient->subclient_name)
            ->set('form.unique_identification_number', $subclient->unique_identification_number)
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasErrors($assertion);
    }

    #[Test]
    #[DataProvider('requestValidation')]
    public function it_can_render_single_space_and_special_character_validation_of_subclient_create(array $requestFields, array $requestErrors): void
    {
        $this->user->assignRole(Role::query()->create(['name' => EnumRole::CREDITOR]));

        Livewire::test(ListPage::class)
            ->set($requestFields)
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasErrors($requestErrors);
    }

    #[Test]
    #[DataProvider('requestRole')]
    public function it_can_no_validation_error_on_subclient_create(string $requestRole): void
    {
        $role = Role::query()->create(['name' => $requestRole]);

        $this->user->assignRole($role);

        $company = Company::factory()->create(['status' => CompanyStatus::ACTIVE]);

        Livewire::test(ListPage::class)
            ->set('form.subclient_name', 'check subclient')
            ->set('form.company_id', $this->user->hasRole(EnumRole::SUPERADMIN->value) ? $company->id : '')
            ->set('form.unique_identification_number', 'ssd-001')
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors();
    }

    #[Test]
    #[DataProvider('requestRole')]
    public function it_can_create_on_subclient(string $requestRole): void
    {
        $role = Role::query()->create(['name' => $requestRole]);

        $this->user->assignRole($role);

        $company = Company::factory()->create(['status' => CompanyStatus::ACTIVE]);

        Livewire::test(ListPage::class)
            ->set('form.subclient_name', $name = 'Test subclient')
            ->set('form.company_id', $this->user->hasRole(EnumRole::SUPERADMIN->value) ? $company->id : '')
            ->set('form.unique_identification_number', $uniqueId = 'SS-001')
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertDatabaseHas(Subclient::class, [
            'company_id' => $this->user->hasRole(EnumRole::SUPERADMIN->value) ? $company->id : $this->user->company_id,
            'subclient_name' => $name,
            'unique_identification_number' => $uniqueId,
        ]);
    }

    #[Test]
    public function it_can_create_on_subclient_when_role_creditor_in_completed_setup_wizard_steps(): void
    {
        Cache::put('remaining-wizard-required-steps-' . $this->user->id, 1);

        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        Company::factory()->create(['status' => CompanyStatus::ACTIVE]);

        Livewire::test(ListPage::class)
            ->set('form.subclient_name', $name = 'Test subclient')
            ->set('form.company_id', '')
            ->set('form.unique_identification_number', $uniqueId = 'SS-001')
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertDatabaseHas(Subclient::class, [
            'company_id' => $this->user->company_id,
            'subclient_name' => $name,
            'unique_identification_number' => $uniqueId,
        ]);
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_name(string $direction): void
    {
        $this->user->update(['subclient_id' => null]);
        $this->subclient->forceDelete();

        $createdSubclients = Subclient::factory(14)
            ->sequence(fn (Sequence $sequence) => ['subclient_name' => range('A', 'Z')[$sequence->index]])
            ->create();

        Livewire::withQueryParams([
            'sort' => 'name',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'subclients',
                fn (LengthAwarePaginator $subclients) => $direction === 'ASC'
                    ? $createdSubclients->first()->is($subclients->getCollection()->first())
                    : $createdSubclients->last()->is($subclients->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_created_on(string $direction): void
    {
        $this->user->update(['subclient_id' => null]);
        $this->subclient->forceDelete();

        $createdSubclients = Subclient::factory(14)->create();

        $createdSubclients->each(function (Subclient $subclient, int $index): void {
            $subclient->forceFill(['created_at' => now()->addDays($index)]);
            $subclient->save();
        });

        Livewire::withQueryParams(['sort' => 'created_on', 'direction' => $direction === 'ASC'])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'created_on')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'subclients',
                fn (LengthAwarePaginator $subclients) => $direction === 'ASC'
                    ? $createdSubclients->first()->is($subclients->getCollection()->first())
                    : $createdSubclients->last()->is($subclients->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_company_name(string $direction): void
    {
        $this->user->update(['subclient_id' => null]);
        $this->subclient->forceDelete();

        $createdSubclients = Subclient::factory(14)->create();

        $createdSubclients->each(function (Subclient $subclient, int $index): void {
            $subclient->company()->update(['company_name' => range('A', 'Z')[$index]]);
        });

        Livewire::withQueryParams([
            'sort' => 'company_name',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'company_name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'subclients',
                fn (LengthAwarePaginator $subclients) => $direction === 'ASC'
                    ? $createdSubclients->first()->is($subclients->getCollection()->first())
                    : $createdSubclients->last()->is($subclients->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_unique_identification_number(string $direction): void
    {
        $this->user->update(['subclient_id' => null]);
        $this->subclient->forceDelete();

        $createdSubclients = Subclient::factory(14)
            ->sequence(fn (Sequence $sequence) => ['unique_identification_number' => range('A', 'Z')[$sequence->index]])
            ->create();

        Livewire::withQueryParams([
            'sort' => 'unique_identification_number',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'unique_identification_number')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'subclients',
                fn (LengthAwarePaginator $subclients) => $direction === 'ASC'
                    ? $createdSubclients->first()->is($subclients->getCollection()->first())
                    : $createdSubclients->last()->is($subclients->getCollection()->first())
            );
    }

    public static function sortDirection(): array
    {
        return [
            ['ASC'],
            ['DESC'],
        ];
    }

    public static function requestRole(): array
    {
        return [
            [EnumRole::SUPERADMIN->value],
            [EnumRole::CREDITOR->value],
        ];
    }

    public static function requestValidation()
    {
        return [
            [
                [
                    'form.subclient_name' => 'a',
                    'form.unique_identification_number' => 'a',
                ],
                [
                    'form.subclient_name' => ['min:2'],
                ],
            ],
            [
                [
                    'form.subclient_name' => Str::repeat('a', 161),
                    'form.unique_identification_number' => Str::repeat('a', 161),
                ],
                [
                    'form.subclient_name' => ['max:160'],
                    'form.unique_identification_number' => ['max:160'],
                ],
            ],
            [
                [
                    'form.subclient_name' => 'Test     name',
                    'form.unique_identification_number' => 'Test    identification number',
                ],
                [
                    'form.subclient_name' => [AddressSingleSpace::class],
                    'form.unique_identification_number' => [SingleSpace::class],
                ],
            ],
        ];
    }
}

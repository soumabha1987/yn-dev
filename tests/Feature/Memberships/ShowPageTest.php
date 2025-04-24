<?php

declare(strict_types=1);

namespace Tests\Feature\Memberships;

use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\Memberships\ShowPage;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShowPageTest extends TestCase
{
    #[Test]
    public function it_can_render_livewire_component(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $membership = Membership::factory()->create();

        $this->withoutVite()
            ->actingAs($user)
            ->get(route('super-admin.memberships.show', ['membership' => $membership]))
            ->assertSeeLivewire(ShowPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view_and_data(): void
    {
        $companyMembership = CompanyMembership::factory()->create();

        Livewire::test(ShowPage::class, ['membership' => $companyMembership->membership])
            ->assertViewIs('livewire.creditor.memberships.show-page')
            ->assertViewHas('companyMemberships', fn (LengthAwarePaginator $companyMemberships) => $companyMembership->is($companyMemberships->getCollection()->first()))
            ->assertSee($companyMembership->company->company_name)
            ->assertSee($companyMembership->e_letter_fee)
            ->assertSee($companyMembership->current_plan_start->formatWithTimezone())
            ->assertSee($companyMembership->status->name)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_with_deleted_company_and_render_data(): void
    {
        $company = Company::factory()->create(['deleted_at' => now()]);

        $companyMembership = CompanyMembership::factory()
            ->for($company)
            ->create();

        Livewire::test(ShowPage::class, ['membership' => $companyMembership->membership])
            ->assertViewIs('livewire.creditor.memberships.show-page')
            ->assertViewHas('companyMemberships', fn (LengthAwarePaginator $companyMemberships) => $companyMembership->is($companyMemberships->getCollection()->first()))
            ->assertSee($company->company_name)
            ->assertSee($companyMembership->e_letter_fee)
            ->assertSee($companyMembership->current_plan_start->formatWithTimezone())
            ->assertSee($companyMembership->status->name)
            ->assertOk();
    }
}

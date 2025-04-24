<?php

declare(strict_types=1);

namespace Tests\Feature\Memberships;

use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\Memberships\ListPage;
use App\Models\Company;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ListPageTest extends TestCase
{
    #[Test]
    public function it_can_render_membership_list_page_component(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $user = User::factory()->create();

        $user->assignRole($role);

        $this->withoutVite()
            ->actingAs($user)
            ->get(route('super-admin.memberships'))
            ->assertSeeLivewire(ListPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        $createdMemberships = Membership::factory(3)
            ->create();

        $this->assertTrue($createdMemberships->first()->position === 0);

        Livewire::test(ListPage::class)
            ->assertViewIs('livewire.creditor.memberships.list-page')
            ->assertViewHas('memberships', fn (Collection $memberships) => $memberships->first()->id === $createdMemberships->first()->id)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_memberships_and_display_the_table(): void
    {
        $membership = Membership::factory()->create(['price' => 100]);

        Livewire::test(ListPage::class)
            ->assertSee($membership->name)
            ->assertSee(Number::currency((float) $membership->price))
            ->assertSee($membership->frequency->name)
            ->assertSee(Number::currency((float) $membership->e_letter_fee))
            ->assertSee(Number::percentage((float) $membership->fee, 2))
            ->assertSee($membership->status ? __('Shown') : __('Hidden'))
            ->assertOk();
    }

    #[Test]
    public function it_can_render_deleted_company_membership_not_display(): void
    {
        $membership = Membership::factory()
            ->for(Company::factory()->create(['deleted_at' => now()]))
            ->create(['price' => 100]);

        Livewire::test(ListPage::class)
            ->assertViewHas('memberships', fn (Collection $memberships) => $memberships->doesntContain($membership))
            ->assertDontSee(Number::currency((float) $membership->price))
            ->assertDontSee($membership->frequency->name)
            ->assertDontSee(Number::currency((float) $membership->e_letter_fee))
            ->assertDontSee(Number::percentage((float) $membership->fee, 2))
            ->assertOk();
    }

    #[Test]
    public function it_can_delete_membership_if_company_membership_count_is_zero(): void
    {
        $membership = Membership::factory()->create();

        Livewire::test(ListPage::class)
            ->call('delete', 0, $membership)
            ->assertDispatched('close-confirmation-box')
            ->assertOk();

        $this->assertEquals(999999, $membership->refresh()->position);

        $this->assertSoftDeleted($membership);
    }

    #[Test]
    public function it_can_update_status_of_current_membership(): void
    {
        $membership = Membership::factory()->create();

        $oldStatus = $membership->status;

        Livewire::test(ListPage::class)
            ->call('toggleActiveInactive', $membership)
            ->assertOk();

        $this->assertEquals($membership->refresh()->status, ! $oldStatus);
    }
}

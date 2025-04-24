<?php

declare(strict_types=1);

namespace Tests\Feature\Memberships;

use App\Enums\MembershipFeatures;
use App\Enums\MembershipFrequency;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\Memberships\EditPage;
use App\Models\Membership;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EditPageTest extends TestCase
{
    #[Test]
    public function it_can_render_the_livewire_component(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $membership = Membership::factory()->create();

        $this->withoutVite()
            ->actingAs($user)
            ->get(route('super-admin.memberships.edit', ['membership' => $membership]))
            ->assertSeeLivewire(EditPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        $membership = Membership::factory()->create();

        Livewire::test(EditPage::class, ['membership' => $membership])
            ->assertSet('form.membership', $membership)
            ->assertSet('form.name', $membership->name)
            ->assertSet('form.price', $membership->price)
            ->assertSet('form.e_letter_fee', $membership->e_letter_fee)
            ->assertSet('form.fee', $membership->fee)
            ->assertSet('form.upload_accounts_limit', $membership->upload_accounts_limit)
            ->assertSet('form.frequency', $membership->frequency->value)
            ->assertSet('form.description', $membership->description)
            ->assertViewIs('livewire.creditor.memberships.edit-page')
            ->assertOk();
    }

    #[Test]
    public function it_can_allow_current_record_as_it_is_not_throw_unique_validation(): void
    {
        $membership = Membership::factory()->create(['price' => '83.44']);

        Livewire::test(EditPage::class, ['membership' => $membership])
            ->set('form.name', $membership->name)
            ->set('form.price', $membership->price)
            ->set('form.fee', $membership->fee)
            ->set('form.e_letter_fee', $membership->e_letter_fee)
            ->set('form.upload_accounts_limit', $membership->upload_accounts_limit)
            ->set('form.frequency', $membership->frequency)
            ->set('form.description', $membership->description)
            ->set('form.status', $membership->status)
            ->set('form.features', fake()->randomElements(MembershipFeatures::names(), 2))
            ->call('update')
            ->assertHasNoErrors()
            ->assertRedirect(route('super-admin.memberships'))
            ->assertOk();
    }

    #[Test]
    public function it_can_update_membership(): void
    {
        $membership = Membership::factory()->create();

        Livewire::test(EditPage::class, ['membership' => $membership])
            ->set('form.name', 'Test membership')
            ->set('form.price', 1000)
            ->set('form.fee', 99)
            ->set('form.e_letter_fee', 10)
            ->set('form.upload_accounts_limit', 1100)
            ->set('form.frequency', MembershipFrequency::MONTHLY)
            ->set('form.description', 'This is description')
            ->set('form.features', fake()->randomElements(MembershipFeatures::names(), 2))
            ->call('update')
            ->assertHasNoErrors()
            ->assertRedirect(route('super-admin.memberships'))
            ->assertOk();

        $membership->refresh();

        $this->assertEquals($membership->name, 'Test membership');
        $this->assertEquals($membership->price, 1000);
        $this->assertEquals($membership->fee, 99);
        $this->assertEquals($membership->e_letter_fee, 10);
        $this->assertEquals($membership->upload_accounts_limit, 1100);
        $this->assertEquals($membership->frequency, MembershipFrequency::MONTHLY);
        $this->assertEquals($membership->description, 'This is description');
        $this->assertArrayHasKey('features', $membership->meta_data);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\ManagePartners;

use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\ManagePartners\EditPage;
use App\Models\Partner;
use Filament\Notifications\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class EditPageTest extends AuthTestCase
{
    #[Test]
    public function access_forbidden_for_non_super_admin_user(): void
    {
        $partner = Partner::factory()->create();

        $this->get(route('super-admin.manage-partners.edit', $partner->id))
            ->assertDontSeeLivewire(EditPage::class)
            ->assertForbidden();
    }

    #[Test]
    public function it_can_render_livewire_component(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        $this->user->company->update(['is_super_admin_company' => true]);

        $partner = Partner::factory()->create();

        $this->get(route('super-admin.manage-partners.edit', $partner->id))
            ->assertSeeLivewire(EditPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_view_page(): void
    {
        $partner = Partner::factory()->create();

        Livewire::test(EditPage::class, ['partner' => $partner])
            ->assertViewIs('livewire.creditor.manage-partners.edit-page')
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_view_page_with_data(): void
    {
        $partner = Partner::factory()->create();

        Livewire::test(EditPage::class, ['partner' => $partner])
            ->assertSet('form.name', $partner->name)
            ->assertSet('form.contact_first_name', $partner->contact_first_name)
            ->assertSet('form.contact_last_name', $partner->contact_last_name)
            ->assertSet('form.contact_email', $partner->contact_email)
            ->assertSet('form.contact_phone', $partner->contact_phone)
            ->assertSet('form.revenue_share', $partner->revenue_share)
            ->assertSet('form.creditors_quota', $partner->creditors_quota)
            ->assertSet('form.report_emails', implode(',', $partner->report_emails))
            ->assertOk();
    }

    #[Test]
    public function it_can_render_update_prater(): void
    {
        $partner = Partner::factory()->create();

        Livewire::test(EditPage::class, ['partner' => $partner])
            ->set('form.name', $name = fake()->name())
            ->set('form.contact_first_name', $fname = fake()->firstName())
            ->set('form.contact_last_name', $lname = fake()->lastName())
            ->set('form.contact_email', $email = fake()->safeEmail())
            ->set('form.contact_phone', $phone = '9008990067')
            ->set('form.revenue_share', $revenueShare = fake()->numberBetween(0, 99))
            ->set('form.creditors_quota', $quota = fake()->numberBetween(100, 999999))
            ->set('form.report_emails', 'a@a.com,b@b.com,c@c.com,d@d.com,e@e.com')
            ->call('update')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertRedirect(route('super-admin.manage-partners'));

        Notification::assertNotified(__('Partner link updated.'));

        $this->assertDatabaseHas(Partner::class, [
            'id' => $partner->id,
            'name' => $name,
            'contact_first_name' => $fname,
            'contact_last_name' => $lname,
            'contact_email' => $email,
            'contact_phone' => $phone,
            'revenue_share' => $revenueShare,
            'creditors_quota' => $quota,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Users;

use App\Enums\CompanyMembershipStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\Users\EditPage;
use App\Mail\UserInvitationMail;
use App\Models\CompanyMembership;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EditPageTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_can_render_livewire_component(): void
    {
        $this->user->company()->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);

        CompanyMembership::factory()
            ->for($this->user->company)
            ->create([
                'status' => CompanyMembershipStatus::ACTIVE,
                'current_plan_end' => now()->addMonth(),
            ]);

        $this->withoutVite()
            ->actingAs($this->user)
            ->get(route('creditor.users.edit', $this->user->id))
            ->assertSeeLivewire(EditPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_access_forbidden_for_non_master_user(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->company()->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        $this->user->assignRole($role);

        $user = User::factory()->for($this->user->company)->create(['parent_id' => $this->user->id]);

        CompanyMembership::factory()
            ->for($this->user->company)
            ->create([
                'status' => CompanyMembershipStatus::ACTIVE,
                'current_plan_end' => now()->addMonth(),
            ]);

        $this->withoutVite()
            ->actingAs($user)
            ->get(route('creditor.users.edit', $this->user->id))
            ->assertDontSeeLivewire(EditPage::class)
            ->assertForbidden();
    }

    #[Test]
    public function it_can_render_livewire_view_page(): void
    {
        $nameParts = explode(' ', $this->user->name);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        Livewire::actingAs($this->user)
            ->test(EditPage::class, ['user' => $this->user])
            ->assertViewIs('livewire.creditor.users.edit-page')
            ->assertSet('form.first_name', $firstName)
            ->assertSet('form.last_name', $lastName)
            ->assertSet('form.email', $this->user->email)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_update_login_user(): void
    {
        Mail::fake();

        Livewire::actingAs($this->user)
            ->test(EditPage::class, ['user' => $this->user])
            ->set('form.first_name', 'UpdateFirstName')
            ->set('form.last_name', 'UpdateLastName')
            ->set('form.email', $email = 'update@email.com')
            ->call('update')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertRedirectToRoute('creditor.users');

        Notification::assertNotified(__('User account updated.'));

        Mail::assertNothingQueued();

        $this->assertEquals('UpdateFirstName UpdateLastName', $this->user->refresh()->name);
        $this->assertEquals($email, $this->user->email);
    }

    #[Test]
    public function it_can_render_update_name_for_user(): void
    {
        Mail::fake();

        $user = User::factory()->create(['company_id' => $this->user->company_id]);

        Livewire::actingAs($this->user)
            ->test(EditPage::class, ['user' => $user])
            ->set('form.first_name', 'UpdateFirstName')
            ->set('form.last_name', 'UpdateLastName')
            ->call('update')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertRedirectToRoute('creditor.users');

        Mail::assertNothingQueued();

        Notification::assertNotified(__('User account updated.'));

        $this->assertEquals('UpdateFirstName UpdateLastName', $user->refresh()->name);
    }

    #[Test]
    public function it_can_render_update_email_for_user(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'company_id' => $this->user->company_id,
            'email_verified_at' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(EditPage::class, ['user' => $user])
            ->set('form.first_name', 'UpdateFirstName')
            ->set('form.last_name', 'UpdateLastName')
            ->set('form.email', $email = 'update@email.com')
            ->call('update')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertRedirectToRoute('creditor.users');

        Notification::assertNotified(__('User account updated.'));

        $this->assertEquals('UpdateFirstName UpdateLastName', $user->refresh()->name);
        $this->assertEquals($email, $user->email);

        $this->assertNull($user->email_verified_at);

        Mail::assertQueued(
            UserInvitationMail::class,
            fn (UserInvitationMail $mail) => $mail->assertTo($email)
        );
    }
}

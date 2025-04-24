<?php

declare(strict_types=1);

namespace Tests\Feature\Users;

use App\Enums\CompanyMembershipStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\Users\ListPage;
use App\Mail\UserBlockedMail;
use App\Mail\UserInvitationMail;
use App\Models\CompanyMembership;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ListPageTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Model::preventLazyLoading();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function can_render_livewire_component_when_visit_the_route(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->company()->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        $this->user->assignRole($role);

        CompanyMembership::factory()
            ->for($this->user->company)
            ->create([
                'status' => CompanyMembershipStatus::ACTIVE,
                'current_plan_end' => now()->addMonth(),
            ]);

        $this->withoutVite()
            ->actingAs($this->user)
            ->get(route('creditor.users'))
            ->assertSeeLivewire(ListPage::class)
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
            ->get(route('creditor.users'))
            ->assertDontSeeLivewire(ListPage::class)
            ->assertForbidden();
    }

    #[Test]
    public function it_can_render_the_livewire_component_with_correct_view(): void
    {
        $blockedUser = User::factory()->create([
            'company_id' => $this->user->company_id,
            'blocked_at' => now(),
            'blocker_user_id' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ListPage::class)
            ->assertViewIs('livewire.creditor.users.list-page')
            ->assertViewHas('users', fn (Collection $users) => $this->user->is($users->first()) && $users->doesntContain($blockedUser))
            ->assertOk();
    }

    #[Test]
    public function it_can_call_delete(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'company_id' => $this->user->company_id,
            'blocked_at' => null,
            'blocker_user_id' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(ListPage::class)
            ->call('delete', $user)
            ->assertDispatched('close-confirmation-box')
            ->assertOk();

        Notification::assertNotified(__('User deleted.'));

        Mail::assertQueued(
            UserBlockedMail::class,
            fn (UserBlockedMail $mail) => $mail
                ->assertTo($user->email)
                ->assertHasSubject('Notification: Your Account Has Been Deleted')
        );

        $this->assertNotNull($user->refresh()->blocked_at);
        $this->assertEquals($this->user->id, $user->blocker_user_id);
        $this->assertNotSoftDeleted($user);
    }

    #[Test]
    public function it_can_not_delete_self_users(): void
    {
        Livewire::actingAs($this->user)
            ->test(ListPage::class)
            ->call('delete', $this->user)
            ->assertDispatched('close-confirmation-box')
            ->assertOk();

        Notification::assertNotified(__('Sorry, your permissions do not allow you to delete this User.'));

        $this->assertNotSoftDeleted($this->user);
    }

    #[Test]
    public function it_can_also_not_delete_other_users_of_the_company(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($this->user)
            ->test(ListPage::class)
            ->call('delete', $user)
            ->assertDispatched('close-confirmation-box')
            ->assertOk();

        Notification::assertNotified(__('Sorry, your permissions do not allow you to delete this User.'));

        $this->assertNotSoftDeleted($user);
    }

    #[Test]
    public function it_can_call_resend_for_send_again_invitation_link(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'company_id' => $this->user->company_id,
            'email_verified_at' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(ListPage::class)
            ->call('resend', $user)
            ->assertOk();

        Notification::assertNotified(__('User invitation link sent.'));

        Mail::assertQueued(
            UserInvitationMail::class,
            fn (UserInvitationMail $mail) => $mail
                ->assertTo($user->email)
                ->assertHasSubject('Welcome to YouNegotiate - Set Up Your Password')
        );
    }

    #[Test]
    public function it_can_call_resend_for_active_user(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'company_id' => $this->user->company_id,
            'email_verified_at' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(ListPage::class)
            ->call('resend', $user)
            ->assertOk();

        Notification::assertNotified(__('This email belongs to an existing active user.'));

        Mail::assertNothingQueued();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_first_name(string $direction): void
    {
        $createdUsers = User::factory()
            ->forEachSequence(
                ['name' => 'A user'],
                ['name' => 'D user'],
                ['name' => 'E user'],
            )
            ->create(['company_id' => $this->user->company_id]);

        $this->user->update(['name' => 'B user']);

        Livewire::withQueryParams([
            'sort' => 'first-name',
            'direction' => $direction === 'ASC',
        ])
            ->actingAs($this->user)
            ->test(ListPage::class)
            ->assertSet('sortCol', 'first-name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'users',
                fn (Collection $users) => $direction === 'ASC'
                    ? $createdUsers->first()->is($users->first())
                    : $createdUsers->last()->is($users->first())
            )
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_last_name(string $direction): void
    {
        $createdUsers = User::factory()
            ->forEachSequence(
                ['name' => 'User A'],
                ['name' => 'User D'],
                ['name' => 'User E'],
            )
            ->create(['company_id' => $this->user->company_id]);

        $this->user->update(['name' => 'User B']);

        Livewire::withQueryParams([
            'sort' => 'last-name',
            'direction' => $direction === 'ASC',
        ])
            ->actingAs($this->user)
            ->test(ListPage::class)
            ->assertSet('sortCol', 'last-name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'users',
                fn (Collection $users) => $direction === 'ASC'
                    ? $createdUsers->first()->is($users->first())
                    : $createdUsers->last()->is($users->first())
            )
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_email(string $direction): void
    {
        $createdUsers = User::factory()
            ->forEachSequence(
                ['email' => 'a_user@test.com'],
                ['email' => 'd_user@test.com'],
                ['email' => 'e_user@test.com'],
            )
            ->create(['company_id' => $this->user->company_id]);

        $this->user->update(['email' => 'b_user@test.com']);

        Livewire::withQueryParams([
            'sort' => 'email',
            'direction' => $direction === 'ASC',
        ])
            ->actingAs($this->user)
            ->test(ListPage::class)
            ->assertSet('sortCol', 'email')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'users',
                fn (Collection $users) => $direction === 'ASC'
                    ? $createdUsers->first()->is($users->first())
                    : $createdUsers->last()->is($users->first())
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

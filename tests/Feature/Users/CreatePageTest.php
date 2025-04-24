<?php

declare(strict_types=1);

namespace Tests\Feature\Users;

use App\Enums\CompanyMembershipStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\Users\CreatePage;
use App\Mail\UserInvitationMail;
use App\Models\CompanyMembership;
use App\Models\User;
use App\Rules\NamingRule;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreatePageTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_can_render_livewire_component_when_visit_the_routes(): void
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
            ->get(route('creditor.users.create'))
            ->assertSeeLivewire(CreatePage::class)
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
            ->get(route('creditor.users.create'))
            ->assertDontSeeLivewire(CreatePage::class)
            ->assertForbidden();
    }

    #[Test]
    #[DataProvider('validationRule')]
    public function it_can_throw_required_validation(array $requestData, array $requestErrors): void
    {
        Livewire::actingAs($this->user)
            ->test(CreatePage::class)
            ->set($requestData)
            ->call('create')
            ->assertOk()
            ->assertHasErrors($requestErrors);
    }

    #[Test]
    public function it_can_create_the_user(): void
    {
        Mail::fake();

        Role::query()->create(['name' => EnumRole::CREDITOR]);

        Livewire::actingAs($this->user)
            ->test(CreatePage::class)
            ->set('form.first_name', 'Test')
            ->set('form.last_name', 'name')
            ->set('form.password', 'Livewire@2024')
            ->set('form.email', $email = fake()->email())
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect(route('creditor.users'));

        Notification::assertNotified(__('User created.'));

        $this->assertDatabaseHas(User::class, [
            'name' => 'Test name',
            'email' => $email,
            'parent_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'email_verified_at' => null,
        ]);

        Mail::assertQueued(
            UserInvitationMail::class,
            fn (UserInvitationMail $mail) => $mail
                ->assertTo($email)
                ->assertHasSubject('Welcome to YouNegotiate - Set Up Your Password')
        );
    }

    #[Test]
    public function it_can_not_create_for_creditor_exists_three_user(): void
    {
        User::factory(2)->create(['company_id' => $this->user->company_id]);

        Livewire::actingAs($this->user)
            ->test(CreatePage::class)
            ->assertRedirect(route('creditor.users'));

        Notification::assertNotified(__('On this version, we offer up to 3 Users on each membership account.'));
    }

    public static function validationRule(): array
    {
        return [
            [
                [],
                [
                    'form.first_name' => ['required'],
                    'form.last_name' => ['required'],
                    'form.email' => ['required'],
                    'form.password' => ['required'],
                ],
            ],
            [
                [
                    'form.first_name' => str('a')->repeat(100),
                    'form.last_name' => str('a')->repeat(100),
                    'form.email' => str('a')->repeat(257),
                    'form.password' => str('a')->repeat(100),
                ],
                [
                    'form.first_name' => ['max:25'],
                    'form.last_name' => ['max:25'],
                    'form.email' => ['max:255'],
                    'form.password',
                ],
            ],
            [
                [
                    'form.first_name' => 'a',
                    'form.last_name' => 'a',
                    'form.email' => 'a',
                    'form.password' => 'a',
                ],
                [
                    'form.first_name' => ['min:2'],
                    'form.last_name' => ['min:2'],
                    'form.email' => ['min:2'],
                    'form.password',
                ],
            ],
            [
                [
                    'form.first_name' => 'abcd   efg',
                    'form.last_name' => 'abcd@@@@WWWDDD',
                    'form.email' => 'abcd',
                    'form.password' => 'abcdefg',
                ],
                [
                    'form.first_name' => [NamingRule::class],
                    'form.last_name' => [NamingRule::class],
                    'form.email' => ['email'],
                    'form.password',
                ],
            ],
        ];
    }
}

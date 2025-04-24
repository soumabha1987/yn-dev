<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\CompanyStatus;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\Auth\RegisterPage;
use App\Models\Company;
use App\Models\User;
use App\Notifications\VerifyEmailQueuedNotification;
use App\Providers\RouteServiceProvider;
use App\Rules\NamingRule;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegisterPageTest extends TestCase
{
    #[Test]
    public function it_can_render_the_livewire_component(): void
    {
        $this->withoutVite()
            ->get(route('register'))
            ->assertSeeLivewire(RegisterPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_correct_view_on_livewire_component(): void
    {
        Livewire::test(RegisterPage::class)
            ->assertViewIs('livewire.creditor.auth.register-page')
            ->assertOk();
    }

    #[Test]
    public function it_can_give_required_validation(): void
    {
        Livewire::test(RegisterPage::class)
            ->call('register')
            ->assertSet('resetCaptcha', true)
            ->assertHasErrors(['form.name', 'form.email', 'form.password', 'form.recaptcha', 'form.terms_and_conditions'])
            ->assertOk();
    }

    #[Test]
    #[DataProvider('requestValidation')]
    public function it_can_give_company_name_and_password_validation(array $requestSetData, array $requestErrors): void
    {
        Http::fake(fn () => Http::response(['success' => true]));

        Livewire::test(RegisterPage::class)
            ->set($requestSetData)
            ->set('form.email', 'test@test.com')
            ->set('form.recaptcha', fake()->uuid())
            ->set('form.terms_and_conditions', true)
            ->call('register')
            ->assertSet('resetCaptcha', true)
            ->assertHasErrors($requestErrors)
            ->assertHasNoErrors([
                'form.email',
                'form.recaptcha',
                'form.terms_and_conditions',
            ])
            ->assertOk();
    }

    #[Test]
    public function it_can_company_name_and_password_no_validation_errors(): void
    {
        Http::fake(fn () => Http::response(['success' => true]));

        Livewire::test(RegisterPage::class)
            ->set('form.company_name', 'Laravel test company')
            ->set('form.password', $password = 'Laravel@test#1')
            ->set('form.password_confirmation', $password)
            ->set('form.email', 'test@test.com')
            ->set('form.recaptcha', fake()->uuid())
            ->set('form.terms_and_conditions', true)
            ->call('register')
            ->assertSet('resetCaptcha', true)
            ->assertHasNoErrors([
                'form.company_name',
                'form.password',
                'form.email',
                'form.recaptcha',
                'form.terms_and_conditions',
            ])
            ->assertOk();
    }

    #[Test]
    public function it_can_give_unique_validation(): void
    {
        $user = User::factory()->create();

        Livewire::test(RegisterPage::class)
            ->set('form.name', $user->name)
            ->set('form.email', $user->email)
            ->set('form.password', $password = 'Livewire@2024')
            ->set('form.password_confirmation', $password)
            ->set('form.recaptcha', fake()->uuid())
            ->set('form.terms_and_conditions', true)
            ->call('register')
            ->assertSet('resetCaptcha', true)
            ->assertHasErrors([
                'form.email' => [__('validation.unique', ['attribute' => 'email'])],
            ])
            ->assertHasNoErrors(['form.password'])
            ->assertOk();
    }

    #[Test]
    public function it_can_register_company_and_logged_in_user(): void
    {
        Event::fake();

        Notification::fake();

        Http::fake(fn () => Http::response(['success' => true]));

        Role::query()->create(['name' => EnumRole::CREDITOR]);

        Livewire::test(RegisterPage::class)
            ->set('form.name', $name = 'Laravel testing company')
            ->set('form.email', $email = fake()->email())
            ->set('form.password', $password = 'Livewire@2024')
            ->set('form.password_confirmation', $password)
            ->set('form.recaptcha', fake()->uuid())
            ->set('form.terms_and_conditions', true)
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(RouteServiceProvider::HOME)
            ->assertOk();

        $user = User::first();

        $this->assertDatabaseHas(Role::class, ['name' => EnumRole::CREDITOR]);

        Event::assertDispatched(fn (Registered $event) => $event->user->id === $user->id);

        Notification::assertSentTo([$user], VerifyEmailQueuedNotification::class);

        $company = Company::first();

        $this->assertNull($company->company_name);
        $this->assertEquals($company->owner_full_name, $name);
        $this->assertEquals($company->owner_email, $email);
        $this->assertEquals($company->status, CompanyStatus::CREATED);

        $this->assertEquals($user->name, $name);
        $this->assertEquals($user->email, $email);
        $this->assertEquals($user->company_id, $company->id);

        $this->assertTrue(Hash::check('Livewire@2024', $user->password));

        $this->assertTrue($user->hasRole(EnumRole::CREDITOR));

        $this->assertAuthenticatedAs($user);
    }

    public static function requestValidation(): array
    {
        return [
            [
                [
                    'form.name' => str('a')->repeat(26),
                    'form.password' => $password = 'password@123',
                    'form.password_confirmation' => $password,
                ],
                [
                    'form.name' => ['max:25'],
                    'form.password',
                ],
            ],
            [
                [
                    'form.name' => str('a')->repeat(2),
                    'form.password' => $password = 'PASSWORD@123',
                    'form.password_confirmation' => $password,
                ],
                [
                    'form.name' => ['min:3'],
                    'form.password',
                ],
            ],
            [
                [
                    'form.name' => 'company        test',
                    'form.password' => $password = '1234567890',
                    'form.password_confirmation' => $password,
                ],
                [
                    'form.name' => [NamingRule::class],
                    'form.password',
                ],
            ],
            [
                [
                    'form.name' => 'company@@@$##$%%^%Test',
                    'form.password' => $password = 'Password@1234567890',
                    'form.password_confirmation' => $password,
                ],
                [
                    'form.name' => [NamingRule::class],
                    'form.password',
                ],
            ],
            [
                [
                    'form.name' => '12345678',
                    'form.password' => $password = 'pass',
                    'form.password_confirmation' => $password,
                ],
                [
                    'form.name' => [NamingRule::class],
                    'form.password',
                ],
            ],
            [
                [
                    'form.password' => $password = '@$#$#^$^*^',
                    'form.password_confirmation' => $password,
                ],
                [
                    'form.password',
                ],
            ],
        ];
    }
}

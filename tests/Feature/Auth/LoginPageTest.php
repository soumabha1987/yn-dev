<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\Auth\LoginPage;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LoginPageTest extends TestCase
{
    #[Test]
    public function it_can_render_login_page(): void
    {
        $this->withoutVite()
            ->get(route('login'))
            ->assertSeeLivewire(LoginPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component(): void
    {
        Livewire::test(LoginPage::class)
            ->assertSee(__('Member Sign In'))
            ->assertViewIs('livewire.creditor.auth.login-page')
            ->assertOk();
    }

    #[Test]
    public function it_can_required_fields_validation(): void
    {
        Livewire::test(LoginPage::class)
            ->call('authenticate')
            ->assertHasErrors([
                'form.email' => [__('validation.required', ['attribute' => 'email'])],
                'form.password' => [__('validation.required', ['attribute' => 'password'])],
                'form.recaptcha' => [__('validation.required', ['attribute' => 'recaptcha'])],
            ])
            ->assertSet('resetCaptcha', true)
            ->assertOk();
    }

    #[Test]
    public function it_can_other_non_required_validation(): void
    {
        Http::fake(fn () => Http::response(['success' => true]));

        Livewire::test(LoginPage::class)
            ->set('form.email', fake()->word())
            ->set('form.password', fake()->password())
            ->set('form.recaptcha', 'invalid-recaptcha-token')
            ->call('authenticate')
            ->assertSet('resetCaptcha', true)
            ->assertHasErrors(['form.email'])
            ->assertOk();
    }

    #[Test]
    public function it_can_not_logged_in_using_fake_validation(): void
    {
        Http::fake(fn () => Http::response(['success' => true]));

        Livewire::test(LoginPage::class)
            ->set('form.email', fake()->email())
            ->set('form.password', fake()->password())
            ->set('form.recaptcha', fake()->uuid())
            ->call('authenticate')
            ->assertSet('resetCaptcha', true)
            ->assertHasErrors(['form.email' => [__('auth.failed')]])
            ->assertOk();

        $this->assertGuest();
    }

    #[Test]
    public function it_can_throw_rate_limiting_error_when_you_fire_multiple_requests(): void
    {
        $this->travelTo(now()->addMinutes(10));

        Http::fake(fn () => Http::response(['success' => true]));

        $credentials = [
            'email' => fake()->email(),
            'password' => fake()->password(),
            'recaptcha' => fake()->uuid(),
        ];

        // Using wrong credentials make 5 requests and 6th request has rate limiting error!
        collect(range(1, 5))->each(function () use ($credentials): void {
            Livewire::test(LoginPage::class)
                ->set('form.email', $credentials['email'])
                ->set('form.password', $credentials['password'])
                ->set('form.recaptcha', $credentials['recaptcha'])
                ->call('authenticate')
                ->assertSet('resetCaptcha', true)
                ->assertHasErrors(['form.email' => [__('auth.failed')]]);
        });

        Livewire::test(LoginPage::class)
            ->set('form.email', $credentials['email'])
            ->set('form.password', $credentials['password'])
            ->set('form.recaptcha', $credentials['recaptcha'])
            ->call('authenticate')
            ->assertSet('resetCaptcha', true)
            ->assertHasErrors(['form.email' => __('auth.throttle', [
                'seconds' => 60,
                'minutes' => ceil(60 / 60),
            ])]);
    }

    #[Test]
    public function it_can_login_using_credentials(): void
    {
        Http::fake(fn () => Http::response(['success' => true]));

        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $user = User::factory()->create([
            'password' => 'test',
            'email' => 'test@test.com',
        ]);

        $user->assignRole($role);

        Livewire::test(LoginPage::class)
            ->set('form.email', $user->email)
            ->set('form.password', 'test')
            ->set('form.recaptcha', fake()->uuid())
            ->call('authenticate')
            ->assertSet('resetCaptcha', true)
            ->assertHasNoErrors()
            ->assertRedirectToRoute('home');

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function it_can_not_logged_in_because_user_is_blocked(): void
    {
        Http::fake(fn () => Http::response(['success' => true]));

        $user = User::factory()->create([
            'password' => 'test',
            'email' => 'test@test.com',
            'blocked_at' => now(),
        ]);

        Livewire::test(LoginPage::class)
            ->set('form.email', $user->email)
            ->set('form.password', 'test')
            ->set('form.recaptcha', fake()->uuid())
            ->call('authenticate')
            ->assertSet('resetCaptcha', true)
            ->assertHasErrors(['form.email' => [__('auth.failed')]])
            ->assertOk();

        $this->assertGuest();
    }

    #[Test]
    public function it_can_not_logged_in_because_its_company_is_deactivate(): void
    {
        Http::fake(fn () => Http::response(['success' => true]));

        $company = Company::factory()->create();

        $company->update(['is_deactivate' => true]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'password' => 'test',
            'email' => 'test@test.com',
        ]);

        Livewire::test(LoginPage::class)
            ->set('form.email', $user->email)
            ->set('form.password', 'test')
            ->set('form.recaptcha', fake()->uuid())
            ->call('authenticate')
            ->assertSet('resetCaptcha', true)
            ->assertHasErrors(['form.email' => [__('auth.failed')]])
            ->assertOk();

        $this->assertGuest();
    }

    #[Test]
    public function superadmin_can_logged_in(): void
    {
        Http::fake(fn () => Http::response(['success' => true]));

        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $user = User::factory()->create([
            'password' => 'test',
            'email' => 'test@test.com',
        ]);
        $user->assignRole($role);

        Livewire::test(LoginPage::class)
            ->set('form.email', $user->email)
            ->set('form.password', 'test')
            ->set('form.recaptcha', fake()->uuid())
            ->call('authenticate')
            ->assertSet('resetCaptcha', true)
            ->assertHasNoErrors()
            ->assertRedirectToRoute('home');

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function it_can_forgot_password_to_store_the_email(): void
    {
        Livewire::test(LoginPage::class)
            ->assertOk()
            ->set('form.email', 'test@gmail.com')
            ->call('forgotPassword')
            ->assertRedirect(route('forgot-password'));

        $this->assertEquals('test@gmail.com', Session::pull('entered_user_email'));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Livewire\Creditor\Auth\NewUserChangePassword;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NewUserChangePasswordTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->user = User::factory()->create(['email_verified_at' => null]);
    }

    #[Test]
    public function it_can_render_livewire_component(): void
    {
        $this->get(URL::temporarySignedRoute(
            'new-user-register',
            now()->addDay(),
            ['email' => $this->user->email]
        ))
            ->assertSeeLivewire(NewUserChangePassword::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_component_view_page(): void
    {
        Livewire::withQueryParams(['email' => $this->user->email])
            ->test(NewUserChangePassword::class)
            ->assertViewIs('livewire.creditor.auth.new-user-change-password')
            ->assertOk();
    }

    #[Test]
    public function it_can_render_component_in_valid_email(): void
    {
        Livewire::withQueryParams(['email' => fake()->email()])
            ->test(NewUserChangePassword::class)
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    #[Test]
    #[DataProvider('validationRule')]
    public function it_can_render_create_validation_error(array $requestData, array $requestErrors): void
    {
        Livewire::withQueryParams(['email' => $this->user->email])
            ->test(NewUserChangePassword::class)
            ->set($requestData)
            ->call('changePassword')
            ->assertOk()
            ->assertHasErrors($requestErrors);
    }

    #[Test]
    public function it_can_render_call_change_password_with_different_email(): void
    {
        Livewire::withQueryParams(['email' => $this->user->email])
            ->test(NewUserChangePassword::class)
            ->set('form.email', fake()->email())
            ->call('changePassword')
            ->assertOk()
            ->assertHasErrors(['form.email' => ['in']]);
    }

    #[Test]
    public function it_can_render_set_new_password_successfully(): void
    {
        Http::fake(fn () => Http::response(['success' => true]));

        Livewire::withQueryParams(['email' => $this->user->email])
            ->test(NewUserChangePassword::class)
            ->set('form.email', $this->user->email)
            ->set('form.password', $password = 'Password@123')
            ->set('form.password_confirmation', $password)
            ->set('form.recaptcha', 'invalid-recaptcha-token')
            ->call('changePassword')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertRedirectToRoute('login');

        Notification::assertNotified(__('Your password has been saved.'));

        $this->assertNotNull($this->user->refresh()->email_verified_at);
    }

    public static function validationRule(): array
    {
        return [
            [
                [
                    'form.email' => '',
                ],
                [
                    'form.email' => ['required'],
                    'form.password' => ['required'],
                    'form.recaptcha' => ['required'],
                ],
            ],
            [
                [
                    'form.email' => 'abc',
                    'form.password' => 'abc',
                    'form.recaptcha' => 'invalid-recaptcha-token',
                ],
                [
                    'form.email' => ['email', 'in'],
                    'form.password' => ['confirmed', Password::class],
                ],
            ],
        ];
    }
}

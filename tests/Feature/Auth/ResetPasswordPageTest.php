<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Livewire\Creditor\Auth\ForgotPasswordPage;
use App\Livewire\Creditor\Auth\ResetPasswordPage;
use App\Models\User;
use App\Notifications\ResetPasswordQueuedNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResetPasswordPageTest extends TestCase
{
    #[Test]
    public function it_can_render_the_livewire_component(): void
    {
        $this->withoutVite()
            ->get(route('reset-password', ['token' => fake()->uuid()]))
            ->assertSeeLivewire(ResetPasswordPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_the_livewire_component_with_correct_view(): void
    {
        Livewire::test(ResetPasswordPage::class, ['token' => $token = fake()->uuid()])
            ->assertSet('form.token', $token)
            ->assertViewIs('livewire.creditor.auth.reset-password-page')
            ->assertOk();
    }

    #[Test]
    public function reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        Http::fake(fn () => Http::response(['success' => true]));

        $user = User::factory()->create();

        Livewire::test(ForgotPasswordPage::class)
            ->set('form.email', $user->email)
            ->set('form.recaptcha', fake()->uuid())
            ->call('forgotPassword')
            ->assertSet('resetCaptcha', true)
            ->assertSet('form.email', '')
            ->assertOk();

        Notification::assertSentTo($user, ResetPasswordQueuedNotification::class, function ($notification): bool {
            $response = $this->withoutVite()
                ->get(route('reset-password', ['token' => $notification->token]));

            $response->assertSeeLivewire(ResetPasswordPage::class)
                ->assertOk();

            return true;
        });
    }

    #[Test]
    public function password_can_be_reset_with_valid_token(): void
    {
        Event::fake();

        Notification::fake();

        Http::fake(fn () => Http::response(['success' => true]));

        $user = User::factory()->create();

        Livewire::test(ForgotPasswordPage::class)
            ->set('form.email', $user->email)
            ->set('form.recaptcha', fake()->uuid())
            ->call('forgotPassword')
            ->assertSet('resetCaptcha', true)
            ->assertSet('form.email', '')
            ->assertOk();

        Notification::assertSentTo($user, ResetPasswordQueuedNotification::class, function ($notification) use ($user): bool {
            Livewire::test(ResetPasswordPage::class, ['token' => $notification->token])
                ->set('form.email', $user->email)
                ->set('form.password', $password = 'Livewire@2024')
                ->set('form.password_confirmation', $password)
                ->call('resetPassword')
                ->assertHasNoErrors()
                ->assertRedirect(route('login'));

            Event::assertDispatched(fn (PasswordReset $passwordReset) => $passwordReset->user->id === $user->id);

            return true;
        });

        $this->assertTrue(Hash::check('Livewire@2024', $user->refresh()->password));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Livewire\Creditor\Auth\ForgotPasswordPage;
use App\Models\User;
use App\Notifications\ResetPasswordQueuedNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ForgotPasswordPageTest extends TestCase
{
    #[Test]
    public function it_can_render_the_livewire_component(): void
    {
        $this->withoutVite()
            ->get(route('forgot-password'))
            ->assertSeeLivewire(ForgotPasswordPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        Session::put('entered_user_email', 'test@gmail.com');

        Livewire::test(ForgotPasswordPage::class)
            ->assertSet('form.email', 'test@gmail.com')
            ->assertViewIs('livewire.creditor.auth.forgot-password-page')
            ->assertOk();
    }

    #[Test]
    public function it_can_give_required_validation(): void
    {
        Livewire::test(ForgotPasswordPage::class)
            ->call('forgotPassword')
            ->assertSet('resetCaptcha', true)
            ->assertHasErrors([
                'form.email' => [__('validation.required', ['attribute' => 'email'])],
                'form.recaptcha' => [__('validation.required', ['attribute' => 'recaptcha'])],
            ])
            ->assertOk();
    }

    #[Test]
    public function it_can_not_send_email_for_forgot_password_when_we_dont_have_user(): void
    {
        Http::fake(fn () => Http::response(['success' => true]));

        Livewire::test(ForgotPasswordPage::class)
            ->set('form.email', fake()->email())
            ->set('form.recaptcha', fake()->uuid())
            ->call('forgotPassword')
            ->assertSet('resetCaptcha', true)
            ->assertHasErrors(['form.email' => __(Password::INVALID_USER)])
            ->assertOk();
    }

    #[Test]
    public function it_can_send_reset_password_notification(): void
    {
        $user = User::factory()->create();

        Notification::fake();

        Http::fake(fn () => Http::response(['success' => true]));

        Livewire::test(ForgotPasswordPage::class)
            ->set('form.email', $user->email)
            ->set('form.recaptcha', fake()->uuid())
            ->call('forgotPassword')
            ->assertSet('resetCaptcha', true)
            ->assertSet('form.email', '')
            ->assertHasNoErrors()
            ->assertOk();

        Notification::assertSentTo([$user], ResetPasswordQueuedNotification::class);
    }
}

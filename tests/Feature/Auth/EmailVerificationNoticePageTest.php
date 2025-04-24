<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Livewire\Creditor\Auth\EmailVerificationNoticePage;
use App\Notifications\VerifyEmailQueuedNotification;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\AuthTestCase;

class EmailVerificationNoticePageTest extends AuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->user->email_verified_at = null;
        $this->user->save();
    }

    #[Test]
    public function it_can_render_the_livewire_component(): void
    {
        $this->get(route('email-verification-notice'))
            ->assertSeeLivewire(EmailVerificationNoticePage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_and_view_is_render(): void
    {
        Livewire::test(EmailVerificationNoticePage::class)
            ->assertViewIs('livewire.creditor.auth.email-verification-notice-page')
            ->assertOk();
    }

    #[Test]
    public function it_can_logged_out(): void
    {
        Livewire::test(EmailVerificationNoticePage::class)
            ->call('logout')
            ->assertOk()
            ->assertRedirect();

        $this->assertGuest();
    }

    #[Test]
    public function it_can_resend_email_verification_notification(): void
    {
        Notification::fake();

        Livewire::test(EmailVerificationNoticePage::class)
            ->call('resendEmailVerification')
            ->assertOk();

        Notification::assertSentTo($this->user, VerifyEmailQueuedNotification::class, function ($notification) {
            $this->get(call_user_func($notification::$createUrlCallback, $this->user))
                ->assertRedirect(RouteServiceProvider::HOME);

            return true;
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Providers;

use App\Notifications\ResetPasswordQueuedNotification;
use App\Notifications\VerifyEmailQueuedNotification;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        VerifyEmailQueuedNotification::createUrlUsing(function ($notifiable) {
            return URL::temporarySignedRoute(
                'email-verify',
                Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60 * 24)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
        });

        ResetPasswordQueuedNotification::createUrlUsing(function ($notifiable, $token) {
            return url(route('reset-password', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]));
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Filament\Notifications\Notification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class EmailVerificationController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            Notification::make('verified_email')
                ->title(__('Invalid or expired link.'))
                ->danger()
                ->duration(10000)
                ->send();

            return redirect()->route('home');
        }

        if (Auth::check()) {
            Auth::logout();

            Session::invalidate();

            Session::regenerateToken();

            Cache::flush();
        }

        $user = User::findOrFail($request->route()->parameter('id'));

        if ($user->hasVerifiedEmail()) {
            Notification::make('verified_email')
                ->title(__('Your email is already verified, please login to access!'))
                ->success()
                ->duration(10000)
                ->send();

            return redirect()->intended(RouteServiceProvider::HOME);
        }

        $user->markEmailAsVerified();

        event(new Verified($user));

        Auth::login($user);

        Session::regenerate();

        Notification::make('verified_email')
            ->title(__('Your email has been successfully verified!'))
            ->success()
            ->duration(10000)
            ->send();

        return redirect()->intended(RouteServiceProvider::HOME);
    }
}

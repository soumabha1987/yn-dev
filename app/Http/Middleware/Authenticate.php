<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next, ...$guards)
    {
        if ($this->shouldLogout($request)) {
            $isConsumerAuthentication = Arr::first($guards) === 'consumer' && Auth::guard('consumer')->check();

            $this->performLogout($request);

            return $isConsumerAuthentication ? to_route('consumer.login') : to_route('login');
        }

        $this->updateLastActiveTime($request);

        if (Arr::first($guards) === 'consumer' && ! Auth::guard('consumer')->check()) {
            return to_route('consumer.login');
        }

        if (Arr::first($guards) === 'web' && ! Auth::guard('web')->check()) {
            return to_route('login');
        }

        return parent::handle($request, $next, ...$guards); // @phpstan-ignore-line
    }

    /**
     * Update the last active time in the session.
     */
    private function updateLastActiveTime(Request $request): void
    {
        $request->session()->put('last_active_time', now());
    }

    /**
     * Check if the user should be logged out based on inactivity.
     */
    private function shouldLogout(Request $request): bool
    {
        return $request->session()->has('last_active_time')
            && now()->subMinutes(15)->gt($request->session()->get('last_active_time'))
            && app()->isProduction();
    }

    /**
     * Perform logout and session related actions.
     */
    private function performLogout(Request $request): void
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();
    }
}

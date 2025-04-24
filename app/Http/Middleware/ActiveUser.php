<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->blocked_at && $user->blocker_user_id) {
            Notification::make('active_plan')
                ->title(__('User Account has been deleted by :anotherUser personalize.', [
                    'anotherUser' => $user->blockerUser->name,
                ]))
                ->duration(10000)
                ->danger()
                ->send();

            Auth::logout();

            return to_route('login');
        }

        return $next($request);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Role;
use App\Models\User;
use Closure;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActivePlan
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasRole(Role::SUPERADMIN)) {
            return $next($request);
        }

        $user->loadMissing(['company.activeCompanyMembership']);

        if ($user->company->activeCompanyMembership === null) {
            Notification::make('active_plan')
                ->title(__('Renew your plan to continue enjoying the full benefits of :appName.', ['appName' => config('app.name')]))
                ->duration(10000)
                ->danger()
                ->send();

            return to_route('creditor.membership-settings');
        }

        return $next($request);
    }
}

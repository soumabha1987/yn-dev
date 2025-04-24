<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\CreditorCurrentStep;
use App\Enums\Role;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckProfileCompleted
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasRole(Role::CREDITOR)) {
            if ($user->company->current_step === CreditorCurrentStep::COMPLETED->value) {
                return $next($request);
            }

            return to_route('creditor.profile');
        }

        return $next($request);
    }
}

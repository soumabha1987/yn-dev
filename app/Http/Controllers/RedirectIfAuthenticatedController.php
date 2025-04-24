<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Services\SetupWizardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RedirectIfAuthenticatedController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return match (true) {
            $request->user()->hasRole(Role::SUPERADMIN) => to_route('super-admin.manage-creditors'),
            $request->user()->hasRole(Role::CREDITOR) && app(SetupWizardService::class)->getRemainingStepsCount($request->user()) > 0 => to_route('creditor.setup-wizard'),
            $request->user()->hasRole(Role::CREDITOR) => to_route('creditor.dashboard'),
            default => to_route('home'),
        };
    }
}

<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Http\Controllers\RedirectIfAuthenticatedController;
use App\Http\Controllers\TilledWebhookListenerController;
use App\Http\Middleware\ActiveUser;
use App\Http\Middleware\Authenticate;
use App\Livewire\Creditor\ManageConsumers\ListPage as ManageConsumersListPage;
use App\Livewire\Creditor\ManageConsumers\ViewPage as ManageConsumersViewPage;
use App\Livewire\Creditor\Reports\NewGenerateReport\IndexPage as NewGenerateReportPage;
use App\Livewire\Creditor\Reports\ReportHistoryPage;
use App\Livewire\Creditor\Subclient\ListPage as SubclientListPage;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\RoleMiddleware;

Route::post('tilled-webhook-listener', TilledWebhookListenerController::class)->name('tilled-webhook-listener');

Route::middleware([Authenticate::using('web'), ActiveUser::class, EnsureEmailIsVerified::redirectTo('email-verification-notice')])->group(function (): void {
    Route::get('/', RedirectIfAuthenticatedController::class)
        ->middleware(RoleMiddleware::using(Role::mainRoles()))
        ->name('home');

    Route::get('reports/history', ReportHistoryPage::class)->name('reports.history');
    Route::get('generate-reports', NewGenerateReportPage::class)->name('generate-reports');

    Route::get('sub-accounts', SubclientListPage::class)->name('manage-subclients');

    Route::prefix('manage-consumers')->group(function (): void {
        Route::get('/', ManageConsumersListPage::class)->name('manage-consumers');
        Route::get('{consumer}', ManageConsumersViewPage::class)->name('manage-consumers.view');
    });
});

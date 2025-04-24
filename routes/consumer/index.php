<?php

declare(strict_types=1);

use App\Http\Controllers\Consumer\InvitationController;
use App\Http\Controllers\ConsumerUnsubscribeEmailController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckIfSsnIsRequired;
use App\Http\Middleware\IsValidConsumerRequestMiddleware;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\ValidateSignature;
use App\Livewire\Consumer\CustomOffer;
use App\Livewire\Consumer\EcoMailBox;
use App\Livewire\Consumer\ExternalPayment;
use App\Livewire\Consumer\Login;
use App\Livewire\Consumer\MyAccount;
use App\Livewire\Consumer\Negotiate;
use App\Livewire\Consumer\Payment;
use App\Livewire\Consumer\PaymentComplete;
use App\Livewire\Consumer\PaymentHistory;
use App\Livewire\Consumer\Profile\Account;
use App\Livewire\Consumer\Profile\CommunicationControls;
use App\Livewire\Consumer\Profile\PersonalizeLogo;
use App\Livewire\Consumer\SchedulePlan;
use App\Livewire\Consumer\VerifySsn;
use Illuminate\Support\Facades\Route;

Route::get('unsubscribe-email/{data}', ConsumerUnsubscribeEmailController::class)
    ->name('consumer.unsubscribe-email');

Route::get('payment', ExternalPayment::class)
    ->middleware(ValidateSignature::class)
    ->name('consumer.external-payment');

Route::get('webview', InvitationController::class)->name('webview');

Route::middleware(RedirectIfAuthenticated::class . ':consumer')
    ->get('login', Login::class)
    ->name('consumer.login');

Route::middleware(Authenticate::using('consumer'))->group(function (): void {
    Route::get('verify-ssn', VerifySsn::class)->name('consumer.verify_ssn');

    Route::middleware(CheckIfSsnIsRequired::class)->group(function (): void {
        Route::get('/', MyAccount::class)->name('consumer.account');
        Route::get('profile', Account::class)->name('consumer.profile');
        Route::get('communication-controls', CommunicationControls::class)->name('consumer.communication_controls');
        Route::get('personalize-your-experience', PersonalizeLogo::class)->name('consumer.personalize_logo');
        Route::get('e-letters', EcoMailBox::class)->name('consumer.e-letters');

        Route::middleware(IsValidConsumerRequestMiddleware::class)->group(function (): void {
            Route::get('negotiate/{consumer}', Negotiate::class)->name('consumer.negotiate');
            Route::get('payment-history/{consumer}', PaymentHistory::class)->name('consumer.payment_history');
            Route::get('payment/{consumer}', Payment::class)->name('consumer.payment');
            Route::get('custom-offer/{consumer}', CustomOffer::class)->name('consumer.custom-offer');
            Route::get('custom-offer/{type}/{consumer}', CustomOffer::class)
                ->whereIn('type', ['settlement', 'installment'])
                ->name('consumer.custom-offer.type');
            Route::get('schedule-plan/{consumer}', SchedulePlan::class)->name('consumer.schedule_plan');

            Route::get('complete-payment/{consumer}', PaymentComplete::class)->name('consumer.complete_payment');
        });
    });
});

<?php

declare(strict_types=1);

use App\Http\Controllers\EmailVerificationController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\ValidateSignature;
use App\Livewire\Creditor\Auth\EmailVerificationNoticePage;
use App\Livewire\Creditor\Auth\ForgotPasswordPage;
use App\Livewire\Creditor\Auth\LoginPage;
use App\Livewire\Creditor\Auth\NewUserChangePassword;
use App\Livewire\Creditor\Auth\RegisterPage;
use App\Livewire\Creditor\Auth\ResetPasswordPage;
use App\Livewire\Creditor\ChangePasswordPage;
use Illuminate\Support\Facades\Route;

Route::middleware(Authenticate::class)
    ->group(function (): void {
        Route::get('change-password', ChangePasswordPage::class)->name('change-password');

        Route::get('email-verification', EmailVerificationNoticePage::class)
            ->name('email-verification-notice');
    });

Route::get('email-verify/{id}/{hash}', EmailVerificationController::class)->name('email-verify');

Route::middleware(RedirectIfAuthenticated::class)
    ->group(function (): void {
        Route::get('login', LoginPage::class)->name('login');
        Route::get('register/{code?}', RegisterPage::class)->name('register');
        Route::get('forgot-password', ForgotPasswordPage::class)->name('forgot-password');
        Route::get('reset-password/{token}', ResetPasswordPage::class)->name('reset-password');
        Route::get('new-user-register', NewUserChangePassword::class)
            ->name('new-user-register')
            ->middleware(ValidateSignature::class);
    });

<?php

declare(strict_types=1);

namespace App\Exceptions;

use Filament\Notifications\Notification;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Session;
use Sentry\State\Scope;
use Throwable;

use function Sentry\configureScope;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = ['current_password', 'password', 'password_confirmation'];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        MerchantPaymentException::class,
    ];

    public function render($request, Throwable $e)
    {
        if ($e instanceof TokenMismatchException) {
            Notification::make('token-mismatch')
                ->success()
                ->title(__('Logged out due to timeout. Please log back in.'))
                ->duration(10000)
                ->send();

            return back();
        }

        return parent::render($request, $e);
    }

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            if (app()->isProduction()) {
                if ($user = request()->user()) {
                    configureScope(function (Scope $scope) use ($user): void {
                        $scope->setUser([
                            'id' => $user->id,
                            'email' => $user->email,
                        ]);
                    });
                }
                if (app()->bound('sentry')) {
                    app('sentry')->captureException($e);
                }
            }
        });
    }
}

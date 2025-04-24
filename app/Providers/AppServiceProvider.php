<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\CustomContentService;
use App\Services\SetupWizardService;
use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use Carbon\CarbonInterval;
use Filament\Notifications\Livewire\Notifications;
use Filament\Support\Enums\VerticalAlignment;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Telescope\TelescopeServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local')) {
            $this->app->register(IdeHelperServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(
            fn (): Password => Password::min(8) // Required at least 8 characters...
                ->max(16) // Required maximum 16 characters...
                ->mixedCase() // Required lower as well as upper case characters...
                ->numbers() // Required at least one Number ...
                ->symbols() // Required at least one symbol...
        );

        Model::unguard();

        Notifications::verticalAlignment(VerticalAlignment::End);

        DB::whenQueryingForLongerThan(CarbonInterval::seconds(5), function (Connection $connection, QueryExecuted $event): void {
            Log::channel('daily')->debug('Query taking longer than 5 seconds', [$connection, $event]);
        });

        LogViewer::auth(fn (Request $request): bool => $request->user() && in_array($request->user()->email, ['naliyaparaspn@gmail.com']));

        $this->app->when(SetupWizardService::class)
            ->needs('$customContentService')
            ->give(fn (Application $app): mixed => $app->make(CustomContentService::class));
    }
}

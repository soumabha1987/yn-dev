<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\FeatureName;
use App\Services\FeatureFlagService;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Log;

class ScheduleImportCommandListener
{
    /**
     * Handle the event.
     */
    public function handle(CommandStarting $event): void
    {
        if ($event->command === 'import-consumers:via-sftp') {
            if (app(FeatureFlagService::class)->disabled(FeatureName::SCHEDULE_IMPORT)) {
                Log::channel('daily')->info('This feature is disabled by YouNegotiate', ['command' => $event->command]);

                exit(0);
            }
        }
    }
}

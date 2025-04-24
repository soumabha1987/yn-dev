<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\FeatureName;
use App\Services\FeatureFlagService;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Log;

class ScheduleExportCommandListener
{
    /**
     * Handle the event.
     */
    public function handle(CommandStarting $event): void
    {
        $commands = [
            'daily:schedule-export',
            'weekly:schedule-export',
            'monthly:schedule-export',
        ];

        if (in_array($event->command, $commands)) {
            if (app(FeatureFlagService::class)->disabled(FeatureName::SCHEDULE_EXPORT)) {
                Log::channel('daily')->info('This feature is disabled by YouNegotiate', ['command' => $event->command]);

                exit(0);
            }
        }
    }
}

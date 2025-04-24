<?php

declare(strict_types=1);

namespace App\Console;

use App\Console\Commands\DailyScheduleExportCommand;
use App\Console\Commands\DeleteCFPBLettersZipFileCommand;
use App\Console\Commands\ImportConsumersViaSFTPCommand;
use App\Console\Commands\MembershipPlanAutoRenewCommand;
use App\Console\Commands\MonthlyScheduleExportCommand;
use App\Console\Commands\NextPaymentDueSoonSkipFailedScheduledCommand;
use App\Console\Commands\NotifyOfferExpiringSoon;
use App\Console\Commands\NotifyUpcomingPaymentReminder;
use App\Console\Commands\PartnerMonthlyBillingReportsCommand;
use App\Console\Commands\ProcessConsumerPaymentsCommand;
use App\Console\Commands\ProcessCreditorPaymentsCommand;
use App\Console\Commands\ReprocessConsumerFailedPaymentsCommand;
use App\Console\Commands\ReprocessMembershipAutoRenewFailedCommand;
use App\Console\Commands\ResetExpiredOffersCommand;
use App\Console\Commands\RestartPaymentPlanCommand;
use App\Console\Commands\RunCampaignCommand;
use App\Console\Commands\RunScheduledAutomationCampaignCommand;
use App\Console\Commands\SendExpiredPlanNotificationCommand;
use App\Console\Commands\SoftDeleteCompaniesCommand;
use App\Console\Commands\WeeklyScheduleExportCommand;
use Illuminate\Auth\Console\ClearResetsCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Console\PruneCommand;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Carbon;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     * We need to add more schedule report related command in this file.
     *
     * @see https://github.com/devclick-technology/ynb/pull/322
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command(MembershipPlanAutoRenewCommand::class)->twiceDaily(12, 18);

        $schedule->command(ReprocessMembershipAutoRenewFailedCommand::class)->twiceDaily(3, 15);

        $schedule->command(ClearResetsCommand::class)->dailyAt('8:00');

        $schedule->command(RestartPaymentPlanCommand::class)->dailyAt('4:00');

        $schedule->command(ProcessConsumerPaymentsCommand::class)->dailyAt('5:00');
        $schedule->command(ReprocessConsumerFailedPaymentsCommand::class)->dailyAt('7:00');
        $schedule->command(NextPaymentDueSoonSkipFailedScheduledCommand::class)->dailyAt('2:30');
        $schedule->command(NotifyOfferExpiringSoon::class)->dailyAt('5:00');

        $schedule->command(ProcessCreditorPaymentsCommand::class)->weeklyOn(Carbon::MONDAY, '13:00');

        $schedule->command(DailyScheduleExportCommand::class)->dailyAt('10:45');
        $schedule->command(WeeklyScheduleExportCommand::class)->weeklyOn(Carbon::MONDAY, '10:05');
        $schedule->command(MonthlyScheduleExportCommand::class)->monthlyOn(time: '10:25');

        $schedule->command(RunScheduledAutomationCampaignCommand::class)->hourly();
        $schedule->command(NotifyUpcomingPaymentReminder::class)->dailyAt('16:30');

        $schedule->command(PruneCommand::class)->daily();

        $schedule->command(SendExpiredPlanNotificationCommand::class)->dailyAt('17:00');
        $schedule->command(DeleteCFPBLettersZipFileCommand::class)->daily();

        $schedule->command(SoftDeleteCompaniesCommand::class)->daily();
        $schedule->command(PartnerMonthlyBillingReportsCommand::class)->monthlyOn(time: '14:00');

        $schedule->command(RunCampaignCommand::class)->dailyAt(time: '17:30');

        $schedule->command(ImportConsumersViaSFTPCommand::class)->daily();

        $schedule->command(ResetExpiredOffersCommand::class)->dailyAt('9:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}

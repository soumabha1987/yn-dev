<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Commands\Traits\Reports;
use App\Enums\ScheduleExportFrequency;
use Carbon\Carbon;
use Illuminate\Console\Command;

class WeeklyScheduleExportCommand extends Command
{
    use Reports;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weekly:schedule-export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will export the weekly records and send them via email or upload them to the SFTP server.';

    protected Carbon $from;

    protected Carbon $to;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->from = now()->subWeek();
        $this->to = now();

        $this->scheduleExport(ScheduleExportFrequency::WEEKLY);
    }
}

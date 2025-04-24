<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Console\Commands\DailyScheduleExportCommand;
use App\Enums\NewReportType;
use App\Enums\ScheduleExportFrequency;
use App\Jobs\DeleteScheduleExportFileJob;
use App\Jobs\SendScheduleExportEmailJob;
use App\Mail\ScheduleExportMail;
use App\Models\Consumer;
use App\Models\ScheduleExport;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DailyScheduleExportCommandTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    // TODO: When feature flag is disabled at that time we need to check its working or not
    // @see https://laravel.com/docs/11.x/console-tests#console-events

    #[Test]
    public function can_export_transaction_history(): void
    {
        Storage::fake();

        Bus::fake();

        ScheduleExport::factory()->create([
            'subclient_id' => null,
            'company_id' => null,
            'report_type' => NewReportType::ALL_ACCOUNTS_STATUS_AND_ACTIVITY,
            'user_id' => $this->user->id,
            'sftp_connection_id' => null,
            'emails' => [fake()->safeEmail()],
            'frequency' => ScheduleExportFrequency::DAILY,
            'last_sent_at' => null,
            'pause' => false,
        ]);

        Transaction::factory()->create();

        $this->artisan(DailyScheduleExportCommand::class)->assertOk();

        Bus::assertChained([SendScheduleExportEmailJob::class, DeleteScheduleExportFileJob::class]);

        $this->assertDirectoryExists(Storage::path('public/schedule-export'));
    }

    #[Test]
    public function can_export_all_consumer_account_summary_delivered_mail_with_attachment(): void
    {
        Storage::fake();

        Mail::fake();

        $scheduleExport = ScheduleExport::factory()->create([
            'subclient_id' => null,
            'company_id' => null,
            'report_type' => NewReportType::SUMMARY_BALANCE_COMPLIANCE,
            'user_id' => $this->user->id,
            'sftp_connection_id' => null,
            'emails' => [fake()->safeEmail()],
            'frequency' => ScheduleExportFrequency::DAILY,
            'last_sent_at' => null,
            'pause' => false,
        ]);

        Consumer::factory()->create();

        $this->artisan(DailyScheduleExportCommand::class)->assertOk();

        Mail::assertQueued(ScheduleExportMail::class, fn ($mail) => $scheduleExport->is((fn () => $this->{'scheduleExport'})->call($mail)));

        Storage::assertDirectoryEmpty('public/schedule-export');
    }

    #[Test]
    public function daily_schedule_export_only_take_not_paused(): void
    {
        Bus::fake();

        ScheduleExport::factory()->create([
            'subclient_id' => null,
            'company_id' => null,
            'report_type' => NewReportType::CONSUMER_PAYMENTS,
            'user_id' => $this->user->id,
            'sftp_connection_id' => null,
            'emails' => [fake()->safeEmail()],
            'frequency' => ScheduleExportFrequency::DAILY,
            'last_sent_at' => now(),
            'pause' => true,
        ]);

        $this->artisan(DailyScheduleExportCommand::class)->assertOk();

        Bus::assertNothingDispatched();
    }
}

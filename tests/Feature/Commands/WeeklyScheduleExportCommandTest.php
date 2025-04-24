<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Console\Commands\WeeklyScheduleExportCommand;
use App\Enums\ConsumerStatus;
use App\Enums\NewReportType;
use App\Enums\ScheduleExportFrequency;
use App\Enums\TransactionStatus;
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

class WeeklyScheduleExportCommandTest extends TestCase
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
    public function can_export_schedule_transactions(): void
    {
        Storage::fake();

        Bus::fake();

        ScheduleExport::factory()->create([
            'subclient_id' => null,
            'company_id' => null,
            'report_type' => NewReportType::DISPUTE_NO_PAY,
            'user_id' => $this->user->id,
            'sftp_connection_id' => null,
            'emails' => [fake()->safeEmail()],
            'frequency' => ScheduleExportFrequency::WEEKLY,
            'last_sent_at' => now()->subDays(6),
            'pause' => false,
        ]);

        Consumer::factory()->create([
            'status' => fake()->randomElement([ConsumerStatus::DEACTIVATED, ConsumerStatus::NOT_PAYING, ConsumerStatus::DISPUTE]),
            'disputed_at' => today()->subDay(),
        ]);

        $this->artisan(WeeklyScheduleExportCommand::class)->assertOk();

        Bus::assertChained([SendScheduleExportEmailJob::class, DeleteScheduleExportFileJob::class]);

        $this->assertDirectoryExists(Storage::path('public/schedule-export'));
    }

    #[Test]
    public function can_export_consumer_payment_delivered_mail_with_attachment(): void
    {
        Storage::fake();

        Mail::fake();

        $scheduleExport = ScheduleExport::factory()->create([
            'subclient_id' => null,
            'company_id' => null,
            'report_type' => NewReportType::CONSUMER_PAYMENTS,
            'user_id' => $this->user->id,
            'sftp_connection_id' => null,
            'emails' => [fake()->safeEmail()],
            'frequency' => ScheduleExportFrequency::WEEKLY,
            'last_sent_at' => null,
            'pause' => false,
        ]);

        Transaction::factory()->create([
            'status' => TransactionStatus::SUCCESSFUL,
            'created_at' => today()->subDay(),
        ]);

        $this->artisan(WeeklyScheduleExportCommand::class)->assertOk();

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
            'report_type' => NewReportType::FINAL_PAYMENTS_BALANCE_SUMMARY,
            'user_id' => $this->user->id,
            'sftp_connection_id' => null,
            'emails' => [fake()->safeEmail()],
            'frequency' => ScheduleExportFrequency::WEEKLY,
            'last_sent_at' => null,
            'pause' => true,
        ]);

        $this->artisan(WeeklyScheduleExportCommand::class)->assertOk();

        Bus::assertNothingDispatched();
    }
}

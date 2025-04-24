<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Console\Commands\MonthlyScheduleExportCommand;
use App\Enums\NewReportType;
use App\Enums\ScheduleExportFrequency;
use App\Jobs\DeleteScheduleExportFileJob;
use App\Jobs\SendScheduleExportEmailJob;
use App\Mail\ScheduleExportMail;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\ConsumerProfile;
use App\Models\ScheduleExport;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MonthlyScheduleExportCommandTest extends TestCase
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

        $company = Company::factory()->create();

        ScheduleExport::factory()->create([
            'subclient_id' => null,
            'company_id' => $company->id,
            'report_type' => NewReportType::SUMMARY_BALANCE_COMPLIANCE,
            'user_id' => $this->user->id,
            'sftp_connection_id' => null,
            'csv_header_id' => null,
            'emails' => [fake()->safeEmail()],
            'frequency' => ScheduleExportFrequency::MONTHLY,
            'last_sent_at' => now()->subWeeks(4),
            'pause' => false,
        ]);

        Consumer::factory()->for($company)->create();

        $this->artisan(MonthlyScheduleExportCommand::class)->assertOk();

        Bus::assertChained([SendScheduleExportEmailJob::class, DeleteScheduleExportFileJob::class]);

        $this->assertDirectoryExists(Storage::path('public/schedule-export'));
    }

    #[Test]
    public function can_export_schedule_transactions_and_delivered_mail_with_attachment(): void
    {
        Storage::fake();

        Mail::fake();

        $scheduleExport = ScheduleExport::factory()->create([
            'subclient_id' => null,
            'company_id' => null,
            'report_type' => NewReportType::CONSUMER_OPT_OUT,
            'user_id' => $this->user->id,
            'sftp_connection_id' => null,
            'emails' => [fake()->safeEmail()],
            'frequency' => ScheduleExportFrequency::MONTHLY,
            'last_sent_at' => null,
            'pause' => false,
        ]);

        Consumer::factory()
            ->for(ConsumerProfile::factory()->create(['email_permission' => false]))
            ->create();

        $this->artisan(MonthlyScheduleExportCommand::class)->assertOk();

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
            'report_type' => NewReportType::SUMMARY_BALANCE_COMPLIANCE,
            'user_id' => $this->user->id,
            'sftp_connection_id' => null,
            'emails' => [fake()->safeEmail()],
            'frequency' => ScheduleExportFrequency::MONTHLY,
            'last_sent_at' => now()->addHours(1),
            'pause' => true,
        ]);

        $this->artisan(MonthlyScheduleExportCommand::class)->assertOk();

        Bus::assertNothingDispatched();
    }
}

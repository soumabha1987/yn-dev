<?php

declare(strict_types=1);

namespace Tests\Feature\Reports\GenerateReport;

use App\Enums\ReportType;
use App\Enums\Role as EnumRole;
use App\Enums\TransactionStatus;
use App\Exports\ScheduleTransactionExport;
use App\Livewire\Creditor\Reports\GenerateReport\IndexPage as GenerateReportPage;
use App\Models\ReportHistory;
use App\Models\ScheduleTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ScheduledTransactionTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        $this->markTestSkipped();

        parent::setUp();

        Model::preventLazyLoading();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_can_export_counter_offer_data_when_role_creditor(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        ScheduleTransaction::factory(25)
            ->sequence(fn (Sequence $sequence) => [
                'schedule_date' => today()->addDays($sequence->index + 2),
            ])
            ->create([
                'company_id' => $this->user->company_id,
                'status' => TransactionStatus::SCHEDULED->value,
            ]);

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.subclient_id', 'master')
            ->set('form.report_type', ReportType::SCHEDULED_TRANSACTIONS)
            ->set('form.start_date', $startDate = today()->toDateString())
            ->set('form.end_date', $endDate = today()->addMonths(2)->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertFileDownloaded()
            ->assertOk();

        $this->assertDatabaseHas(ReportHistory::class, [
            'user_id' => $this->user->id,
            'report_type' => ReportType::SCHEDULED_TRANSACTIONS,
            'records' => 25,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => true,
        ]);
    }

    #[Test]
    public function it_can_store_the_counter_offers_into_storage_when_role_creditor(): void
    {
        Storage::fake();

        Excel::fake();

        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        ScheduleTransaction::factory(15)
            ->sequence(fn (Sequence $sequence) => [
                'schedule_date' => today()->addDays($sequence->index + 2),
            ])
            ->create([
                'company_id' => $this->user->company_id,
                'status' => TransactionStatus::SCHEDULED->value,
            ]);

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.subclient_id', 'master')
            ->set('form.report_type', ReportType::SCHEDULED_TRANSACTIONS)
            ->set('form.start_date', $startDate = today()->toDateString())
            ->set('form.end_date', $endDate = today()->addMonths(2)->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertOk();

        $reportHistory = ReportHistory::query()->firstOrFail();

        Excel::assertStored(
            'download-report/' . Str::slug(ReportType::SCHEDULED_TRANSACTIONS->value) . '/' . $reportHistory->downloaded_file_name,
            fn (ScheduleTransactionExport $scheduleTransactionExport) => $scheduleTransactionExport->collection()->count() === 15
        );
    }

    #[Test]
    public function it_can_export_counter_offer_data_when_role_super_admin(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        ScheduleTransaction::factory(25)
            ->sequence(fn (Sequence $sequence) => [
                'schedule_date' => today()->addDays($sequence->index + 2),
            ])
            ->create([
                'status' => TransactionStatus::SCHEDULED->value,
            ]);

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.report_type', ReportType::SCHEDULED_TRANSACTIONS)
            ->set('form.start_date', $startDate = today()->toDateString())
            ->set('form.end_date', $endDate = today()->addMonths(2)->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertFileDownloaded()
            ->assertOk();

        $this->assertDatabaseHas(ReportHistory::class, [
            'user_id' => $this->user->id,
            'report_type' => ReportType::SCHEDULED_TRANSACTIONS,
            'records' => 25,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => true,
        ]);
    }

    #[Test]
    public function it_can_store_the_counter_offers_into_storage_when_role_super_admin(): void
    {
        Storage::fake();

        Excel::fake();

        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        ScheduleTransaction::factory(10)
            ->sequence(fn (Sequence $sequence) => [
                'schedule_date' => today()->addDays($sequence->index + 2),
            ])
            ->create([
                'status' => TransactionStatus::SCHEDULED->value,
            ]);

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.report_type', ReportType::SCHEDULED_TRANSACTIONS)
            ->set('form.start_date', $startDate = today()->toDateString())
            ->set('form.end_date', $endDate = today()->addMonths(2)->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertOk();

        $reportHistory = ReportHistory::query()->firstOrFail();

        Excel::assertStored(
            'download-report/' . Str::slug(ReportType::SCHEDULED_TRANSACTIONS->value) . '/' . $reportHistory->downloaded_file_name,
            fn (ScheduleTransactionExport $scheduleTransactionExport) => $scheduleTransactionExport->collection()->count() === 10
        );
    }
}

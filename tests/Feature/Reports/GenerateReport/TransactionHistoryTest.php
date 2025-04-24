<?php

declare(strict_types=1);

namespace Tests\Feature\Reports\GenerateReport;

use App\Enums\ReportType;
use App\Enums\Role as EnumRole;
use App\Exports\TransactionHistoryExport;
use App\Livewire\Creditor\Reports\GenerateReport\IndexPage as GenerateReportPage;
use App\Models\ReportHistory;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TransactionHistoryTest extends TestCase
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
    public function it_can_export_transaction_history_data_when_role_creditor(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        Transaction::factory(20)->create(['company_id' => $this->user->company_id]);

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.subclient_id', 'master')
            ->set('form.report_type', ReportType::TRANSACTION_HISTORY)
            ->set('form.start_date', $startDate = today()->subMonths(2)->toDateString())
            ->set('form.end_date', $endDate = today()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertFileDownloaded()
            ->assertOk();

        $this->assertDatabaseHas(ReportHistory::class, [
            'user_id' => $this->user->id,
            'report_type' => ReportType::TRANSACTION_HISTORY,
            'records' => 20,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => true,
        ]);
    }

    #[Test]
    public function it_can_store_the_transaction_history_into_storage_when_role_creditor(): void
    {
        Storage::fake();

        Excel::fake();

        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        $occurTransaction = Transaction::factory(20)->create(['company_id' => $this->user->company_id])->first();

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.subclient_id', 'master')
            ->set('form.report_type', ReportType::TRANSACTION_HISTORY)
            ->set('form.start_date', today()->subMonths(2)->toDateString())
            ->set('form.end_date', today()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertOk();

        $reportHistory = ReportHistory::query()->firstOrFail();

        Excel::assertStored(
            'download-report/' . Str::slug(ReportType::TRANSACTION_HISTORY->value) . '/' . $reportHistory->downloaded_file_name,
            fn (TransactionHistoryExport $transactionHistoryExport) => $transactionHistoryExport->collection()->contains(fn ($transaction) => $transaction['transaction_id'] === $occurTransaction->transaction_id)
                && $transactionHistoryExport->collection()->count() === 20
        );
    }

    #[Test]
    public function it_can_export_transaction_history_data_when_role_super_admin(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        Transaction::factory(20)->create();

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.report_type', ReportType::TRANSACTION_HISTORY)
            ->set('form.start_date', $startDate = today()->subMonths(2)->toDateString())
            ->set('form.end_date', $endDate = today()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertFileDownloaded()
            ->assertOk();

        $this->assertDatabaseHas(ReportHistory::class, [
            'user_id' => $this->user->id,
            'report_type' => ReportType::TRANSACTION_HISTORY,
            'records' => 20,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => true,
        ]);
    }

    #[Test]
    public function it_can_store_the_transaction_history_into_storage_when_role_super_admin(): void
    {
        Storage::fake();

        Excel::fake();

        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        $occurTransaction = Transaction::factory(20)->create()->first();

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.report_type', ReportType::TRANSACTION_HISTORY)
            ->set('form.start_date', today()->subMonths(2)->toDateString())
            ->set('form.end_date', today()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertOk();

        $reportHistory = ReportHistory::query()->firstOrFail();

        Excel::assertStored(
            'download-report/' . Str::slug(ReportType::TRANSACTION_HISTORY->value) . '/' . $reportHistory->downloaded_file_name,
            fn (TransactionHistoryExport $transactionHistoryExport) => $transactionHistoryExport->collection()->contains(fn ($transaction) => $transaction['transaction_id'] === $occurTransaction->transaction_id)
                && $transactionHistoryExport->collection()->count() === 20
        );
    }
}

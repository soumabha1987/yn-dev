<?php

declare(strict_types=1);

namespace Tests\Feature\Reports\NewGenerateReport;

use App\Enums\NewReportType;
use App\Enums\Role as EnumRole;
use App\Exports\finalPaymentsBalanceSummaryExport;
use App\Livewire\Creditor\Reports\NewGenerateReport\IndexPage as GenerateReportPage;
use App\Models\Consumer;
use App\Models\ReportHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FinalPaymentBalanceSummaryTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Model::preventLazyLoading();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_can_export_consumer_final_payment_balance_summary_report_with_data_when_role_creditor(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        Consumer::factory(16)->create(['company_id' => $this->user->company_id]);

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.subclient_id', 'master')
            ->set('form.report_type', NewReportType::FINAL_PAYMENTS_BALANCE_SUMMARY)
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertFileDownloaded()
            ->assertOk();

        $this->assertDatabaseHas(ReportHistory::class, [
            'user_id' => $this->user->id,
            'report_type' => NewReportType::FINAL_PAYMENTS_BALANCE_SUMMARY,
            'records' => 16,
            'status' => true,
        ]);
    }

    #[Test]
    public function it_can_store_consumer_final_payment_balance_summary_into_storage_when_role_creditor(): void
    {
        Storage::fake();

        Excel::fake();

        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        $occurConsumer = Consumer::factory(14)
            ->create(['company_id' => $this->user->company_id])
            ->first();

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.subclient_id', 'master')
            ->set('form.report_type', NewReportType::FINAL_PAYMENTS_BALANCE_SUMMARY)
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertOk();

        $reportHistory = ReportHistory::query()->firstOrFail();

        Excel::assertStored(
            'download-report/' . Str::slug(NewReportType::FINAL_PAYMENTS_BALANCE_SUMMARY->value) . '/' . $reportHistory->downloaded_file_name,
            fn (finalPaymentsBalanceSummaryExport $finalPaymentsBalanceSummaryExport) => $finalPaymentsBalanceSummaryExport->collection()
                ->contains(fn ($consumer) => $consumer['total_payments_made'] === Number::currency((float) $occurConsumer->total_balance - $occurConsumer->current_balance))
                && $finalPaymentsBalanceSummaryExport->collection()->count() === 14
        );
    }

    #[Test]
    public function it_can_export_consumer_final_payment_balance_summary_report_with_data_when_role_super_admin(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        Consumer::factory(12)->create();

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.report_type', NewReportType::FINAL_PAYMENTS_BALANCE_SUMMARY)
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertFileDownloaded()
            ->assertOk();

        $this->assertDatabaseHas(ReportHistory::class, [
            'user_id' => $this->user->id,
            'report_type' => NewReportType::FINAL_PAYMENTS_BALANCE_SUMMARY,
            'records' => 12,
            'status' => true,
        ]);
    }

    #[Test]
    public function it_can_store_the_consumer_final_payment_balance_summary_when_role_super_admin(): void
    {
        Storage::fake();

        Excel::fake();

        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        $occurConsumer = Consumer::factory(22)
            ->create()
            ->first();

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.report_type', NewReportType::FINAL_PAYMENTS_BALANCE_SUMMARY)
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertOk();

        $reportHistory = ReportHistory::query()->firstOrFail();

        Excel::assertStored(
            'download-report/' . Str::slug(NewReportType::FINAL_PAYMENTS_BALANCE_SUMMARY->value) . '/' . $reportHistory->downloaded_file_name,
            fn (finalPaymentsBalanceSummaryExport $finalPaymentsBalanceSummaryExport) => $finalPaymentsBalanceSummaryExport->collection()
                ->contains(fn ($consumer) => $consumer['total_payments_made'] === Number::currency((float) $occurConsumer->total_balance - $occurConsumer->current_balance))
                && $finalPaymentsBalanceSummaryExport->collection()->count() === 22
        );
    }
}

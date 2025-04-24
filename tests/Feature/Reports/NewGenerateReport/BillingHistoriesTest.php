<?php

declare(strict_types=1);

namespace Tests\Feature\Reports\NewGenerateReport;

use App\Enums\MembershipTransactionStatus;
use App\Enums\ReportType;
use App\Enums\Role as EnumRole;
use App\Enums\Timezone;
use App\Exports\BillingHistoriesExport;
use App\Livewire\Creditor\Reports\NewGenerateReport\IndexPage as GenerateReportPage;
use App\Models\MembershipTransaction;
use App\Models\ReportHistory;
use App\Models\User;
use App\Models\YnTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BillingHistoriesTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Model::preventLazyLoading();

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::create(['name' => EnumRole::SUPERADMIN]));
    }

    #[Test]
    public function it_can_export_billing_histories_reports_when_role_super_admin(): void
    {
        MembershipTransaction::factory(5)->create([
            'status' => MembershipTransactionStatus::SUCCESS,
            'created_at' => now()->subDays(fake()->numberBetween(2, 20)),
        ]);

        YnTransaction::factory(5)->create([
            'status' => MembershipTransactionStatus::SUCCESS,
            'created_at' => now()->subDays(fake()->numberBetween(2, 20)),
        ]);

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.company_id', null)
            ->set('form.report_type', ReportType::BILLING_HISTORIES)
            ->set('form.start_date', $startDate = today()->subMonth()->toDateString())
            ->set('form.end_date', $endDate = today()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertFileDownloaded()
            ->assertOk();

        $timezone = $this->user->company->timezone->value;

        $this->assertDatabaseHas(ReportHistory::class, [
            'user_id' => $this->user->id,
            'report_type' => ReportType::BILLING_HISTORIES,
            'records' => 10,
            'start_date' => Carbon::parse($startDate, $timezone)->startOfDay()->utc()->toDateString(),
            'end_date' => Carbon::parse($endDate, $timezone)->endOfDay()->utc()->toDateString(),
            'status' => true,
        ]);
    }

    #[Test]
    public function it_can_store_the_consumers_into_storage_when_role_super_admin(): void
    {
        Storage::fake();

        Excel::fake();

        MembershipTransaction::factory(5)->create([
            'status' => MembershipTransactionStatus::SUCCESS,
            'created_at' => now()->subDays(fake()->numberBetween(2, 20)),
        ]);

        YnTransaction::factory(5)->create([
            'status' => MembershipTransactionStatus::SUCCESS,
            'created_at' => now()->subdays(fake()->numberBetween(2, 20)),
        ]);

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.company_id', null)
            ->set('form.report_type', ReportType::BILLING_HISTORIES)
            ->set('form.start_date', today()->subMonth()->toDateString())
            ->set('form.end_date', today()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertOk();

        $reportHistory = ReportHistory::query()->firstOrFail();

        Excel::assertStored(
            'download-report/' . Str::slug(ReportType::BILLING_HISTORIES->value) . '/' . $reportHistory->downloaded_file_name,
            fn (BillingHistoriesExport $billingHistoriesExport) => $billingHistoriesExport->collection()->count() === 10
        );
    }

    #[Test]
    public function it_can_export_billing_histories_reports_when_role_super_admin_with_outer_date_range_data(): void
    {
        MembershipTransaction::factory($membershipTransactionCount = 10)
            ->sequence(fn (Sequence $sequence) => [
                'created_at' => now()->subDays(
                    $sequence->index % 2
                        ? fake()->numberBetween(2, 20)
                        : fake()->numberBetween(32, 60)
                ),
            ])
            ->create(['status' => MembershipTransactionStatus::SUCCESS]);

        YnTransaction::factory($ynTransactionCount = 20)
            ->sequence(fn (Sequence $sequence) => [
                'created_at' => now()->subDays(
                    $sequence->index % 2
                        ? fake()->numberBetween(2, 20)
                        : fake()->numberBetween(32, 60)
                ),
            ])
            ->create(['status' => MembershipTransactionStatus::SUCCESS]);

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.company_id', null)
            ->set('form.report_type', ReportType::BILLING_HISTORIES)
            ->set('form.start_date', $startDate = today()->subMonth()->toDateString())
            ->set('form.end_date', $endDate = today()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertFileDownloaded()
            ->assertOk();

        $this->assertDatabaseHas(ReportHistory::class, [
            'user_id' => $this->user->id,
            'report_type' => ReportType::BILLING_HISTORIES,
            'records' => ($membershipTransactionCount + $ynTransactionCount) / 2,
            'start_date' => Carbon::parse($startDate, Timezone::EST->value)->startOfDay()->utc()->toDateString(),
            'end_date' => Carbon::parse($endDate, Timezone::EST->value)->endOfDay()->utc()->toDateString(),
            'status' => true,
        ]);
    }

    #[Test]
    public function it_can_store_the_consumers_into_storage_when_role_super_admin_with_outer_date_range_data(): void
    {
        Storage::fake();

        Excel::fake();

        MembershipTransaction::factory($membershipTransactionCount = 10)
            ->sequence(fn (Sequence $sequence) => [
                'created_at' => now()->subDays(
                    $sequence->index % 2
                        ? fake()->numberBetween(2, 20)
                        : fake()->numberBetween(32, 60)
                ),
            ])
            ->create(['status' => MembershipTransactionStatus::SUCCESS]);

        YnTransaction::factory($ynTransactionCount = 20)
            ->sequence(fn (Sequence $sequence) => [
                'created_at' => now()->subDays(
                    $sequence->index % 2
                        ? fake()->numberBetween(2, 20)
                        : fake()->numberBetween(32, 60)
                ),
            ])
            ->create(['status' => MembershipTransactionStatus::SUCCESS]);

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.company_id', null)
            ->set('form.report_type', ReportType::BILLING_HISTORIES)
            ->set('form.start_date', today()->subMonth()->toDateString())
            ->set('form.end_date', today()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertOk();

        $reportHistory = ReportHistory::query()->firstOrFail();

        Excel::assertStored(
            'download-report/' . Str::slug(ReportType::BILLING_HISTORIES->value) . '/' . $reportHistory->downloaded_file_name,
            fn (BillingHistoriesExport $billingHistoriesExport) => $billingHistoriesExport->collection()->count() === ($membershipTransactionCount + $ynTransactionCount) / 2
        );
    }
}

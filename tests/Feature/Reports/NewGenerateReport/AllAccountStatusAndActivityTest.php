<?php

declare(strict_types=1);

namespace Tests\Feature\Reports\NewGenerateReport;

use App\Enums\NewReportType;
use App\Enums\Role as EnumRole;
use App\Enums\Timezone;
use App\Exports\AllAccountStatusAndActivityExport;
use App\Livewire\Creditor\Reports\NewGenerateReport\IndexPage as GenerateReportPage;
use App\Models\Consumer;
use App\Models\ReportHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AllAccountStatusAndActivityTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Model::preventLazyLoading();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_can_export_all_consumers_status_and_activity_report_with_data_when_role_creditor(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        Consumer::factory(16)
            ->create([
                'company_id' => $this->user->company_id,
                'created_at' => now()->subDays(fake()->numberBetween(2, 20)),
            ]);

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.subclient_id', 'master')
            ->set('form.report_type', NewReportType::ALL_ACCOUNTS_STATUS_AND_ACTIVITY)
            ->set('form.start_date', $startDate = today()->subMonth()->toDateString())
            ->set('form.end_date', $endDate = today()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertFileDownloaded()
            ->assertOk();

        $timezone = $this->user->company->timezone->value;

        $this->assertDatabaseHas(ReportHistory::class, [
            'user_id' => $this->user->id,
            'report_type' => NewReportType::ALL_ACCOUNTS_STATUS_AND_ACTIVITY,
            'records' => 16,
            'status' => true,
            'start_date' => Carbon::parse($startDate, $timezone)->startOfDay()->utc()->toDateString(),
            'end_date' => Carbon::parse($endDate, $timezone)->endOfDay()->utc()->toDateString(),
        ]);
    }

    #[Test]
    public function it_can_store_the_consumer_payments_into_storage_when_role_creditor(): void
    {
        Storage::fake();

        Excel::fake();

        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        $occurConsumer = Consumer::factory(16)
            ->create([
                'company_id' => $this->user->company_id,
                'created_at' => now()->subDays(fake()->numberBetween(2, 20)),
            ])
            ->first();

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.subclient_id', 'master')
            ->set('form.report_type', NewReportType::ALL_ACCOUNTS_STATUS_AND_ACTIVITY)
            ->set('form.start_date', today()->subMonths(2)->toDateString())
            ->set('form.end_date', today()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertOk();

        $reportHistory = ReportHistory::query()->firstOrFail();

        Excel::assertStored(
            'download-report/' . Str::slug(NewReportType::ALL_ACCOUNTS_STATUS_AND_ACTIVITY->value) . '/' . $reportHistory->downloaded_file_name,
            fn (AllAccountStatusAndActivityExport $accountStatusAndActivityExport) => $accountStatusAndActivityExport->collection()->contains(fn ($consumer) => $consumer['beginning_balance'] === Number::currency((float) $occurConsumer->total_balance))
                && $accountStatusAndActivityExport->collection()->count() === 16
        );
    }

    #[Test]
    public function it_can_export_consumer_payments_data_when_role_super_admin(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        Consumer::factory(20)
            ->create([
                'company_id' => $this->user->company_id,
                'created_at' => now()->subDays(fake()->numberBetween(2, 20)),
            ]);

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.report_type', NewReportType::ALL_ACCOUNTS_STATUS_AND_ACTIVITY)
            ->set('form.start_date', $startDate = today()->subMonths(2)->toDateString())
            ->set('form.end_date', $endDate = today()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertFileDownloaded()
            ->assertOk();

        $this->assertDatabaseHas(ReportHistory::class, [
            'user_id' => $this->user->id,
            'report_type' => NewReportType::ALL_ACCOUNTS_STATUS_AND_ACTIVITY,
            'records' => 20,
            'start_date' => Carbon::parse($startDate, Timezone::EST->value)->startOfDay()->utc()->toDateString(),
            'end_date' => Carbon::parse($endDate, Timezone::EST->value)->endOfDay()->utc()->toDateString(),
            'status' => true,
        ]);
    }

    #[Test]
    public function it_can_store_the_consumer_payments_into_storage_when_role_super_admin(): void
    {
        Storage::fake();

        Excel::fake();

        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        $occurConsumer = Consumer::factory(12)
            ->create([
                'company_id' => $this->user->company_id,
                'created_at' => now()->subDays(fake()->numberBetween(2, 20)),
            ])
            ->first();

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.report_type', NewReportType::ALL_ACCOUNTS_STATUS_AND_ACTIVITY)
            ->set('form.start_date', today()->subMonths(2)->toDateString())
            ->set('form.end_date', today()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertOk();

        $reportHistory = ReportHistory::query()->firstOrFail();

        Excel::assertStored(
            'download-report/' . Str::slug(NewReportType::ALL_ACCOUNTS_STATUS_AND_ACTIVITY->value) . '/' . $reportHistory->downloaded_file_name,
            fn (AllAccountStatusAndActivityExport $accountStatusAndActivityExport) => $accountStatusAndActivityExport->collection()->contains(fn ($consumer) => $consumer['beginning_balance'] === Number::currency((float) $occurConsumer->total_balance))
                && $accountStatusAndActivityExport->collection()->count() === 12
        );
    }
}

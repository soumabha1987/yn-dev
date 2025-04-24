<?php

declare(strict_types=1);

namespace Tests\Feature\Reports\NewGenerateReport;

use App\Enums\ConsumerStatus;
use App\Enums\NewReportType;
use App\Enums\Role as EnumRole;
use App\Enums\Timezone;
use App\Exports\DisputeNoPayExport;
use App\Livewire\Creditor\Reports\NewGenerateReport\IndexPage as GenerateReportPage;
use App\Models\Consumer;
use App\Models\ReportHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DisputeNoPayTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Model::preventLazyLoading();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_can_export_dispute_and_no_pay_data_when_role_creditor(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        Consumer::factory(15)
            ->create([
                'company_id' => $this->user->company_id,
                'status' => fake()->randomElement([ConsumerStatus::DEACTIVATED, ConsumerStatus::DISPUTE, ConsumerStatus::NOT_PAYING]),
                'disputed_at' => today()->subDays(fake()->numberBetween(1, 50)),
            ]);

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.subclient_id', 'master')
            ->set('form.report_type', NewReportType::DISPUTE_NO_PAY)
            ->set('form.start_date', $startDate = today()->subMonths(2)->toDateString())
            ->set('form.end_date', $endDate = today()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertFileDownloaded()
            ->assertOk();

        $this->assertDatabaseHas(ReportHistory::class, [
            'user_id' => $this->user->id,
            'report_type' => NewReportType::DISPUTE_NO_PAY,
            'records' => 15,
            'start_date' => Carbon::parse($startDate, $this->user->company->timezone->value)->startOfDay()->utc()->toDateString(),
            'end_date' => Carbon::parse($endDate, $this->user->company->timezone->value)->endOfDay()->utc()->toDateString(),
            'status' => true,
        ]);
    }

    #[Test]
    public function it_can_store_dispute_and_no_pay_into_storage_when_role_creditor(): void
    {
        Storage::fake();

        Excel::fake();

        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        $occurConsumer = Consumer::factory(15)
            ->create([
                'company_id' => $this->user->company_id,
                'status' => fake()->randomElement([ConsumerStatus::DEACTIVATED, ConsumerStatus::DISPUTE, ConsumerStatus::NOT_PAYING]),
                'disputed_at' => today()->subDays(fake()->numberBetween(1, 50)),
            ])
            ->first();

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.subclient_id', 'master')
            ->set('form.report_type', NewReportType::DISPUTE_NO_PAY)
            ->set('form.start_date', today()->subMonths(2)->toDateString())
            ->set('form.end_date', today()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertOk();

        $reportHistory = ReportHistory::query()->firstOrFail();

        Excel::assertStored(
            'download-report/' . Str::slug(NewReportType::DISPUTE_NO_PAY->value) . '/' . $reportHistory->downloaded_file_name,
            fn (DisputeNoPayExport $disputeNoPayExport) => $disputeNoPayExport->collection()->contains(fn ($consumer) => $consumer['disputed_at'] === $occurConsumer->disputed_at->formatWithTimezone())
                && $disputeNoPayExport->collection()->count() === 15
        );
    }

    #[Test]
    public function it_can_export_consumer_payments_data_when_role_super_admin(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        Consumer::factory(10)
            ->create([
                'status' => fake()->randomElement([ConsumerStatus::DEACTIVATED, ConsumerStatus::DISPUTE, ConsumerStatus::NOT_PAYING]),
                'disputed_at' => today()->subDays(fake()->numberBetween(1, 50)),
            ]);

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.report_type', NewReportType::DISPUTE_NO_PAY)
            ->set('form.start_date', $startDate = today()->subMonths(2)->toDateString())
            ->set('form.end_date', $endDate = today()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertFileDownloaded()
            ->assertOk();

        $this->assertDatabaseHas(ReportHistory::class, [
            'user_id' => $this->user->id,
            'report_type' => NewReportType::DISPUTE_NO_PAY,
            'records' => 10,
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

        $occurConsumer = Consumer::factory(14)
            ->create([
                'status' => fake()->randomElement([ConsumerStatus::DEACTIVATED, ConsumerStatus::DISPUTE, ConsumerStatus::NOT_PAYING]),
                'disputed_at' => today()->subDays(fake()->numberBetween(1, 50)),
            ])
            ->first();

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.report_type', NewReportType::DISPUTE_NO_PAY)
            ->set('form.start_date', today()->subMonths(2)->toDateString())
            ->set('form.end_date', today()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertOk();

        $reportHistory = ReportHistory::query()->firstOrFail();

        Excel::assertStored(
            'download-report/' . Str::slug(NewReportType::DISPUTE_NO_PAY->value) . '/' . $reportHistory->downloaded_file_name,
            fn (DisputeNoPayExport $disputeNoPayExport) => $disputeNoPayExport->collection()->contains(fn ($consumer) => $consumer['disputed_at'] === $occurConsumer->disputed_at->formatWithTimezone())
                && $disputeNoPayExport->collection()->count() === 14
        );
    }
}

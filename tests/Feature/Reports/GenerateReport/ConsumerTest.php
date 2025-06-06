<?php

declare(strict_types=1);

namespace Tests\Feature\Reports\GenerateReport;

use App\Enums\ReportType;
use App\Enums\Role as EnumRole;
use App\Exports\ConsumersExport;
use App\Livewire\Creditor\Reports\GenerateReport\IndexPage as GenerateReportPage;
use App\Models\Consumer;
use App\Models\ReportHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConsumerTest extends TestCase
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
    public function it_can_export_consumers_when_role_creditor(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        Consumer::factory(5)
            ->for($this->user->company)
            ->for($this->user->subclient)
            ->sequence(fn (Sequence $sequence) => ['created_at' => today()->subDays($sequence->index + 2)])
            ->create();

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.subclient_id', 'master')
            ->set('form.report_type', ReportType::CONSUMERS)
            ->set('form.start_date', $startDate = today()->subMonths(2)->toDateString())
            ->set('form.end_date', $endDate = today()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertFileDownloaded()
            ->assertOk();

        $this->assertDatabaseHas(ReportHistory::class, [
            'user_id' => $this->user->id,
            'report_type' => ReportType::CONSUMERS,
            'records' => 5,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => true,
        ]);
    }

    #[Test]
    public function it_can_store_the_consumers_into_storage_when_role_creditor(): void
    {
        Storage::fake();

        Excel::fake();

        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        Consumer::factory(5)
            ->for($this->user->company)
            ->for($this->user->subclient)
            ->sequence(fn (Sequence $sequence) => ['created_at' => today()->subDays($sequence->index + 2)])
            ->create();

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.subclient_id', 'master')
            ->set('form.report_type', ReportType::CONSUMERS)
            ->set('form.start_date', today()->subMonths(2)->toDateString())
            ->set('form.end_date', today()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertOk();

        $reportHistory = ReportHistory::query()->firstOrFail();

        Excel::assertStored(
            'download-report/' . ReportType::CONSUMERS->value . '/' . $reportHistory->downloaded_file_name,
            fn (ConsumersExport $consumersExport) => $consumersExport->collection()->count() === 5
        );
    }

    #[Test]
    public function it_can_export_consumers_when_role_super_admin(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => ['created_at' => today()->subDays($sequence->index + 2)])
            ->create();

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.report_type', ReportType::CONSUMERS)
            ->set('form.start_date', $startDate = today()->subMonths(2)->toDateString())
            ->set('form.end_date', $endDate = today()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertFileDownloaded()
            ->assertOk();

        $this->assertDatabaseHas(ReportHistory::class, [
            'user_id' => $this->user->id,
            'report_type' => ReportType::CONSUMERS,
            'records' => 5,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => true,
        ]);
    }

    #[Test]
    public function it_can_store_the_consumers_into_storage_when_role_super_admin(): void
    {
        Storage::fake();

        Excel::fake();

        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => ['created_at' => today()->subDays($sequence->index + 2)])
            ->create();

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.report_type', ReportType::CONSUMERS)
            ->set('form.start_date', today()->subMonths(2)->toDateString())
            ->set('form.end_date', today()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertOk();

        $reportHistory = ReportHistory::query()->firstOrFail();

        Excel::assertStored(
            'download-report/' . ReportType::CONSUMERS->value . '/' . $reportHistory->downloaded_file_name,
            fn (ConsumersExport $consumersExport) => $consumersExport->collection()->count() === 5
        );
    }
}

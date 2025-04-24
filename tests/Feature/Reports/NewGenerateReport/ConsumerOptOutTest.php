<?php

declare(strict_types=1);

namespace Tests\Feature\Reports\NewGenerateReport;

use App\Enums\NewReportType;
use App\Enums\Role as EnumRole;
use App\Exports\ConsumerOptOutExport;
use App\Livewire\Creditor\Reports\NewGenerateReport\IndexPage as GenerateReportPage;
use App\Models\Consumer;
use App\Models\ConsumerProfile;
use App\Models\ReportHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConsumerOptOutTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Model::preventLazyLoading();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_can_export_consumer_opt_out_report_with_data_when_role_creditor(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        Consumer::factory(25)
            ->for(ConsumerProfile::factory()->create(['email_permission' => false]))
            ->create(['company_id' => $this->user->company_id]);

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.subclient_id', 'master')
            ->set('form.report_type', NewReportType::CONSUMER_OPT_OUT)
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertFileDownloaded()
            ->assertOk();

        $this->assertDatabaseHas(ReportHistory::class, [
            'user_id' => $this->user->id,
            'report_type' => NewReportType::CONSUMER_OPT_OUT,
            'records' => 25,
            'status' => true,
        ]);
    }

    #[Test]
    public function it_can_store_consumer_opt_out_into_storage_when_role_creditor(): void
    {
        Storage::fake();

        Excel::fake();

        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        Consumer::factory(20)
            ->for(ConsumerProfile::factory()->create([
                'email_permission' => false,
                'text_permission' => true,
            ]))
            ->create(['company_id' => $this->user->company_id]);

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.subclient_id', 'master')
            ->set('form.report_type', NewReportType::CONSUMER_OPT_OUT)
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertOk();

        $reportHistory = ReportHistory::query()->firstOrFail();

        Excel::assertStored(
            'download-report/' . Str::slug(NewReportType::CONSUMER_OPT_OUT->value) . '/' . $reportHistory->downloaded_file_name,
            fn (ConsumerOptOutExport $consumerOptOutExport) => $consumerOptOutExport->collection()->contains(fn ($consumer) => $consumer['type'] === __('email opt out'))
                && $consumerOptOutExport->collection()->count() === 20
        );
    }

    #[Test]
    public function it_can_export_consumer_opt_out_report_with_data_when_role_super_admin(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        Consumer::factory(10)
            ->for(ConsumerProfile::factory()->create(['email_permission' => false]))
            ->create();

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.report_type', NewReportType::CONSUMER_OPT_OUT)
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertFileDownloaded()
            ->assertOk();

        $this->assertDatabaseHas(ReportHistory::class, [
            'user_id' => $this->user->id,
            'report_type' => NewReportType::CONSUMER_OPT_OUT,
            'records' => 10,
            'status' => true,
        ]);
    }

    #[Test]
    public function it_can_store_the_consumer_opt_out_into_storage_when_role_super_admin(): void
    {
        Storage::fake();

        Excel::fake();

        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        Consumer::factory(18)
            ->for(ConsumerProfile::factory()->create([
                'email_permission' => false,
                'text_permission' => false,
            ]))
            ->create();

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.report_type', NewReportType::CONSUMER_OPT_OUT)
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertOk();

        $reportHistory = ReportHistory::query()->firstOrFail();

        Excel::assertStored(
            'download-report/' . Str::slug(NewReportType::CONSUMER_OPT_OUT->value) . '/' . $reportHistory->downloaded_file_name,
            fn (ConsumerOptOutExport $consumerOptOutExport) => $consumerOptOutExport->collection()->contains(fn ($consumer) => $consumer['type'] === __('email and mobile opt out'))
                && $consumerOptOutExport->collection()->count() === 18
        );
    }
}

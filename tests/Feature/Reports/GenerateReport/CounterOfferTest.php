<?php

declare(strict_types=1);

namespace Tests\Feature\Reports\GenerateReport;

use App\Enums\ConsumerStatus;
use App\Enums\ReportType;
use App\Enums\Role as EnumRole;
use App\Exports\CounterOffersExport;
use App\Livewire\Creditor\Reports\GenerateReport\IndexPage as GenerateReportPage;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\ReportHistory;
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

class CounterOfferTest extends TestCase
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

        ConsumerNegotiation::factory(10)
            ->sequence(fn (Sequence $sequence) => [
                'consumer_id' => Consumer::factory()
                    ->for($this->user->company)
                    ->for($this->user->subclient)
                    ->state([
                        'counter_offer' => true,
                        'status' => ConsumerStatus::JOINED->value,
                    ]),
                'created_at' => today()->subDays($sequence->index + 2),
            ])
            ->for($this->user->company)
            ->create([
                'active_negotiation' => true,
            ]);

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.subclient_id', 'master')
            ->set('form.report_type', ReportType::COUNTER_OFFERS)
            ->set('form.start_date', $startDate = today()->subMonths(2)->toDateString())
            ->set('form.end_date', $endDate = today()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertFileDownloaded()
            ->assertOk();

        $this->assertDatabaseHas(ReportHistory::class, [
            'user_id' => $this->user->id,
            'report_type' => ReportType::COUNTER_OFFERS,
            'records' => 10,
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

        ConsumerNegotiation::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'consumer_id' => Consumer::factory()
                    ->for($this->user->company)
                    ->for($this->user->subclient)
                    ->state([
                        'counter_offer' => true,
                        'status' => ConsumerStatus::JOINED->value,
                    ]),
                'created_at' => today()->subDays($sequence->index + 2),
            ])
            ->for($this->user->company)
            ->create([
                'active_negotiation' => true,
            ]);

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.subclient_id', 'master')
            ->set('form.report_type', ReportType::COUNTER_OFFERS)
            ->set('form.start_date', today()->subMonths(2)->toDateString())
            ->set('form.end_date', today()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertOk();

        $reportHistory = ReportHistory::query()->firstOrFail();

        Excel::assertStored(
            'download-report/' . Str::slug(ReportType::COUNTER_OFFERS->value) . '/' . $reportHistory->downloaded_file_name,
            fn (CounterOffersExport $counterOffersExport) => $counterOffersExport->collection()->count() === 5
        );
    }

    #[Test]
    public function it_can_export_consumers_when_role_super_admin(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        ConsumerNegotiation::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'consumer_id' => Consumer::factory()
                    ->for($this->user->company)
                    ->for($this->user->subclient)
                    ->state([
                        'counter_offer' => true,
                        'status' => ConsumerStatus::JOINED->value,
                    ]),
                'created_at' => today()->subDays($sequence->index + 2),
            ])
            ->create([
                'active_negotiation' => true,
            ]);

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.subclient_id', $this->user->subclient_id)
            ->set('form.report_type', ReportType::COUNTER_OFFERS)
            ->set('form.start_date', $startDate = today()->subMonths(2)->toDateString())
            ->set('form.end_date', $endDate = today()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertFileDownloaded()
            ->assertOk();

        $this->assertDatabaseHas(ReportHistory::class, [
            'user_id' => $this->user->id,
            'report_type' => ReportType::COUNTER_OFFERS,
            'records' => 5,
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

        ConsumerNegotiation::factory(15)
            ->sequence(fn (Sequence $sequence) => [
                'consumer_id' => Consumer::factory()
                    ->for($this->user->company)
                    ->for($this->user->subclient)
                    ->state([
                        'counter_offer' => true,
                        'status' => ConsumerStatus::JOINED->value,
                    ]),
                'created_at' => today()->subDays($sequence->index + 2),
            ])
            ->for($this->user->company)
            ->create([
                'active_negotiation' => true,
            ]);

        Livewire::actingAs($this->user)
            ->test(GenerateReportPage::class)
            ->set('form.subclient_id', null)
            ->set('form.report_type', ReportType::COUNTER_OFFERS)
            ->set('form.start_date', today()->subMonths(2)->toDateString())
            ->set('form.end_date', today()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertOk();

        $reportHistory = ReportHistory::query()->firstOrFail();

        Excel::assertStored(
            'download-report/' . Str::slug(ReportType::COUNTER_OFFERS->value) . '/' . $reportHistory->downloaded_file_name,
            fn (CounterOffersExport $counterOffersExport) => $counterOffersExport->collection()->count() === 15
        );
    }
}

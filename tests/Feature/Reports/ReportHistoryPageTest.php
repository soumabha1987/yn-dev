<?php

declare(strict_types=1);

namespace Tests\Feature\Reports;

use App\Enums\CompanyStatus;
use App\Enums\NewReportType;
use App\Enums\ReportHistoryStatus;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\Reports\ReportHistoryPage;
use App\Models\ReportHistory;
use App\Models\Subclient;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class ReportHistoryPageTest extends AuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);

        $this->user->company()->update(['status' => CompanyStatus::ACTIVE]);
    }

    #[Test]
    public function it_can_render_livewire_component_of_report_history_page(): void
    {
        $this->get(route('reports.history'))
            ->assertSeeLivewire(ReportHistoryPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_of_view(): void
    {
        $reportHistory = ReportHistory::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(ReportHistoryPage::class)
            ->assertViewIs('livewire.creditor.reports.report-history-page')
            ->assertViewHas('reportHistories', fn (LengthAwarePaginator $reportHistories) => $reportHistory->is($reportHistories->getCollection()->first()))
            ->assertSee($reportHistory->created_at->formatWithTimezone())
            ->assertSee($reportHistory->report_type->displayName())
            ->assertSee($reportHistory->records)
            ->assertSee($reportHistory->start_date->format('M d, Y'))
            ->assertSee($reportHistory->end_date->format('M d, Y'))
            ->assertOk();
    }

    #[Test]
    public function it_can_render_report_histories(): void
    {
        Storage::fake();

        $fileName = $this->user->id . '_' . date('Y_m_d_H_i_s') . '_' . Str::random(10) . '.csv';

        $reportHistory = ReportHistory::factory()->create([
            'user_id' => $this->user->id,
            'report_type' => NewReportType::ALL_ACCOUNTS_STATUS_AND_ACTIVITY,
            'status' => ReportHistoryStatus::SUCCESS,
            'downloaded_file_name' => $fileName,
        ]);

        UploadedFile::fake()->create($fileName)->storeAs('download-report/' . Str::slug($reportHistory->report_type->value) . '/' . $fileName);

        Livewire::test(ReportHistoryPage::class)
            ->call('downloadReport', $reportHistory)
            ->assertFileDownloaded($fileName)
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_created_on(string $direction): void
    {
        $createdReportHistories = ReportHistory::factory(10)
            ->sequence(fn (Sequence $sequence) => [
                'created_at' => now()->subDays($sequence->index + 2),
            ])
            ->create(['user_id' => $this->user->id]);

        Livewire::withQueryParams([
            'sort' => 'created-on',
            'direction' => $direction === 'ASC',
        ])
            ->test(ReportHistoryPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'created-on')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'reportHistories',
                fn (LengthAwarePaginator $reportHistories) => $direction === 'ASC'
                    ? $createdReportHistories->last()->is($reportHistories->getCollection()->first())
                    : $createdReportHistories->first()->is($reportHistories->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_name(string $direction): void
    {
        $createdReportHistories = ReportHistory::factory()
            ->forEachSequence(
                ['report_type' => NewReportType::ALL_ACCOUNTS_STATUS_AND_ACTIVITY],
                ['report_type' => NewReportType::CONSUMER_OPT_OUT],
                ['report_type' => NewReportType::DISPUTE_NO_PAY],
                ['report_type' => NewReportType::SUMMARY_BALANCE_COMPLIANCE],
            )
            ->create(['user_id' => $this->user->id]);

        Livewire::withQueryParams([
            'sort' => 'name',
            'direction' => $direction === 'ASC',
        ])
            ->test(ReportHistoryPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'reportHistories',
                fn (LengthAwarePaginator $reportHistories) => $direction === 'ASC'
                    ? $createdReportHistories->first()->is($reportHistories->getCollection()->first())
                    : $createdReportHistories->last()->is($reportHistories->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_records(string $direction): void
    {
        $createdReportHistories = ReportHistory::factory(10)
            ->sequence(fn (Sequence $sequence) => [
                'records' => $sequence->index + 2,
            ])
            ->create(['user_id' => $this->user->id]);

        Livewire::withQueryParams([
            'sort' => 'records',
            'direction' => $direction === 'ASC',
        ])
            ->test(ReportHistoryPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'records')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'reportHistories',
                fn (LengthAwarePaginator $reportHistories) => $direction === 'ASC'
                    ? $createdReportHistories->first()->is($reportHistories->getCollection()->first())
                    : $createdReportHistories->last()->is($reportHistories->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_start_date(string $direction): void
    {
        $createdReportHistories = ReportHistory::factory(10)
            ->sequence(fn (Sequence $sequence) => [
                'start_date' => today()->subDays($sequence->index + 3),
            ])
            ->create(['user_id' => $this->user->id]);

        Livewire::withQueryParams([
            'sort' => 'start-date',
            'direction' => $direction === 'ASC',
        ])
            ->test(ReportHistoryPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'start-date')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'reportHistories',
                fn (LengthAwarePaginator $reportHistories) => $direction === 'ASC'
                    ? $createdReportHistories->last()->is($reportHistories->getCollection()->first())
                    : $createdReportHistories->first()->is($reportHistories->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_end_date(string $direction): void
    {
        $createdReportHistories = ReportHistory::factory(10)
            ->sequence(fn (Sequence $sequence) => [
                'end_date' => today()->subDays($sequence->index + 3),
            ])
            ->create(['user_id' => $this->user->id]);

        Livewire::withQueryParams([
            'sort' => 'end-date',
            'direction' => $direction === 'ASC',
        ])
            ->test(ReportHistoryPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'end-date')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'reportHistories',
                fn (LengthAwarePaginator $reportHistories) => $direction === 'ASC'
                    ? $createdReportHistories->last()->is($reportHistories->getCollection()->first())
                    : $createdReportHistories->first()->is($reportHistories->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_account_in_scope(string $direction): void
    {
        $createdReportHistories = ReportHistory::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'subclient_id' => Subclient::factory()->state([
                    'subclient_name' => range('A', 'Z')[$sequence->index],
                ]),
            ])
            ->create(['user_id' => $this->user->id]);

        $withoutSubclientReportHistory = ReportHistory::factory()
            ->create([
                'user_id' => $this->user->id,
                'subclient_id' => null,
            ]);

        Livewire::withQueryParams([
            'sort' => 'account-in-scope',
            'direction' => $direction === 'ASC',
        ])
            ->test(ReportHistoryPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'account-in-scope')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'reportHistories',
                fn (LengthAwarePaginator $reportHistories) => $direction === 'ASC'
                    ? $createdReportHistories->first()->isNot($reportHistories->getCollection()->first())
                            && $withoutSubclientReportHistory->is($reportHistories->getCollection()->first())
                    : $createdReportHistories->last()->is($reportHistories->getCollection()->first())
            );
    }

    public static function sortDirection(): array
    {
        return [
            ['ASC'],
            ['DESC'],
        ];
    }
}

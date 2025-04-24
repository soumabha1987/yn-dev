<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Reports\GenerateReport;

use App\Enums\ReportType;
use App\Enums\Role;
use App\Livewire\Creditor\Forms\GenerateReportForm;
use App\Livewire\Creditor\Traits\GenerateReports;
use App\Models\User;
use App\Services\CompanyService;
use App\Services\ConsumerService;
use App\Services\SubclientService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IndexPage extends Component
{
    use GenerateReports;

    public GenerateReportForm $form;

    public array $reportTypes = [];

    public array $validatedData = [];

    public string $reportType = '';

    protected ConsumerService $consumerService;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
        $this->consumerService = app(ConsumerService::class);
    }

    public function mount(): void
    {
        $excludedReportTypes = collect([
            ReportType::CONSUMER_ACTIVITIES->value,
            ReportType::UPCOMING_TRANSACTIONS->value,
            ReportType::RECENT_TRANSACTIONS->value,
        ])
            ->when(
                $this->user->hasRole(Role::CREDITOR),
                fn (Collection $excludedReportTypes) => $excludedReportTypes->push(ReportType::BILLING_HISTORIES->value)
            )
            ->toArray();

        $this->reportTypes = collect(ReportType::displaySelectionBox())
            ->except($excludedReportTypes)
            ->sort()
            ->all();
    }

    public function generateReport(): ?StreamedResponse
    {
        $validatedData = $this->form->validate();

        $filename = match ($validatedData['report_type']) {
            ReportType::TRANSACTION_HISTORY->value => $this->transactionHistoriesReport($this->setup($validatedData)),
            ReportType::SCHEDULED_TRANSACTIONS->value => $this->scheduleTransactionsReport($this->setup($validatedData)),
            ReportType::CONSUMERS->value => $this->consumersReport($this->setup($validatedData)),
            ReportType::PROFILE_PERMISSIONS->value => $this->profilePermissionsReport($this->setup($validatedData)),
            ReportType::COUNTER_OFFERS->value => $this->counterOffersReport($this->setup($validatedData)),
            ReportType::DEACTIVATED_AND_DISPUTE_CONSUMERS->value => $this->deactivatedAndDisputeConsumersReport($this->setup($validatedData)),
            ReportType::BILLING_HISTORIES->value => $this->billingHistoriesReport($this->setup($validatedData)),
            default => $this->reportType,
        };

        if ($filename !== null && Storage::exists($filename)) {
            return Storage::download($filename);
        }

        return null;
    }

    private function setup(array $data): array
    {
        $data['subclient_id'] = $data['subclient_id'] !== 'master' ? $data['subclient_id'] : null;

        return [
            'company_id' => $this->user->hasRole(Role::SUPERADMIN) ? $data['company_id'] : $this->user->company_id,
            'subclient_id' => match (true) {
                $this->user->hasRole(Role::CREDITOR) => $data['subclient_id'] ?? null,
                default => null,
            },
            'start_date' => Carbon::parse($data['start_date'])->startOfDay()->toDateString(),
            'end_date' => Carbon::parse($data['end_date'])->endOfDay()->toDateString(),
        ];
    }

    public function render(): View
    {
        $subAccounts = $this->user->hasRole(Role::SUPERADMIN)
            ? app(CompanyService::class)->fetchForSelectionBox()
            : app(SubclientService::class)->fetchForGenerateReportSelectionBox($this->user->company_id);

        return view('livewire.creditor.reports.generate-report.index-page')
            ->with('subAccounts', $subAccounts)
            ->title(__('Create A Report'));
    }
}

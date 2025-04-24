<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Reports\NewGenerateReport;

use App\Enums\NewReportType;
use App\Enums\Role;
use App\Enums\Timezone;
use App\Livewire\Creditor\Forms\NewGenerateReportForm;
use App\Livewire\Creditor\Traits\NewGenerateReports;
use App\Models\User;
use App\Services\CompanyService;
use App\Services\ConsumerService;
use App\Services\SubclientService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IndexPage extends Component
{
    use NewGenerateReports;

    public NewGenerateReportForm $form;

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
        $this->reportTypes = collect(NewReportType::displaySelectionBox())
            ->when(
                $this->user->hasRole(Role::CREDITOR),
                fn (Collection $collection) => $collection->except(NewReportType::BILLING_HISTORIES->value)
            )
            ->sort()
            ->all();
    }

    public function generateReport(): ?StreamedResponse
    {
        $validatedData = $this->form->validate();

        $filename = match ($validatedData['report_type']) {
            NewReportType::BILLING_HISTORIES->value => $this->billingHistoriesReport($this->setup($validatedData)),
            NewReportType::ALL_ACCOUNTS_STATUS_AND_ACTIVITY->value => $this->allAccountStatusAndActivityReport($this->setup($validatedData)),
            NewReportType::CONSUMER_PAYMENTS->value => $this->consumerPaymentsReport($this->setup($validatedData)),
            NewReportType::DISPUTE_NO_PAY->value => $this->disputeNoPayReport($this->setup($validatedData)),
            NewReportType::CONSUMER_OPT_OUT->value => $this->consumerOptOutReport($this->setup($validatedData)),
            NewReportType::FINAL_PAYMENTS_BALANCE_SUMMARY->value => $this->finalPaymentsBalanceSummaryReport($this->setup($validatedData)),
            NewReportType::SUMMARY_BALANCE_COMPLIANCE->value => $this->summaryBalanceComplianceReport($this->setup($validatedData)),
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

        $timezone = Timezone::EST->value;

        if ($this->user->company) {
            $timezone = $this->user->company->timezone->value;
        }

        return [
            'company_id' => $this->user->hasRole(Role::SUPERADMIN) ? $data['company_id'] : $this->user->company_id,
            'subclient_id' => match (true) {
                $this->user->hasRole(Role::CREDITOR) => $data['subclient_id'] ?? null,
                default => null,
            },
            'start_date' => Carbon::parse($data['start_date'], $timezone)->startOfDay()->utc()->toDateString(),
            'end_date' => Carbon::parse($data['end_date'], $timezone)->endOfDay()->utc()->toDateString(),
        ];
    }

    public function render(): View
    {
        $subAccounts = $this->user->hasRole(Role::SUPERADMIN)
            ? app(CompanyService::class)->fetchForSelectionBox()
            : app(SubclientService::class)->fetchForGenerateReportSelectionBox($this->user->company_id);

        return view('livewire.creditor.reports.new-generate-report.index-page')
            ->with('subAccounts', $subAccounts)
            ->title(__('Create A Report'));
    }
}

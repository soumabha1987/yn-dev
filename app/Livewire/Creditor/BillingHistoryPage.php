<?php

declare(strict_types=1);

namespace App\Livewire\Creditor;

use App\Enums\CompanyMembershipStatus;
use App\Enums\MembershipFrequency;
use App\Enums\MembershipTransactionStatus;
use App\Enums\ReportType;
use App\Exports\CreditorBillingHistoriesExport;
use App\Livewire\Traits\WithPagination;
use App\Models\MembershipTransaction;
use App\Models\User;
use App\Models\YnTransaction;
use App\Services\CompanyMembershipService;
use App\Services\MembershipPaymentProfileService;
use App\Services\MembershipTransactionService;
use App\Services\PartnerService;
use App\Services\TilledPaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BillingHistoryPage extends Component
{
    use WithPagination;

    public bool $isPlanExpire = false;

    protected MembershipTransactionService $membershipTransactionService;

    private User $user;

    public function __construct()
    {
        $this->membershipTransactionService = app(MembershipTransactionService::class);
        $this->user = Auth::user();
    }

    public function mount(): void
    {
        $this->user->loadMissing('company.activeCompanyMembership');

        $this->isPlanExpire = blank($this->user->company->activeCompanyMembership);
    }

    public function downloadInvoice(int $id, string $type): ?StreamedResponse
    {
        $pdf = $type === 'yn' ? $this->downloadYnInvoice($id) : $this->downloadMembershipInvoice($id);

        if ($pdf) {
            $this->dispatch('close-menu');

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf;
            }, $type . '_' . $id . '_you_negotiate_invoice.pdf');
        }

        return null;
    }

    private function downloadMembershipInvoice(int $membershipTransactionId): ?string
    {
        $membershipTransaction = MembershipTransaction::query()
            ->with('company', 'membership')
            ->find($membershipTransactionId);

        if (blank($membershipTransaction)) {
            $this->error('Sorry, this transaction does not exists');

            $this->dispatch('close-menu');

            return null;
        }

        return Pdf::setOption('isRemoteEnabled', true)
            ->loadView('pdf.creditor.membership-transaction-invoice', [
                'membershipTransaction' => $membershipTransaction,
            ])
            ->output();
    }

    private function downloadYnInvoice(int $ynTransactionId): ?string
    {
        $ynTransaction = YnTransaction::query()
            ->with('company', 'transactions', 'scheduleTransaction')
            ->find($ynTransactionId);

        if (blank($ynTransaction)) {
            $this->error('Sorry, this transaction does not exists');

            $this->dispatch('close-menu');

            return null;
        }

        return Pdf::setOption('isRemoteEnabled', true)
            ->loadView('pdf.creditor.yn-transaction-invoice', [
                'ynTransaction' => $ynTransaction,
            ])
            ->output();
    }

    public function export(): StreamedResponse
    {
        $billingHistories = $this->membershipTransactionService->exportBillingHistory($this->setUp());

        $downloadFilename = $this->user->id . '_' . now()->format('Y_m_d_H_i_s') . '_' . Str::random(10) . '.csv';

        $filename = 'download-report/' . Str::slug(ReportType::BILLING_HISTORIES->value) . '/' . $downloadFilename;

        Excel::store(
            new CreditorBillingHistoriesExport($billingHistories),
            $filename,
            writerType: ExcelExcel::CSV
        );

        return Storage::download($filename);
    }

    public function reprocess(MembershipTransaction $membershipTransaction): void
    {
        if (! $this->isPlanExpire) {
            $this->error(__('Great news, this payment was not processed. No payment due at this time.'));

            $this->dispatch('close-menu');

            return;
        }

        $membershipTransaction->loadMissing('membership');

        $membership = $membershipTransaction->membership;

        if ($membership->deleted_at !== null || ! $membership->status) {
            $this->error(__('Delete this card and ALWAYS allow the creditor member to reprocess a failed payment no matter the failed date or plan updates or changes'));

            $this->dispatch('close-menu');

            return;
        }

        $membershipPaymentProfile = app(MembershipPaymentProfileService::class)
            ->fetchByCompany($this->user->company_id);

        if (! $membershipPaymentProfile) {
            $this->error(__('Payment method not found, please contact help@younegotiate.com'));

            $this->dispatch('close-menu');

            return;
        }

        $response = app(TilledPaymentService::class)
            ->createPaymentIntents((int) ($membershipTransaction->price * 100), $membershipPaymentProfile->tilled_payment_method_id);

        $transactionStatus = optional($response)['status'];

        $membershipTransactionStatus = (! $transactionStatus || ! in_array($transactionStatus, ['processing', 'succeeded']))
            ? MembershipTransactionStatus::FAILED->value
            : MembershipTransactionStatus::SUCCESS->value;

        $companyMembership = app(CompanyMembershipService::class)->latestPlanEndDate($this->user->company_id);

        $planEndDate = match ($membership->frequency) {
            MembershipFrequency::WEEKLY => $companyMembership->current_plan_end->addWeek(),
            MembershipFrequency::MONTHLY => $companyMembership->current_plan_end->addMonthNoOverflow(),
            MembershipFrequency::YEARLY => $companyMembership->current_plan_end->addYear(),
        };

        $partnerRevenueShare = 0;

        if ($this->user->company->partner_id) {
            $partnerRevenueShare = app(PartnerService::class)
                ->calculatePartnerRevenueShare($this->user->company->partner, $membershipTransaction->price);
        }

        MembershipTransaction::query()
            ->create([
                'company_id' => $this->user->company_id,
                'membership_id' => $membership->id,
                'status' => $membershipTransactionStatus,
                'price' => $membershipTransaction->price,
                'tilled_transaction_id' => $response['id'] ?? null,
                'response' => $response,
                'plan_end_date' => $planEndDate,
                'partner_revenue_share' => $partnerRevenueShare,
            ]);

        if ($membershipTransactionStatus === MembershipTransactionStatus::SUCCESS->value) {
            $companyMembership->update([
                'current_plan_start' => $companyMembership->current_plan_end,
                'current_plan_end' => $planEndDate,
                'auto_renew' => true,
                'status' => $planEndDate->gte(today()) ? CompanyMembershipStatus::ACTIVE : CompanyMembershipStatus::INACTIVE,
            ]);

            $this->success('Congratulation your failed transaction renew successfully');

            $this->dispatch('close-menu');

            return;
        }

        $this->error('Sorry your transaction payment still failed, please try again');

        $this->dispatch('close-menu');
    }

    public function setUp(): array
    {
        return [
            'company_id' => $this->user->company_id,
            'is_plan_expire' => $this->isPlanExpire,
        ];
    }

    public function render(): View
    {
        return view('livewire.creditor.billing-history-page')
            ->with('billingHistories', $this->membershipTransactionService->fetchBillingHistory([...$this->setUp(), 'per_page' => $this->perPage]))
            ->title(__('Billing History'));
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Traits;

use App\Enums\NewReportType;
use App\Enums\ReportHistoryStatus;
use App\Exports\AllAccountStatusAndActivityExport;
use App\Exports\BillingHistoriesExport;
use App\Exports\ConsumerOptOutExport;
use App\Exports\ConsumerPaymentsExport;
use App\Exports\DisputeNoPayExport;
use App\Exports\finalPaymentsBalanceSummaryExport;
use App\Exports\SummaryBalanceComplianceExport;
use App\Models\ReportHistory;
use App\Services\MembershipTransactionService;
use App\Services\TransactionService;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;

trait NewGenerateReports
{
    private function billingHistoriesReport(array $data): ?string
    {
        $membershipTransactions = app(MembershipTransactionService::class)->generateReports($data);

        $data['count'] = $membershipTransactions->count();

        if ($data['count'] === 0) {
            $this->error(__('Sorry! The downloaded report is empty.'));

            return null;
        }

        [$filename, $downloadFilename] = $this->getFileName(NewReportType::BILLING_HISTORIES);

        Excel::store(
            new BillingHistoriesExport($membershipTransactions),
            $filename,
            writerType: ExcelExcel::CSV
        );

        $this->createReportHistory($data, NewReportType::BILLING_HISTORIES, $downloadFilename);

        return $filename;
    }

    private function allAccountStatusAndActivityReport(array $data): ?string
    {
        $consumers = $this->consumerService->reportAllAccountStatusAndActivity($data);

        $data['count'] = $consumers->count();

        if ($data['count'] === 0) {
            $this->error(__('Sorry! The downloaded report is empty.'));

            return null;
        }

        [$filename, $downloadFilename] = $this->getFileName(NewReportType::ALL_ACCOUNTS_STATUS_AND_ACTIVITY);

        Excel::store(
            new AllAccountStatusAndActivityExport($consumers),
            $filename,
            writerType: ExcelExcel::CSV
        );

        $this->createReportHistory($data, NewReportType::ALL_ACCOUNTS_STATUS_AND_ACTIVITY, $downloadFilename);

        return $filename;
    }

    private function consumerPaymentsReport(array $data): ?string
    {
        $transactions = app(TransactionService::class)->getConsumerPaymentsReport($data);

        $data['count'] = $transactions->count();

        if ($data['count'] === 0) {
            $this->error(__('Sorry! The downloaded report is empty.'));

            return null;
        }

        [$filename, $downloadFilename] = $this->getFileName(NewReportType::CONSUMER_PAYMENTS);

        Excel::store(
            new ConsumerPaymentsExport($transactions),
            $filename,
            writerType: ExcelExcel::CSV
        );

        $this->createReportHistory($data, NewReportType::CONSUMER_PAYMENTS, $downloadFilename);

        return $filename;
    }

    private function disputeNoPayReport(array $data): ?string
    {
        $consumers = $this->consumerService->reportDisputeNoPay($data);

        $data['count'] = $consumers->count();

        if ($data['count'] === 0) {
            $this->error(__('Sorry! The downloaded report is empty.'));

            return null;
        }

        [$filename, $downloadFilename] = $this->getFileName(NewReportType::DISPUTE_NO_PAY);

        Excel::store(
            new DisputeNoPayExport($consumers),
            $filename,
            writerType: ExcelExcel::CSV
        );

        $this->createReportHistory($data, NewReportType::DISPUTE_NO_PAY, $downloadFilename);

        return $filename;
    }

    private function consumerOptOutReport(array $data): ?string
    {
        $consumers = $this->consumerService->reportConsumerOptOut($data);

        $data['count'] = $consumers->count();

        if ($data['count'] === 0) {
            $this->error(__('Sorry! The downloaded report is empty.'));

            return null;
        }

        [$filename, $downloadFilename] = $this->getFileName(NewReportType::CONSUMER_OPT_OUT);

        Excel::store(
            new ConsumerOptOutExport($consumers),
            $filename,
            writerType: ExcelExcel::CSV
        );

        $this->createReportHistory($data, NewReportType::CONSUMER_OPT_OUT, $downloadFilename);

        return $filename;
    }

    private function finalPaymentsBalanceSummaryReport(array $data): ?string
    {
        $consumers = $this->consumerService->reportFinalPaymentsBalanceSummary($data);

        $data['count'] = $consumers->count();

        if ($data['count'] === 0) {
            $this->error(__('Sorry! The downloaded report is empty.'));

            return null;
        }

        [$filename, $downloadFilename] = $this->getFileName(NewReportType::FINAL_PAYMENTS_BALANCE_SUMMARY);

        Excel::store(
            new finalPaymentsBalanceSummaryExport($consumers),
            $filename,
            writerType: ExcelExcel::CSV
        );

        $this->createReportHistory($data, NewReportType::FINAL_PAYMENTS_BALANCE_SUMMARY, $downloadFilename);

        return $filename;
    }

    public function summaryBalanceComplianceReport(array $data): ?string
    {
        $consumers = $this->consumerService->reportSummaryBalanceCompliance($data);

        $data['count'] = $consumers->count();

        if ($data['count'] === 0) {
            $this->error(__('Sorry! The downloaded report is empty.'));

            return null;
        }

        [$filename, $downloadFilename] = $this->getFileName(NewReportType::SUMMARY_BALANCE_COMPLIANCE);

        Excel::store(
            new SummaryBalanceComplianceExport($consumers),
            $filename,
            writerType: ExcelExcel::CSV
        );

        $this->createReportHistory($data, NewReportType::SUMMARY_BALANCE_COMPLIANCE, $downloadFilename);

        return $filename;
    }

    private function getFileName(NewReportType $updatedReportType): array
    {
        $downloadFilename = $this->user->id . '_' . now()->format('Y_m_d_H_i_s') . '_' . Str::random(10) . '.csv';

        $filename = 'download-report/' . Str::slug($updatedReportType->value) . '/' . $downloadFilename;

        return [$filename, $downloadFilename];
    }

    private function createReportHistory(array $data, NewReportType $reportType, string $downloadFilename): void
    {
        ReportHistory::query()->create([
            'user_id' => $this->user->id,
            'subclient_id' => $data['subclient_id'],
            'report_type' => $reportType,
            'status' => ReportHistoryStatus::SUCCESS,
            'records' => $data['count'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'downloaded_file_name' => $downloadFilename,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Traits;

use App\Enums\ReportHistoryStatus;
use App\Enums\ReportType;
use App\Exports\BillingHistoriesExport;
use App\Exports\ConsumersExport;
use App\Exports\ConsumersWithConsumerProfileExport;
use App\Exports\CounterOffersExport;
use App\Exports\DeactivatedAndDisputeConsumersExport;
use App\Exports\ScheduleTransactionExport;
use App\Exports\TransactionHistoryExport;
use App\Models\ReportHistory;
use App\Services\MembershipTransactionService;
use App\Services\ScheduleTransactionService;
use App\Services\TransactionService;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;

trait GenerateReports
{
    private function consumersReport(array $data): ?string
    {
        $consumers = $this->consumerService->generateReports($data);

        $data['count'] = $consumers->count();

        if ($data['count'] === 0) {
            $this->error(__('Sorry! The downloaded report is empty.'));

            return null;
        }

        [$filename, $downloadFilename] = $this->getFileName(ReportType::CONSUMERS);

        Excel::store(
            new ConsumersExport($consumers, $this->user),
            $filename,
            writerType: ExcelExcel::CSV
        );

        $this->createReportHistory($data, ReportType::CONSUMERS, $downloadFilename);

        return $filename;
    }

    private function transactionHistoriesReport(array $data): ?string
    {
        $transactions = app(TransactionService::class)->getTransactionReports($data);

        $data['count'] = $transactions->count();

        if ($data['count'] === 0) {
            $this->error(__('Sorry! The downloaded report is empty.'));

            return null;
        }

        [$filename, $downloadFilename] = $this->getFileName(ReportType::TRANSACTION_HISTORY);

        Excel::store(
            new TransactionHistoryExport($transactions),
            $filename,
            writerType: ExcelExcel::CSV
        );

        $this->createReportHistory($data, ReportType::TRANSACTION_HISTORY, $downloadFilename);

        return $filename;
    }

    private function scheduleTransactionsReport(array $data): ?string
    {
        $scheduleTransactions = app(ScheduleTransactionService::class)->getGenerateReports($data);

        $data['count'] = $scheduleTransactions->count();

        if ($data['count'] === 0) {
            $this->error(__('Sorry! The downloaded report is empty.'));

            return null;
        }

        [$filename, $downloadFilename] = $this->getFileName(ReportType::SCHEDULED_TRANSACTIONS);

        Excel::store(
            new ScheduleTransactionExport($scheduleTransactions),
            $filename,
            writerType: ExcelExcel::CSV
        );

        $this->createReportHistory($data, ReportType::SCHEDULED_TRANSACTIONS, $downloadFilename);

        return $filename;
    }

    private function profilePermissionsReport(array $data): ?string
    {
        $consumerWithProfilePermissions = $this->consumerService->reportProfilePermission($data);

        $data['count'] = $consumerWithProfilePermissions->count();

        if ($data['count'] === 0) {
            $this->error(__('Sorry! The downloaded report is empty.'));

            return null;
        }

        [$filename, $downloadFilename] = $this->getFileName(ReportType::PROFILE_PERMISSIONS);

        Excel::store(
            new ConsumersWithConsumerProfileExport($consumerWithProfilePermissions),
            $filename,
            writerType: ExcelExcel::CSV
        );

        $this->createReportHistory($data, ReportType::PROFILE_PERMISSIONS, $downloadFilename);

        return $filename;
    }

    private function counterOffersReport(array $data): ?string
    {
        $counterOffers = $this->consumerService->reportOfCounterOffers($data);

        $data['count'] = $counterOffers->count();

        if ($data['count'] === 0) {
            $this->error(__('Sorry! The downloaded report is empty.'));

            return null;
        }

        [$filename, $downloadFilename] = $this->getFileName(ReportType::COUNTER_OFFERS);

        Excel::store(
            new CounterOffersExport($counterOffers, $this->user),
            $filename,
            writerType: ExcelExcel::CSV
        );

        $this->createReportHistory($data, ReportType::COUNTER_OFFERS, $downloadFilename);

        return $filename;
    }

    private function deactivatedAndDisputeConsumersReport(array $data): ?string
    {
        $deactivatedAndDisputeConsumers = $this->consumerService->reportOfDeactivatedAndDispute($data);

        $data['count'] = $deactivatedAndDisputeConsumers->count();

        if ($data['count'] === 0) {
            $this->error(__('Sorry! The downloaded report is empty.'));

            return null;
        }

        [$filename, $downloadFilename] = $this->getFileName(ReportType::DEACTIVATED_AND_DISPUTE_CONSUMERS);

        Excel::store(
            new DeactivatedAndDisputeConsumersExport($deactivatedAndDisputeConsumers, $this->user),
            $filename,
            writerType: ExcelExcel::CSV
        );

        $this->createReportHistory($data, ReportType::DEACTIVATED_AND_DISPUTE_CONSUMERS, $downloadFilename);

        return $filename;
    }

    private function billingHistoriesReport(array $data): ?string
    {
        $membershipTransactions = app(MembershipTransactionService::class)->generateReports($data);

        $data['count'] = $membershipTransactions->count();

        if ($data['count'] === 0) {
            $this->error(__('Sorry! The downloaded report is empty.'));

            return null;
        }

        [$filename, $downloadFilename] = $this->getFileName(ReportType::BILLING_HISTORIES);

        Excel::store(
            new BillingHistoriesExport($membershipTransactions),
            $filename,
            writerType: ExcelExcel::CSV
        );

        $this->createReportHistory($data, ReportType::BILLING_HISTORIES, $downloadFilename);

        return $filename;
    }

    private function getFileName(ReportType $reportType): array
    {
        $downloadFilename = $this->user->id . '_' . now()->format('Y_m_d_H_i_s') . '_' . Str::random(10) . '.csv';

        $filename = 'download-report/' . Str::slug($reportType->value) . '/' . $downloadFilename;

        return [$filename, $downloadFilename];
    }

    private function createReportHistory(array $data, ReportType $reportType, string $downloadFilename): void
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

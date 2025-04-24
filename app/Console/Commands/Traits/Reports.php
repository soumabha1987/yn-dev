<?php

declare(strict_types=1);

namespace App\Console\Commands\Traits;

use App\Enums\NewReportType;
use App\Enums\ScheduleExportFrequency;
use App\Enums\Timezone;
use App\Exports\AllAccountStatusAndActivityExport;
use App\Exports\BillingHistoriesExport;
use App\Exports\ConsumerMappedHeaderExport;
use App\Exports\ConsumerOptOutExport;
use App\Exports\ConsumerPaymentsExport;
use App\Exports\DisputeNoPayExport;
use App\Exports\finalPaymentsBalanceSummaryExport;
use App\Exports\SummaryBalanceComplianceExport;
use App\Jobs\DeleteScheduleExportFileJob;
use App\Jobs\PutScheduleExportOnSftpJob;
use App\Jobs\SendScheduleExportEmailJob;
use App\Models\ScheduleExport;
use App\Services\ConsumerService;
use App\Services\MembershipTransactionService;
use App\Services\ScheduleExportService;
use App\Services\TransactionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @property Carbon $from
 * @property Carbon $to
 */
trait Reports
{
    protected string $search = '';

    protected ConsumerService $consumerService;

    protected ScheduleExportService $scheduleExportService;

    protected MembershipTransactionService $membershipTransactionService;

    protected TransactionService $transactionService;

    protected function scheduleExport(ScheduleExportFrequency $scheduleExportFrequency): void
    {
        $this->consumerService = app(ConsumerService::class);
        $this->scheduleExportService = app(ScheduleExportService::class);
        $this->membershipTransactionService = app(MembershipTransactionService::class);
        $this->transactionService = app(TransactionService::class);

        $this->scheduleExportService
            ->fetchByFrequency($scheduleExportFrequency)
            ->filter($this->filterByTiming(...))
            ->each(function (ScheduleExport $scheduleExport): void {
                [$isItStored, $filename] = $this->generateReport($scheduleExport);

                if ($isItStored) {
                    $job = $scheduleExport->sftp_connection_id === null
                        ? new SendScheduleExportEmailJob($scheduleExport, $filename)
                        : new PutScheduleExportOnSftpJob($scheduleExport, $filename);

                    Bus::chain([$job, new DeleteScheduleExportFileJob($filename)])->dispatch();
                }
            });
    }

    protected function filterByTiming(ScheduleExport $scheduleExport): bool
    {
        return match ($scheduleExport->frequency) {
            ScheduleExportFrequency::DAILY => $scheduleExport->last_sent_at === null || $scheduleExport->last_sent_at->addHours(20)->lt(now()),
            ScheduleExportFrequency::WEEKLY => $scheduleExport->last_sent_at === null || $scheduleExport->last_sent_at->addDays(5)->lt(now()),
            ScheduleExportFrequency::MONTHLY => $scheduleExport->last_sent_at === null || $scheduleExport->last_sent_at->addWeeks(3)->lt(now()),
        };
    }

    /**
     * @return array<int, bool|null|string>
     */
    protected function generateReport(ScheduleExport $scheduleExport): array
    {
        return match ($scheduleExport->report_type) {
            NewReportType::BILLING_HISTORIES => $this->billingHistories($scheduleExport),
            NewReportType::ALL_ACCOUNTS_STATUS_AND_ACTIVITY => $this->allAccountStatusAndActivity($scheduleExport),
            NewReportType::CONSUMER_PAYMENTS => $this->consumerPayments($scheduleExport),
            NewReportType::DISPUTE_NO_PAY => $this->disputeNoPay($scheduleExport),
            NewReportType::CONSUMER_OPT_OUT => $this->consumerOptOutReport($scheduleExport),
            NewReportType::FINAL_PAYMENTS_BALANCE_SUMMARY => $this->finalPaymentsBalanceSummary($scheduleExport),
            NewReportType::SUMMARY_BALANCE_COMPLIANCE => $this->summaryBalanceCompliance($scheduleExport),
        };
    }

    /**
     * @return array{
     *    start_date: string,
     *    end_date: string,
     * }
     */
    protected function changeTimezone(ScheduleExport $scheduleExport): array
    {
        $timezone = Timezone::EST->value;

        if ($scheduleExport->company_id) {
            $timezone = filled($scheduleExport->company->timezone) ? $scheduleExport->company->timezone->value : Timezone::EST->value;
        }

        return [
            'start_date' => $this->from->timezone($timezone)->startOfDay()->utc()->toDateTimeString(),
            'end_date' => $this->to->timezone($timezone)->endOfDay()->utc()->toDateTimeString(),
        ];
    }

    protected function usingCsvHeader(ScheduleExport $scheduleExport, Collection $data, string $filename): array
    {
        return [
            Excel::store(
                new ConsumerMappedHeaderExport($scheduleExport->csvHeader->getAttribute('mappedHeaders'), $data),
                $filename,
                writerType: ExcelExcel::CSV
            ),
            $filename,
        ];
    }

    /**
     * @return array<int, bool|null|string>
     */
    protected function billingHistories(ScheduleExport $scheduleExport): array
    {
        $data = [
            'company_id' => $scheduleExport->company_id,
            'subclient_id' => $scheduleExport->subclient_id,
            ...$this->changeTimezone($scheduleExport),
        ];

        $membershipTransactions = $this->membershipTransactionService->generateReports($data);

        if ($membershipTransactions->isEmpty()) {
            return [false, null];
        }

        $filename = 'public/schedule-export/' . $scheduleExport->frequency->filename($scheduleExport->report_type->value);

        return [
            Excel::store(
                new BillingHistoriesExport($membershipTransactions),
                $filename,
                writerType: ExcelExcel::CSV
            ),
            $filename,
        ];

    }

    /**
     * @return array<int, bool|null|string>
     */
    protected function allAccountStatusAndActivity(ScheduleExport $scheduleExport): array
    {
        $data = [
            'company_id' => $scheduleExport->company_id,
            'subclient_id' => $scheduleExport->subclient_id,
            ...$this->changeTimezone($scheduleExport),
        ];

        $consumers = $this->consumerService->reportAllAccountStatusAndActivity($data);

        if ($consumers->isEmpty()) {
            return [false, null];
        }

        $filename = 'public/schedule-export/' . $scheduleExport->frequency->filename($scheduleExport->report_type->value);

        return [
            Excel::store(
                new AllAccountStatusAndActivityExport($consumers),
                $filename,
                writerType: ExcelExcel::CSV
            ),
            $filename,
        ];

    }

    /**
     * @return array<int, bool|null|string>
     */
    protected function consumerPayments(ScheduleExport $scheduleExport): array
    {
        $data = [
            'company_id' => $scheduleExport->company_id,
            'subclient_id' => $scheduleExport->subclient_id,
            ...$this->changeTimezone($scheduleExport),
        ];

        $transactions = $this->transactionService->getConsumerPaymentsReport($data);

        if ($transactions->isEmpty()) {
            return [false, null];
        }

        $filename = 'public/schedule-export/' . $scheduleExport->frequency->filename($scheduleExport->report_type->value);

        return [
            Excel::store(
                new ConsumerPaymentsExport($transactions),
                $filename,
                writerType: ExcelExcel::CSV
            ),
            $filename,
        ];
    }

    /**
     * @return array<int, bool|null|string>
     */
    protected function disputeNoPay(ScheduleExport $scheduleExport): array
    {
        $data = [
            'company_id' => $scheduleExport->company_id,
            'subclient_id' => $scheduleExport->subclient_id,
            ...$this->changeTimezone($scheduleExport),
        ];

        $consumers = $this->consumerService->reportDisputeNoPay($data);

        if ($consumers->isEmpty()) {
            return [false, null];
        }

        $filename = 'public/schedule-export/' . $scheduleExport->frequency->filename($scheduleExport->report_type->value);

        return [
            Excel::store(
                new DisputeNoPayExport($consumers),
                $filename,
                writerType: ExcelExcel::CSV
            ),
            $filename,
        ];
    }

    /**
     * @return array<int, bool|null|string>
     */
    protected function consumerOptOutReport(ScheduleExport $scheduleExport): array
    {
        $data = [
            'company_id' => $scheduleExport->company_id,
            'subclient_id' => $scheduleExport->subclient_id,
        ];

        $consumers = $this->consumerService->reportConsumerOptOut($data);

        if ($consumers->isEmpty()) {
            return [false, null];
        }

        $filename = 'public/schedule-export/' . $scheduleExport->frequency->filename($scheduleExport->report_type->value);

        return [
            Excel::store(
                new ConsumerOptOutExport($consumers),
                $filename,
                writerType: ExcelExcel::CSV
            ),
            $filename,
        ];
    }

    /**
     * @return array<int, bool|null|string>
     */
    protected function finalPaymentsBalanceSummary(ScheduleExport $scheduleExport): array
    {
        $data = [
            'company_id' => $scheduleExport->company_id,
            'subclient_id' => $scheduleExport->subclient_id,
        ];

        $consumers = $this->consumerService->reportConsumerOptOut($data);

        if ($consumers->isEmpty()) {
            return [false, null];
        }

        $filename = 'public/schedule-export/' . $scheduleExport->frequency->filename($scheduleExport->report_type->value);

        return [
            Excel::store(
                new finalPaymentsBalanceSummaryExport($consumers),
                $filename,
                writerType: ExcelExcel::CSV
            ),
            $filename,
        ];
    }

    /**
     * @return array<int, bool|null|string>
     */
    protected function summaryBalanceCompliance(ScheduleExport $scheduleExport): array
    {
        $data = [
            'company_id' => $scheduleExport->company_id,
            'subclient_id' => $scheduleExport->subclient_id,
        ];

        $consumers = $this->consumerService->reportSummaryBalanceCompliance($data);

        if ($consumers->isEmpty()) {
            return [false, null];
        }

        $filename = 'public/schedule-export/' . $scheduleExport->frequency->filename($scheduleExport->report_type->value);

        return [
            Excel::store(
                new SummaryBalanceComplianceExport($consumers),
                $filename,
                writerType: ExcelExcel::CSV
            ),
            $filename,
        ];
    }
}

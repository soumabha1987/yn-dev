<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Exports\PartnerMonthlyReportsExport;
use App\Jobs\DeletePartnerMonthlyBillingReportJob;
use App\Jobs\SendPartnerMonthlyBillingReportJob;
use App\Models\Partner;
use App\Services\PartnerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;

class PartnerMonthlyBillingReportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'partner:billing-reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Partners monthly billing reports';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        app(PartnerService::class)
            ->fetchMonthlyReports()
            ->each(function (Partner $partner): void {
                $companies = $partner->companies;

                /** @var float $totalAmount */
                $totalAmount = $companies->sum('total_yn_transactions_amount') + $companies->sum('total_membership_transactions_amount');

                /** @var float $partnerTotalAmount */
                $partnerTotalAmount = $companies->sum('total_yn_transaction_partner_revenue') + $companies->sum('total_membership_transactions_partner_revenue');

                if ($partnerTotalAmount > 0) {
                    $this->createReportAndSendEmailToYouNegotiate($partner, $totalAmount, $partnerTotalAmount);
                }
            });
    }

    private function createReportAndSendEmailToYouNegotiate(Partner $partner, float $totalAmount, float $partnerTotalAmount): void
    {
        $fileName = 'you_negotiate_partner_report_' . Str::random(10) . '_' . $partner->id . '.csv';

        Excel::store(
            new PartnerMonthlyReportsExport($partner, $totalAmount, $partnerTotalAmount),
            $fileName,
            writerType: ExcelExcel::CSV
        );

        Bus::chain([
            new SendPartnerMonthlyBillingReportJob($partner, $fileName),
            new DeletePartnerMonthlyBillingReportJob($fileName),
        ])->dispatch();
    }
}

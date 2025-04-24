<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AutomatedTemplateType;
use App\Enums\MembershipTransactionStatus;
use App\Models\Company;
use App\Models\YnTransaction;
use App\Services\AutomatedCommunicationHistoryService;
use App\Services\PartnerService;
use App\Services\TilledPaymentService;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessCreditorPaymentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:creditor-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Charge creditor payment YouNegotiate share';

    /**
     * Execute the console command.
     */
    public function handle(TilledPaymentService $tilledPaymentService): void
    {
        Company::query()->cursor()->each(function (Company $company) use ($tilledPaymentService): void {
            $this->chargeCompany($company, $tilledPaymentService);
        });
    }

    private function chargeCompany(Company $company, TilledPaymentService $tilledPaymentService): void
    {
        $data = [
            'company_id' => $company->id,
            'from' => today()->subWeek()->toDateString(),
            'to' => Carbon::yesterday()->toDateString(),
        ];

        $transactions = app(TransactionService::class)->fetchProcessCreditorPayments($data);

        $histories = app(AutomatedCommunicationHistoryService::class)->fetchForProcessCreditorPaymentsCommand($data);

        $emailHistories = $histories->where('automated_template_type', AutomatedTemplateType::EMAIL);
        $smsHistories = $histories->where('automated_template_type', AutomatedTemplateType::SMS);

        $emailCount = $emailHistories->count();
        $emailAmount = $emailHistories->sum('cost');
        $smsCount = $smsHistories->count();
        $smsAmount = $smsHistories->sum('cost');

        $totalYnShare = (float) ($transactions->sum('rnn_share'));

        if ($totalYnShare > 0) {
            $company->loadMissing('membershipPaymentProfile');

            $response = $tilledPaymentService->createPaymentIntents(intval($totalYnShare * 100), $company->membershipPaymentProfile?->tilled_payment_method_id);

            $lastYnTransaction = YnTransaction::query()->latest()->value('rnn_invoice_id');

            $partnerRevenueShare = 0;

            if ($company->partner_id) {
                $company->loadMissing('partner');

                $partnerRevenueShare = app(PartnerService::class)->calculatePartnerRevenueShare($company->partner, $totalYnShare);
            }

            $rnnInvoiceId = $lastYnTransaction ? $lastYnTransaction + 1 : 5000;

            $ynTransaction = new YnTransaction([
                'company_id' => $company->id,
                'amount' => number_format($totalYnShare, 2, thousands_separator: ''),
                'response' => $response,
                'billing_cycle_start' => now()->subWeek()->toDateTimeString(),
                'billing_cycle_end' => now()->subDay()->toDateTimeString(),
                'email_count' => $emailCount,
                'sms_count' => $smsCount,
                'eletter_count' => 0,
                'phone_no_count' => 0,
                'email_cost' => $emailAmount,
                'sms_cost' => $smsAmount,
                'eletter_cost' => 0,
                'rnn_invoice_id' => $rnnInvoiceId,
                'reference_number' => mt_rand(100000000, 999999999),
                'partner_revenue_share' => number_format($partnerRevenueShare, 2, thousands_separator: ''),
            ]);

            $transactionStatus = optional($response)['status'];

            // This is not for a membership transaction.
            // We are ensuring the enum is defined only once, which is why we used it.
            $ynTransaction->status = (! $transactionStatus || ! in_array($transactionStatus, ['processing', 'succeeded']))
                ? MembershipTransactionStatus::FAILED
                : MembershipTransactionStatus::SUCCESS;

            $ynTransaction->save();

            if ($ynTransaction->status === MembershipTransactionStatus::SUCCESS && $transactions->isNotEmpty()) {
                $transactions->toQuery()
                    ->update([
                        'rnn_share_pass' => now(),
                        'yn_transaction_id' => $ynTransaction->id,
                    ]);
            }
        }
    }
}

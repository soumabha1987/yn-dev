<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MembershipTransactionStatus;
use App\Models\Company;
use App\Models\YnTransaction;
use Illuminate\Support\Facades\Log;

class EcoLetterPaymentService
{
    public function applyEcoLetterDeduction(Company $company, int $consumerCount): bool
    {
        $amount = $consumerCount * app(CompanyMembershipService::class)->fetchELetterFee($company->id);

        $membershipPaymentProfile = app(MembershipPaymentProfileService::class)
            ->fetchByCompany($company->id);

        $response = null;

        if ($amount > 0) {
            $response = app(TilledPaymentService::class)
                ->createPaymentIntents((int) ($amount * 100), $membershipPaymentProfile->tilled_payment_method_id);
        }

        $lastYnTransaction = YnTransaction::query()->latest()->value('rnn_invoice_id');

        $rnnInvoiceId = $lastYnTransaction ? $lastYnTransaction + 1 : 5000;

        $partnerRevenueShare = 0;

        if ($company->partner_id) {
            $company->loadMissing('partner');

            $partnerRevenueShare = app(PartnerService::class)->calculatePartnerRevenueShare($company->partner, $amount);
        }

        $transactionStatus = $amount > 0 ? optional($response)['status'] : MembershipTransactionStatus::SUCCESS;

        $status = (! $transactionStatus || ! in_array($transactionStatus, ['processing', 'succeeded']))
            ? MembershipTransactionStatus::FAILED
            : MembershipTransactionStatus::SUCCESS;

        YnTransaction::query()
            ->create([
                'company_id' => $company->id,
                'amount' => number_format($amount, 2, thousands_separator: ''),
                'response' => $response,
                'billing_cycle_start' => now()->toDateTimeString(),
                'billing_cycle_end' => now()->toDateTimeString(),
                'email_count' => 0,
                'sms_count' => 0,
                'eletter_count' => $consumerCount,
                'phone_no_count' => 0,
                'email_cost' => 0,
                'sms_cost' => 0,
                'eletter_cost' => number_format($amount, 2, thousands_separator: ''),
                'rnn_invoice_id' => $rnnInvoiceId,
                'reference_number' => mt_rand(100000000, 999999999),
                'partner_revenue_share' => number_format($partnerRevenueShare, 2, thousands_separator: ''),
                'status' => $status,
            ]);

        if ($status === MembershipTransactionStatus::FAILED) {
            Log::channel('daily')->error('There are error payment', [
                'company' => $company->id,
                'amount' => $amount,
                'consumer count' => $consumerCount,
                'response' => $response,
                'error' => data_get($response, 'last_payment_error.message', __('Something went wrong!')),
            ]);

            return false;
        }

        return true;
    }
}

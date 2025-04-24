<?php

declare(strict_types=1);

namespace App\Services\Consumer;

use App\Enums\MembershipTransactionStatus;
use App\Enums\MerchantType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\ExternalPaymentProfile;
use App\Models\Merchant;
use App\Models\ScheduleTransaction;
use App\Models\Transaction;
use App\Models\YnTransaction;
use App\Services\PartnerService;
use App\Services\ScheduleTransactionService;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TilledPaymentService extends SchedulePlanPaymentService
{
    public function payRemainingAmount(
        Consumer $consumer,
        ConsumerNegotiation $consumerNegotiation,
        Collection $scheduleTransactions,
    ): bool {
        $totalAmounts = $scheduleTransactions->sum('amount');

        $tilledMerchantAccountId = $consumer->subclient->tilled_merchant_account_id ?? $consumer->company->tilled_merchant_account_id;

        try {
            $shares = app(CompanyMembershipService::class)->fetchShares($consumer, $totalAmounts);
            $this->ynShare = $shares['yn_share'];
            $this->companyShare = $shares['company_share'];
            $this->revenueSharePercentage = $shares['share_percentage'];

            /** @var MerchantType $method */
            $method = $consumer->paymentProfile->method;

            $response = $this->proceedPayment(
                tilledAccountId: $tilledMerchantAccountId,
                amount: $totalAmounts,
                paymentMethod: $method,
                paymentProfileId: $consumer->paymentProfile->payment_profile_id,
                platformFee: (float) $shares['yn_share'] * 100,
            );

            if ($response->successful()) {
                if ($response->json('status') === 'processing' || $response->json('status') === 'succeeded') {
                    $ynTransaction = $this->createYnTransaction($response, $consumer->company, (float) $shares['yn_share']);

                    $transaction = $this->successfulTransactionOfRemainingBalance(
                        transactionId: $response->json('id'),
                        transactionResponse: $response->json(),
                        scheduleTransactions: $scheduleTransactions,
                        consumer: $consumer,
                        consumerNegotiation: $consumerNegotiation,
                        statusCode: (string) $response->status()
                    );

                    $transaction->update([
                        'rnn_share_pass' => now()->toDateTimeString(),
                        'yn_transaction_id' => $ynTransaction->id,
                    ]);

                    return true;
                }

                $transaction = $this->failedTransaction(
                    transactionId: null,
                    consumer: $consumer,
                    transactionResponse: $response->json(),
                    transactionType: TransactionType::PARTIAL_PIF
                );

                $transaction->status_code = $response->status();
                $transaction->amount = $totalAmounts;
                $transaction->save();

                return false;
            }

            if ($response->failed()) {
                $transaction = $this->failedTransaction(
                    transactionId: null,
                    consumer: $consumer,
                    transactionResponse: $response->json(),
                    transactionType: TransactionType::PARTIAL_PIF
                );

                $transaction->status_code = $response->status();
                $transaction->amount = $totalAmounts;
                $transaction->save();

                return false;
            }

            return false;
        } catch (Exception $exception) {
            Log::channel('daily')->error('There are error in pay remaining balance using tilled', [
                'consumer' => $consumer->id,
                'tilled_merchant_account_id' => $tilledMerchantAccountId,
                'payment_profile_id' => $consumer->paymentProfile->payment_profile_id,
                'message' => $exception->getMessage(),
                'stack trace' => $exception->getTrace(),
            ]);

            return false;
        }
    }

    public function payInstallment(
        ScheduleTransaction $scheduleTransaction,
        Consumer $consumer,
        ConsumerNegotiation $consumerNegotiation
    ): bool {
        $tilledMerchantAccountId = $consumer->subclient->tilled_merchant_account_id ?? $consumer->company->tilled_merchant_account_id;

        try {
            $shares = app(CompanyMembershipService::class)->fetchShares($consumer, (float) $scheduleTransaction->amount);
            $this->ynShare = $shares['yn_share'];
            $this->companyShare = $shares['company_share'];
            $this->revenueSharePercentage = $shares['share_percentage'];

            /** @var MerchantType $method */
            $method = $consumer->paymentProfile->method;

            $response = $this->proceedPayment(
                tilledAccountId: $tilledMerchantAccountId,
                amount: (float) $scheduleTransaction->amount,
                paymentMethod: $method,
                paymentProfileId: $consumer->paymentProfile->payment_profile_id,
                platformFee: (float) $shares['yn_share'] * 100,
            );

            if ($response->successful()) {
                if ($response->json('status') === 'processing' || $response->json('status') === 'succeeded') {
                    $ynTransaction = $this->createYnTransaction($response, $consumer->company, (float) $shares['yn_share']);

                    $transaction = $this->successfulTransactionOfInstallment(
                        transactionId: $response->json('id'),
                        transactionResponse: $response->json(),
                        consumer: $consumer,
                        consumerNegotiation: $consumerNegotiation,
                        scheduleTransaction: $scheduleTransaction,
                        statusCode: (string) $response->status(),
                    );

                    $transaction->update([
                        'rnn_share_pass' => now()->toDateTimeString(),
                        'yn_transaction_id' => $ynTransaction->id,
                    ]);

                    return true;
                }

                $transaction = $this->failedTransaction(
                    transactionId: null,
                    consumer: $consumer,
                    transactionResponse: $response->json(),
                    transactionType: TransactionType::INSTALLMENT
                );

                $transaction->status_code = $response->status();
                $transaction->amount = (float) $scheduleTransaction->amount;
                $transaction->save();

                return false;
            }

            if ($response->failed()) {
                $transaction = $this->failedTransaction(
                    transactionId: null,
                    consumer: $consumer,
                    transactionResponse: $response->json(),
                    transactionType: TransactionType::INSTALLMENT
                );

                $transaction->status_code = $response->status();
                $transaction->amount = (float) $scheduleTransaction->amount;
                $transaction->save();

                return false;
            }

            return false;
        } catch (Exception $exception) {

            Log::channel('daily')->error('There are error in pay installment of tilled js', [
                'consumer' => $consumer->id,
                'tilled_merchant_account_id' => $tilledMerchantAccountId,
                'payment_profile_id' => $consumer->paymentProfile->payment_profile_id,
                'message' => $exception->getMessage(),
                'stack trace' => $exception->getTrace(),
            ]);

            return false;
        }
    }

    /**
     * @param array{
     *  first_name: string,
     *  last_name: string,
     *  address: string,
     *  city: string,
     *  state: string,
     *  zip: string,
     *  card_number: string,
     *  expiry: string,
     *  cvv: string,
     *  amount: float,
     *  method: string,
     *  payment_method_id: string,
     *  is_pif: bool,
     * } $data
     */
    public function makePayment(ExternalPaymentProfile $externalPaymentProfile, array $data, Merchant $merchant): mixed
    {
        $tilledMerchantAccountId = $externalPaymentProfile->subclient->tilled_merchant_account_id ?? $externalPaymentProfile->company->tilled_merchant_account_id;

        $shares = $data['is_pif']
            ? app(CompanyMembershipService::class)->fetchShares($externalPaymentProfile->consumer, (float) $data['amount'])
            : app(ScheduleTransactionService::class)->calculateShareAmount($externalPaymentProfile->consumer, (float) $data['amount']);

        $response = Http::tilled($tilledMerchantAccountId)
            ->post('payment-intents', [
                'amount' => intval($data['amount'] * 100),
                'currency' => 'usd',
                'payment_method_types' => [$data['method'] === MerchantType::CC->value ? 'card' : 'ach_debit'],
                'payment_method_id' => $data['payment_method_id'],
                'confirm' => true,
                'platform_fee_amount' => intval((float) $shares['yn_share'] * 100),
            ]);

        if ($response->successful()) {
            if (in_array($response->json('status'), ['processing', 'succeeded'])) {

                $ynTransaction = $this->createYnTransaction($response, $externalPaymentProfile->company, (float) $shares['yn_share']);

                $transaction = Transaction::query()->create([
                    'company_id' => $externalPaymentProfile->company_id,
                    'subclient_id' => $externalPaymentProfile->subclient_id,
                    'consumer_id' => $externalPaymentProfile->consumer_id,
                    'external_payment_profile_id' => $externalPaymentProfile->id,
                    'transaction_id' => $response->json('id'),
                    'transaction_type' => TransactionType::PARTIAL_PIF,
                    'status' => TransactionStatus::SUCCESSFUL,
                    'amount' => number_format((float) $data['amount'], 2, thousands_separator: ''),
                    'status_code' => $response->status(),
                    'gateway_response' => $response->json(),
                    'rnn_share' => $shares['yn_share'],
                    'company_share' => $shares['company_share'],
                    'revenue_share_percentage' => $shares['share_percentage'],
                    'rnn_invoice_id' => (Transaction::max('rnn_invoice_id') ?? 9000) + 1,
                    'payment_mode' => $data['method'],
                    'superadmin_process' => 0,
                    'rnn_share_pass' => now(),
                    'yn_transaction_id' => $ynTransaction->id,
                ]);

                return $transaction->id;
            }

            Log::channel('daily')->error('Failed tilled merchant payment for donation', [
                'response' => $response->json(),
                'merchant' => $merchant,
                'consumer' => $externalPaymentProfile->consumer,
            ]);

            throw new Exception('Failed tilled merchant payment for donation');
        }

        Log::channel('daily')->error('Failed tilled merchant payment for donation', [
            'response' => $response->json(),
            'merchant' => $merchant,
            'consumer' => $externalPaymentProfile->consumer,
        ]);

        throw new Exception('Failed tilled merchant payment for donation');
    }

    protected function createYnTransaction(Response $response, Company $company, float $totalYnShare): YnTransaction
    {
        $lastYnTransaction = YnTransaction::query()->latest()->value('rnn_invoice_id');

        $rnnInvoiceId = $lastYnTransaction ? $lastYnTransaction + 1 : 5000;

        $partnerRevenueShare = 0;

        if ($company->partner_id) {
            $partnerRevenueShare = app(PartnerService::class)
                ->calculatePartnerRevenueShare($company->partner, (float) $totalYnShare);
        }

        return YnTransaction::query()->create([
            'company_id' => $company->id,
            'amount' => number_format($totalYnShare, 2, thousands_separator: ''),
            'response' => $response->json(),
            'billing_cycle_start' => now(),
            'billing_cycle_end' => now(),
            'email_count' => 0,
            'sms_count' => 0,
            'phone_no_count' => 0,
            'email_cost' => 0,
            'sms_cost' => 0,
            'rnn_invoice_id' => $rnnInvoiceId,
            'reference_number' => mt_rand(100000000, 999999999),
            'status' => MembershipTransactionStatus::SUCCESS->value,
            'partner_revenue_share' => number_format($partnerRevenueShare, 2, thousands_separator: ''),
        ]);
    }

    protected function proceedPayment(
        int|string $tilledAccountId,
        float $amount,
        MerchantType $paymentMethod,
        string $paymentProfileId,
        float $platformFee,
    ): Response {
        return Http::tilled($tilledAccountId)
            ->post('/payment-intents', [
                'amount' => intval($amount * 100),
                'currency' => 'usd',
                'payment_method_types' => [$paymentMethod == MerchantType::CC ? 'card' : 'ach_debit'],
                'payment_method_id' => $paymentProfileId,
                'confirm' => true,
                'platform_fee_amount' => intval($platformFee),
            ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CommunicationCode;
use App\Enums\ConsumerStatus;
use App\Enums\MembershipTransactionStatus;
use App\Enums\MerchantType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\MerchantPaymentException;
use App\Models\Company;
use App\Models\ScheduleTransaction;
use App\Models\Transaction;
use App\Models\YnTransaction;
use App\Services\CompanyMembershipService;
use App\Services\ConsumerNegotiationService;
use App\Services\PartnerService;
use App\Services\TransactionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TilledSchedulePaymentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected static ?Transaction $transaction = null;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected ScheduleTransaction $scheduleTransaction,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        TransactionService $transactionService,
        ConsumerNegotiationService $consumerNegotiationService,
        CompanyMembershipService $companyMembershipService,
    ): void {
        $amount = (float) $this->scheduleTransaction->amount;

        $revenueSharePercentage = (float) $this->scheduleTransaction->revenue_share_percentage;

        $shares = $companyMembershipService->fetchShares($revenueSharePercentage, $amount);

        /** @var MerchantType $paymentMethod */
        $paymentMethod = $this->scheduleTransaction->consumer->paymentProfile->method;

        $response = $this->proceedPayment(
            tilledAccountId: $this->scheduleTransaction->consumer->company->tilled_merchant_account_id,
            amount: $amount,
            paymentMethod: $paymentMethod,
            paymentProfileId: $this->scheduleTransaction->consumer->paymentProfile->payment_profile_id,
            platformFee: (float) $shares['yn_share'] * 100,
        );

        if ($response->successful()) {
            if (in_array($response->json('status'), ['processing', 'succeeded'])) {
                $transaction = $transactionService->successful(
                    transactionId: $response->json('id'),
                    transactionResponse: $response->json(),
                    scheduleTransaction: $this->scheduleTransaction,
                    consumerNegotiationService: $consumerNegotiationService,
                );

                $ynTransaction = $this->createYnTransaction($response, $this->scheduleTransaction->company, (float) $shares['yn_share']);

                $transaction->status_code = (string) $response->status();
                $transaction->amount = number_format($amount, 2, thousands_separator: '');
                $transaction->rnn_share = $shares['yn_share'];
                $transaction->company_share = $shares['company_share'];
                $transaction->rnn_share_pass = now()->toDateTimeString();
                $transaction->yn_transaction_id = $ynTransaction->id;
                $transaction->revenue_share_percentage = $revenueSharePercentage;
                $transaction->save();

                $this->scheduleTransaction->update([
                    'transaction_id' => $transaction->id,
                    'status' => TransactionStatus::SUCCESSFUL->value,
                ]);

                if ($this->scheduleTransaction->consumer->refresh()->status === ConsumerStatus::SETTLED) {
                    if ($this->scheduleTransaction->consumer->unsubscribe) {
                        Log::channel('daily')->info('When sending manual an email at that time consumer is not subscribe for that', [
                            'consumer_id' => $this->scheduleTransaction->consumer->id,
                            'communication_code' => CommunicationCode::BALANCE_PAID,
                        ]);

                        return;
                    }

                    TriggerEmailAndSmsServiceJob::dispatch($this->scheduleTransaction->consumer, CommunicationCode::BALANCE_PAID);
                }

                return;
            }

            $transaction = $transactionService->failed(
                transactionId: null,
                consumer: $this->scheduleTransaction->consumer,
                transactionResponse: $response->json(),
            );

            $transaction->status_code = (string) $response->status();
            $transaction->amount = number_format($amount, 2, thousands_separator: '');
            $transaction->save();

            self::$transaction = $transaction;

            throw new MerchantPaymentException('Oops! Something went wrong when installment payment of the younegotiate merchant');
        }

        if ($response->failed()) {
            $transaction = $transactionService->failed(
                transactionId: null,
                consumer: $this->scheduleTransaction->consumer,
                transactionResponse: $response->json(),
            );

            $transaction->status_code = (string) $response->status();
            $transaction->amount = number_format($amount, 2, thousands_separator: '');
            $transaction->save();

            self::$transaction = $transaction;

            throw new MerchantPaymentException('Oops! Something went wrong when installment payment of the younegotiate merchant');
        }

        throw new MerchantPaymentException('Oops! Something went wrong when installment payment of the younegotiate merchant');
    }

    protected function createYnTransaction(Response $response, Company $company, float $totalYnShare): YnTransaction
    {
        $lastYnTransaction = YnTransaction::query()->latest()->value('rnn_invoice_id');

        $rnnInvoiceId = $lastYnTransaction ? $lastYnTransaction + 1 : 5000;

        $partnerRevenueShare = 0;

        if ($company->partner_id) {
            $company->loadMissing('partner');

            $partnerRevenueShare = app(PartnerService::class)->calculatePartnerRevenueShare($company->partner, $totalYnShare);
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
            ->post('payment-intents', [
                'amount' => intval($amount * 100),
                'currency' => 'usd',
                'payment_method_types' => [$paymentMethod == MerchantType::CC ? 'card' : 'ach_debit'],
                'payment_method_id' => $paymentProfileId,
                'confirm' => true,
                'platform_fee_amount' => intval($platformFee),
            ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::channel('daily')->error('There are error in pay installment of tilled merchant.', [
            'consumer' => $this->scheduleTransaction->consumer,
            'payment_profile' => $this->scheduleTransaction->consumer->paymentProfile,
            'message' => $exception->getMessage(),
            'stack trace' => $exception->getTrace(),
        ]);

        $this->scheduleTransaction->update([
            'transaction_id' => self::$transaction->id ?? null,
            'status' => TransactionStatus::FAILED,
        ]);

        if ($this->scheduleTransaction->attempt_count === 1) {
            $communicationCode = match ($this->scheduleTransaction->transaction_type) {
                TransactionType::PIF => CommunicationCode::PAYMENT_FAILED_WHEN_PIF,
                TransactionType::INSTALLMENT => CommunicationCode::PAYMENT_FAILED_WHEN_INSTALLMENT,
                default => null,
            };

            TriggerEmailAndSmsServiceJob::dispatchIf($communicationCode !== null, $this->scheduleTransaction->consumer, $communicationCode);
        }
    }
}

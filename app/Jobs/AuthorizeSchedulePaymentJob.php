<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CommunicationCode;
use App\Enums\ConsumerStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\MerchantPaymentException;
use App\Models\ScheduleTransaction;
use App\Models\Transaction;
use App\Services\AuthorizePaymentService;
use App\Services\CompanyMembershipService;
use App\Services\ConsumerNegotiationService;
use App\Services\TransactionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuthorizeSchedulePaymentJob implements ShouldQueue
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
        AuthorizePaymentService $authorizePaymentService,
        ConsumerNegotiationService $consumerNegotiationService,
        TransactionService $transactionService,
        CompanyMembershipService $companyMembershipService,
    ): void {
        $merchant = $this->scheduleTransaction->consumer->paymentProfile->merchant;

        $amount = (float) $this->scheduleTransaction->amount;

        $response = $authorizePaymentService->proceedPayment(
            authorize_login_id: $merchant->authorize_login_id,
            authorize_transaction_key: $merchant->authorize_transaction_key,
            amount: $amount,
            consumer_profile_id: $this->scheduleTransaction->consumer->paymentProfile->profile_id,
            payment_profile_id: $this->scheduleTransaction->consumer->paymentProfile->payment_profile_id,
        );

        $transactionResponse = $response->getTransactionResponse();

        if ($response->getMessages()->getResultCode() === 'Ok' && $transactionResponse !== null && $transactionResponse->getMessages() !== null) {
            $revenueSharePercentage = (float) $this->scheduleTransaction->revenue_share_percentage;

            $shares = $companyMembershipService->fetchShares($revenueSharePercentage, $amount);

            $transaction = $transactionService->successful(
                transactionId: $transactionResponse->getTransId(),
                transactionResponse: $transactionResponse,
                scheduleTransaction: $this->scheduleTransaction,
                consumerNegotiationService: $consumerNegotiationService,
            );

            $transaction->status_code = $transactionResponse->getResponseCode();
            $transaction->amount = number_format($amount, 2, thousands_separator: '');
            $transaction->rnn_share = $shares['yn_share'];
            $transaction->company_share = $shares['company_share'];
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
            transactionId: $transactionResponse?->getTransId(),
            consumer: $this->scheduleTransaction->consumer,
            transactionResponse: $transactionResponse,
        );

        $transaction->status_code = optional($transactionResponse?->getErrors(), fn (array $errors) => $errors[0]->getErrorCode());
        $transaction->amount = number_format($amount, 2, thousands_separator: '');
        $transaction->save();

        self::$transaction = $transaction;

        throw new MerchantPaymentException('Oops! Something went wrong in authorize for installment payment');
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::channel('daily')->error('There are error in pay installment of authorize merchant.', [
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

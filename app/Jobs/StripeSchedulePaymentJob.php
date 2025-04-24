<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CommunicationCode;
use App\Enums\ConsumerStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\MerchantPaymentException;
use App\Models\ScheduleTransaction;
use App\Models\StripePaymentDetail;
use App\Models\Transaction;
use App\Services\CompanyMembershipService;
use App\Services\ConsumerNegotiationService;
use App\Services\StripePaymentService;
use App\Services\TransactionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;
use Throwable;

class StripeSchedulePaymentJob implements ShouldQueue
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
    ) {
        //
    }

    /**
     * Execute the job.
     *
     * @throws MerchantPaymentException
     */
    public function handle(
        ConsumerNegotiationService $consumerNegotiationService,
        StripePaymentService $stripePaymentService,
        TransactionService $transactionService,
        CompanyMembershipService $companyMembershipService,
    ): void {
        $stripePaymentDetail = StripePaymentDetail::query()
            ->where('id', $this->scheduleTransaction->stripe_payment_detail_id)
            ->first();

        if (! $stripePaymentDetail) {
            Log::channel('daily')->error('Stripe payment details not found', [
                'schedule_transactions' => $this->scheduleTransaction,
                'consumer' => $this->scheduleTransaction->consumer,
            ]);

            return;
        }

        $merchant = $this->scheduleTransaction->consumer->paymentProfile->merchant;

        $amount = (float) $this->scheduleTransaction->amount;

        $response = $stripePaymentService->proceedPayment(
            secret_key: $merchant->stripe_secret_key,
            stripe_payment_detail: $stripePaymentDetail,
            consumer: $this->scheduleTransaction->consumer,
            amount: $amount
        );

        if ($response->status === PaymentIntent::STATUS_SUCCEEDED) {
            $revenueSharePercentage = (float) $this->scheduleTransaction->revenue_share_percentage;

            $shares = $companyMembershipService->fetchShares($revenueSharePercentage, $amount);

            $transaction = $transactionService->successful(
                transactionId: $response->id,
                transactionResponse: $response->toArray(),
                scheduleTransaction: $this->scheduleTransaction,
                consumerNegotiationService: $consumerNegotiationService,
            );

            $transaction->status_code = 'A';
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
            transactionId: $response->id,
            consumer: $this->scheduleTransaction->consumer,
            transactionResponse: $response->toArray(),
        );

        $transaction->status_code = null;
        $transaction->amount = number_format($amount, 2, thousands_separator: '');
        $transaction->save();

        self::$transaction = $transaction;

        throw new MerchantPaymentException('Oops! Stripe payment for installment is not working');
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::channel('daily')->error('There are error in pay installment of stripe merchant.', [
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

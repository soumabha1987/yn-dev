<?php

declare(strict_types=1);

namespace App\Services\Consumer;

use App\Enums\CommunicationCode;
use App\Enums\ConsumerStatus;
use App\Enums\NegotiationType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Jobs\TriggerEmailAndSmsServiceJob;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\ScheduleTransaction;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SchedulePlanPaymentService
{
    protected string $ynShare = '';

    protected string $companyShare = '';

    protected string $revenueSharePercentage = '';

    public function successfulTransactionOfRemainingBalance(
        mixed $transactionId,
        mixed $transactionResponse,
        Collection $scheduleTransactions,
        Consumer $consumer,
        ConsumerNegotiation $consumerNegotiation,
        string $statusCode,
    ): Transaction {
        $transaction = $this->transaction($transactionId, $consumer, $transactionResponse);

        $totalAmount = $scheduleTransactions->sum('amount');

        $consumer->update([
            'status' => ConsumerStatus::SETTLED,
            'has_failed_payment' => false,
            'current_balance' => max(0, (float) $consumer->current_balance - $totalAmount),
        ]);

        $this->updateConsumerNegotiation($consumerNegotiation, $totalAmount);

        $transaction->status = TransactionStatus::SUCCESSFUL;
        $transaction->amount = $totalAmount;

        if (blank($this->ynShare) && blank($this->companyShare) && blank($this->revenueSharePercentage)) {
            $shares = app(CompanyMembershipService::class)->fetchShares($consumer, $totalAmount);
            $this->ynShare = $shares['yn_share'];
            $this->companyShare = $shares['company_share'];
            $this->revenueSharePercentage = $shares['share_percentage'];
        }

        $transaction->transaction_type = TransactionType::PARTIAL_PIF;
        $transaction->rnn_share = $this->ynShare;
        $transaction->company_share = $this->companyShare;
        $transaction->revenue_share_percentage = $this->revenueSharePercentage;
        $transaction->status_code = $statusCode;
        $transaction->save();

        $scheduleTransactions->toQuery()->update([
            'transaction_id' => $transaction->id,
            'status' => TransactionStatus::SUCCESSFUL,
            'attempt_count' => DB::raw('attempt_count + 1'),
            'last_attempted_at' => now(),
        ]);

        TriggerEmailAndSmsServiceJob::dispatch($consumer, CommunicationCode::BALANCE_PAID);

        return $transaction;
    }

    /**
     * @param  array<string, mixed>  $transactionResponse
     */
    protected function successfulTransactionOfInstallment(
        null|string|int $transactionId,
        mixed $transactionResponse,
        Consumer $consumer,
        ConsumerNegotiation $consumerNegotiation,
        ScheduleTransaction $scheduleTransaction,
        string $statusCode,
    ): Transaction {
        $transaction = $this->transaction($transactionId, $consumer, $transactionResponse);

        $isLastInstallment = ScheduleTransaction::query()
            ->whereIn('status', [TransactionStatus::FAILED, TransactionStatus::SCHEDULED])
            ->where('consumer_id', $consumer->id)
            ->count() === 1;

        $consumer->update([
            'has_failed_payment' => false,
            'status' => $isLastInstallment ? ConsumerStatus::SETTLED : $consumer->status,
            'current_balance' => max(0, (float) $consumer->current_balance - (float) $scheduleTransaction->amount),
        ]);

        $this->updateConsumerNegotiation($consumerNegotiation, (float) $scheduleTransaction->amount);

        if (blank($this->ynShare) && blank($this->companyShare) && blank($this->revenueSharePercentage)) {
            $shares = app(CompanyMembershipService::class)->fetchShares($consumer, (float) $scheduleTransaction->amount);
            $this->ynShare = $shares['yn_share'];
            $this->companyShare = $shares['company_share'];
            $this->revenueSharePercentage = $shares['share_percentage'];
        }

        $transaction->transaction_type = TransactionType::INSTALLMENT;
        $transaction->status = TransactionStatus::SUCCESSFUL;
        $transaction->amount = (string) $scheduleTransaction->amount;
        $transaction->rnn_share = $this->ynShare;
        $transaction->company_share = $this->companyShare;
        $transaction->revenue_share_percentage = $this->revenueSharePercentage;
        $transaction->status_code = $statusCode;
        $transaction->save();

        $scheduleTransaction->update([
            'transaction_id' => $transaction->id,
            'status' => TransactionStatus::SUCCESSFUL,
            'attempt_count' => DB::raw('attempt_count + 1'),
            'last_attempted_at' => now(),
        ]);

        if ($isLastInstallment) {
            TriggerEmailAndSmsServiceJob::dispatch($consumer, CommunicationCode::BALANCE_PAID);
        }

        return $transaction;
    }

    /**
     * @param  array<string, mixed>  $transactionResponse
     */
    protected function failedTransaction(
        null|int|string $transactionId,
        Consumer $consumer,
        mixed $transactionResponse,
        TransactionType $transactionType
    ) {
        $transaction = $this->transaction($transactionId, $consumer, $transactionResponse);

        $transaction->transaction_type = $transactionType;
        $transaction->status = TransactionStatus::FAILED;

        $consumer->update(['has_failed_payment' => true]);

        return $transaction;
    }

    private function transaction(mixed $transactionId, Consumer $consumer, mixed $transactionResponse): Transaction
    {
        $transactionRnnInvoiceId = Transaction::query()->latest()->value('rnn_invoice_id');
        $rnnInvoiceId = $transactionRnnInvoiceId ? $transactionRnnInvoiceId + 1 : 9000;

        return new Transaction([
            'transaction_id' => $transactionId,
            'consumer_id' => $consumer->id,
            'company_id' => $consumer->company_id,
            'payment_profile_id' => $consumer->paymentProfile->id,
            'gateway_response' => $transactionResponse,
            'payment_mode' => $consumer->paymentProfile->method,
            'subclient_id' => $consumer->subclient_id,
            'rnn_invoice_id' => $rnnInvoiceId,
            'superadmin_process' => 0,
        ]);
    }

    public function updateConsumerNegotiation(ConsumerNegotiation $consumerNegotiation, float $scheduleTransactionAmount): void
    {
        $currentBalance = 0;

        $currentBalance = match (true) {
            $consumerNegotiation->payment_plan_current_balance !== null => max(0, (float) $consumerNegotiation->payment_plan_current_balance - $scheduleTransactionAmount),
            $consumerNegotiation->negotiation_type === NegotiationType::PIF && $consumerNegotiation->offer_accepted => max(0, (float) $consumerNegotiation->one_time_settlement - $scheduleTransactionAmount),
            $consumerNegotiation->negotiation_type === NegotiationType::PIF && $consumerNegotiation->counter_offer_accepted => max(0, (float) $consumerNegotiation->counter_one_time_amount - $scheduleTransactionAmount),
            $consumerNegotiation->offer_accepted => max(0, (float) $consumerNegotiation->negotiate_amount - $scheduleTransactionAmount),
            $consumerNegotiation->counter_offer_accepted => max(0, (float) $consumerNegotiation->counter_negotiate_amount - $scheduleTransactionAmount),
            default => $currentBalance,
        };

        $consumerNegotiation->payment_plan_current_balance = (string) $currentBalance;
        $consumerNegotiation->save();
    }
}

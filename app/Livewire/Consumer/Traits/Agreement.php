<?php

declare(strict_types=1);

namespace App\Livewire\Consumer\Traits;

use App\Enums\ConsumerStatus;
use App\Enums\NegotiationType;
use App\Enums\TransactionStatus;
use App\Models\Consumer;
use App\Models\ScheduleTransaction;
use App\Services\Consumer\ScheduleTransactionService;
use App\Services\Consumer\TransactionService;
use App\Services\CustomContentService;
use Barryvdh\DomPDF\Facade\Pdf;

trait Agreement
{
    public string $pdfName = '';

    public function downloadAgreement(Consumer $consumer)
    {
        $consumer->loadMissing(['company', 'subclient', 'paymentProfile', 'consumerNegotiation']);

        $customContent = app(CustomContentService::class)->findByCompanyOrSubclient($consumer->company_id, $consumer->subclient_id);

        [$cancelledScheduleTransactions, $scheduleTransactions] = app(ScheduleTransactionService::class)->fetchByConsumer($consumer->id)
            ->partition(fn (ScheduleTransaction $scheduleTransaction) => $scheduleTransaction->status === TransactionStatus::CANCELLED);

        $transactions = app(TransactionService::class)->fetchByConsumer($consumer->id);

        $this->pdfName = 'public/' . $consumer->account_number . '_you_negotiate_agreement.pdf';

        $pdf = Pdf::setOption('isRemoteEnabled', true)
            ->loadView('pdf.consumer.dom-agreement', [
                'consumer' => $consumer,
                'negotiationCurrentAmount' => $this->negotiationCurrentAmount($consumer),
                'paymentProfile' => $consumer->paymentProfile,
                'customContent' => $customContent,
                'scheduleTransactions' => $scheduleTransactions,
                'cancelledScheduleTransactions' => $cancelledScheduleTransactions,
                'transactions' => $transactions,
            ])
            ->output();

        $this->dispatch('close-menu');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf;
        }, $consumer->account_number . '_you_negotiate_agreement.pdf');
    }

    private function negotiationCurrentAmount(Consumer $consumer): mixed
    {
        $negotiateCurrentAmount = (float) $consumer->current_balance;

        if (! $consumer->consumerNegotiation) {
            return $negotiateCurrentAmount;
        }

        $negotiateCurrentAmount = $this->negotiateAmount($consumer);

        if ($consumer->status === ConsumerStatus::SETTLED) {
            return $negotiateCurrentAmount;
        }

        return $consumer->consumerNegotiation->payment_plan_current_balance !== null
            ? (float) $consumer->consumerNegotiation->payment_plan_current_balance
            : $negotiateCurrentAmount;
    }

    private function negotiateAmount(Consumer $consumer): float
    {
        $negotiationType = $consumer->consumerNegotiation->negotiation_type;

        if ($negotiationType === NegotiationType::PIF) {
            return $consumer->consumerNegotiation->offer_accepted
               ? (float) $consumer->consumerNegotiation->one_time_settlement
               : (float) ($consumer->consumerNegotiation->counter_one_time_amount ?? $consumer->current_balance);
        }

        if ($negotiationType === NegotiationType::INSTALLMENT) {
            $negotiateCurrentAmount = $consumer->consumerNegotiation->offer_accepted
                ? $consumer->consumerNegotiation->negotiate_amount
                : $consumer->consumerNegotiation->counter_negotiate_amount;

            return (float) ($negotiateCurrentAmount ?? $consumer->consumerNegotiation->negotiate_amount);
        }
    }
}

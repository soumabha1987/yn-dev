<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ManageConsumers;

use App\Enums\NegotiationType;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Services\ConsumerNegotiationService;
use App\Services\ConsumerUnsubscribeService;
use App\Services\CustomContentService;
use App\Services\ScheduleTransactionService;
use App\Services\TransactionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait CommonFunctionality
{
    public function toggleSubscription(Consumer $consumer): void
    {
        $consumerUnsubscribeService = app(ConsumerUnsubscribeService::class);

        $consumer->loadMissing(['unsubscribe']);

        if (! $this->isSuperAdmin && $consumer->company_id !== $this->user->company_id) {
            $this->error(__('Consumer is not registered with your company.'));

            return;
        }

        if ($consumer->unsubscribe === null) {
            $consumerUnsubscribeService->create($consumer);

            $this->success(__(':consumerName is opted out.', [
                'consumerName' => $consumer->first_name . ' ' . $consumer->last_name,
            ]));

            return;
        }

        $consumerUnsubscribeService->delete($consumer);

        $this->success(__('Successfully re-subscribed: :consumerName', [
            'consumerName' => $consumer->first_name . ' ' . $consumer->last_name,
        ]));
    }

    public function downloadAgreement(Consumer $consumer): StreamedResponse
    {
        $consumer->loadMissing([
            'paymentProfile',
            'externalPaymentProfile',
            'company:id,company_name,owner_email,address,city,zip',
            'subclient:id,subclient_name',
        ]);

        $consumerNegotiation = app(ConsumerNegotiationService::class)->findByConsumer($consumer->id);
        $customContent = app(CustomContentService::class)->findByCompanyOrSubclient($consumer->company_id, $consumer->subclient_id);
        $scheduleTransactions = app(ScheduleTransactionService::class)->fetchByConsumer($consumer->id);
        $transactions = app(TransactionService::class)->fetchByConsumer($consumer->id);

        $negotiationCurrentAmount = $this->getNegotiationCurrentAmount($consumerNegotiation);

        $pdf = Pdf::setOption('isRemoteEnabled', true)
            ->loadView('pdf.creditor.consumer-agreement', [
                'consumer' => $consumer,
                'paymentProfile' => $consumer->paymentProfile,
                'negotiationCurrentAmount' => $negotiationCurrentAmount,
                'contentOfTermsAndConditions' => $customContent->content ?? 'N / A',
                'scheduleTransactions' => $scheduleTransactions,
                'transactions' => $transactions,
            ])
            ->output();

        $this->dispatch('close-menu');

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf;
        }, $consumer->account_number . '_you_negotiate_agreement.pdf');
    }

    private function getNegotiationCurrentAmount(?ConsumerNegotiation $consumerNegotiation): float
    {
        $negotiationCurrentAmount = 0;

        if (! $consumerNegotiation) {
            return $negotiationCurrentAmount;
        }

        $negotiationType = $consumerNegotiation->negotiation_type;

        if ($negotiationType === NegotiationType::PIF) {
            $negotiationCurrentAmount = $consumerNegotiation->offer_accepted
                ? $consumerNegotiation->one_time_settlement
                : $consumerNegotiation->counter_one_time_amount;
        }

        if ($negotiationType === NegotiationType::INSTALLMENT) {
            $negotiationCurrentAmount = $consumerNegotiation->offer_accepted
            ? $consumerNegotiation->negotiate_amount
            : $consumerNegotiation->counter_negotiate_amount;
        }

        return $negotiationCurrentAmount;
    }
}

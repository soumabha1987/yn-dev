<?php

declare(strict_types=1);

namespace App\Livewire\Consumer\Traits\Payments;

use App\Enums\InstallmentType;
use App\Enums\NegotiationType;
use App\Models\Consumer;
use App\Models\ScheduleTransaction;
use App\Services\Consumer\DiscountService;
use App\Services\Consumer\ScheduleTransactionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property Consumer $consumer
 * @property DiscountService $discountService
 */
trait InstallmentDetails
{
    /**
     * @return array<int, array{
     *  schedule_date: string,
     *  amount: float,
     * }>
     */
    private function installmentDetails(): array
    {
        $installmentDetails = collect();

        if ($this->consumer->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT) {
            $installmentDetails = app(ScheduleTransactionService::class)->fetchScheduledOfConsumer($this->consumer)
                ->map(fn (ScheduleTransaction $scheduleTransaction): array => [
                    'schedule_date' => $scheduleTransaction->schedule_date->format('M d, Y'),
                    'amount' => (float) $scheduleTransaction->amount,
                ]);

            if ($installmentDetails->isEmpty()) {
                $installmentDetails = $this->makeFromNegotiations();
            }
        }

        return $installmentDetails->all();
    }

    private function makeFromNegotiations(): Collection
    {
        $noOfInstallments = (int) $this->consumer->consumerNegotiation->no_of_installments;
        $lastInstallmentAmount = $this->consumer->consumerNegotiation->last_month_amount;
        $firstPaymentDate = $this->consumer->consumerNegotiation->first_pay_date;
        $installmentAmount = (float) $this->consumer->consumerNegotiation->monthly_amount;

        if ($this->consumer->consumerNegotiation->counter_offer_accepted) {
            $noOfInstallments = (int) $this->consumer->consumerNegotiation->counter_no_of_installments;
            $lastInstallmentAmount = $this->consumer->consumerNegotiation->counter_last_month_amount;
            $firstPaymentDate = $this->consumer->consumerNegotiation->counter_first_pay_date;
            $installmentAmount = (float) $this->consumer->consumerNegotiation->counter_monthly_amount;
        }

        if ($firstPaymentDate->isPast() && ! $this->consumer->payment_setup) {
            $firstPaymentDate = now();
        }

        $paymentDate = $firstPaymentDate->clone();

        /** @var InstallmentType $installmentType */
        $installmentType = $this->consumer->consumerNegotiation->installment_type;

        $carbonMethod = $installmentType->getCarbonMethod();

        $firstDateIsEndOfMonth = $installmentType === InstallmentType::MONTHLY && $paymentDate->isSameDay($paymentDate->clone()->endOfMonth());

        return collect(range(1, $noOfInstallments))->map(fn (int $number): array => [
            'schedule_date' => $this->getScheduleDate($paymentDate, $carbonMethod, $number - 1, $firstDateIsEndOfMonth)
                ->format('M d, Y'),
            'amount' => $installmentAmount,
        ])->when(
            $lastInstallmentAmount !== null && $lastInstallmentAmount > 0,
            fn (Collection $installmentDetails) => $installmentDetails->push([
                'schedule_date' => $this->getScheduleDate($paymentDate, $carbonMethod, $noOfInstallments, $firstDateIsEndOfMonth)
                    ->format('M d, Y'),
                'amount' => (float) $lastInstallmentAmount,
            ])
        );
    }

    private function getScheduleDate(Carbon $date, string $carbonMethod, int $increment, bool $forceEndOfMonth): Carbon
    {
        return $date->clone()->{$carbonMethod}($increment)->when($forceEndOfMonth, fn (Carbon $date): Carbon => $date->endOfMonth());
    }

    /**
     * @return array{
     *  user_is_come_for_pif_payment: bool,
     *  first_payment_date: ?Carbon,
     *  is_pif_offer_only: bool,
     *  settlement_amount: float,
     *  minimum_pif_discounted_amount: float
     * }
     */
    private function userIsComeForPifPayment(): array
    {
        $negotiationTypeIsPIF = $this->consumer->consumerNegotiation->negotiation_type === NegotiationType::PIF;

        $isPifOfferOnly = false;

        $minimumPifDiscountedAmount = (float) $this->discountService->fetchAmountToPayWhenPif($this->consumer)['discount'];

        $firstPaymentDate = $this->consumer->consumerNegotiation->first_pay_date;
        $settlementAmount = (float) $this->consumer->consumerNegotiation->one_time_settlement;
        if ($this->consumer->consumerNegotiation->counter_offer_accepted) {
            $firstPaymentDate = $this->consumer->consumerNegotiation->counter_first_pay_date;
            $settlementAmount = (float) $this->consumer->consumerNegotiation->counter_one_time_amount;
        }

        if ($negotiationTypeIsPIF && ($firstPaymentDate !== null && $firstPaymentDate->gt(today()))) {
            $isPifOfferOnly = true;
        }

        return [
            'user_is_come_for_pif_payment' => $negotiationTypeIsPIF && ! $isPifOfferOnly,
            'is_pif_offer_only' => $isPifOfferOnly,
            'first_payment_date' => $firstPaymentDate,
            'settlement_amount' => $settlementAmount,
            'minimum_pif_discounted_amount' => $minimumPifDiscountedAmount,
        ];
    }
}

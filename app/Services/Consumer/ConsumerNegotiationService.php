<?php

declare(strict_types=1);

namespace App\Services\Consumer;

use App\Enums\NegotiationType;
use App\Enums\ConsumerStatus;
use App\Enums\InstallmentType;
use App\Models\ConsumerNegotiation;
use App\Models\Consumer;
use App\Services\CampaignTrackerService;
use Illuminate\Support\Facades\Cache;
use App\Services\Consumer\DiscountService;
use App\Services\ConsumerService;



class ConsumerNegotiationService
{
    public function __construct(
        protected DiscountService $discountService
    ) {}

    public function deleteByConsumer(int $consumerId): void
    {
        ConsumerNegotiation::query()
            ->where('consumer_id', $consumerId)
            ->delete();
    }

    public function fetchActive(int $consumerId): ?ConsumerNegotiation
    {
        return ConsumerNegotiation::query()
            ->where('active_negotiation', true)
            ->where('offer_accepted', false)
            ->where('counter_offer_accepted', false)
            ->where('consumer_id', $consumerId)
            ->first();
    }

    // Put this function in a service class or helper utility
    public function updateConsumerNegotiation(
        $consumer,
        array $validatedData,
        bool $isOfferAccepted,
        $minimumPifDiscountedAmount,
        $minimumPpaDiscountedAmount
    ): ?int {
        $amount = (float) $validatedData['amount'];

        // Apply the amount limits based on negotiation type
        if ($amount > $minimumPifDiscountedAmount && $validatedData['negotiation_type'] === NegotiationType::PIF->value) {
            $amount = $minimumPifDiscountedAmount;
        }

        if ($amount > $minimumPpaDiscountedAmount && $validatedData['negotiation_type'] === NegotiationType::INSTALLMENT->value) {
            $amount = $minimumPpaDiscountedAmount;
        }

        $installments = null;
        $lastInstallmentAmount = null;
        $negotiationTypeIsInstallment = $validatedData['negotiation_type'] === NegotiationType::INSTALLMENT->value;

        if ($negotiationTypeIsInstallment) {
            [$installments, $lastInstallmentAmount] = $this->discountService->calculateInstallments($minimumPpaDiscountedAmount, $amount);
        }

        // Update or create consumer negotiation
        ConsumerNegotiation::query()->updateOrCreate(
            [
                'company_id' => $consumer->company_id,
                'consumer_id' => $consumer->id,
            ],
            [
                'first_pay_date' => $validatedData['first_pay_date'],
                'reason' => filled($validatedData['reason']) ? $validatedData['reason'] : null,
                'note' => filled($validatedData['note']) ? $validatedData['note'] : null,
                'negotiation_type' => $validatedData['negotiation_type'],
                'installment_type' => $negotiationTypeIsInstallment ? $validatedData['installment_type'] : null,
                'one_time_settlement' => $negotiationTypeIsInstallment ? null : number_format($amount, 2, thousands_separator: ''),
                'no_of_installments' => $installments,
                'active_negotiation' => true,
                'monthly_amount' => number_format($validatedData['amount'], 2, thousands_separator: ''),
                'negotiate_amount' => $negotiationTypeIsInstallment ? number_format($minimumPpaDiscountedAmount, 2, thousands_separator: '') : null,
                'last_month_amount' => $lastInstallmentAmount ? number_format((float) $lastInstallmentAmount, 2, thousands_separator: '') : null,
            ]
        );

        // Refresh consumer
        $consumer->refresh()->consumerNegotiation->fill([
            'offer_accepted' => $isOfferAccepted,
        ]);

        // Update consumer status
        $consumer->fill([
            'offer_accepted' => $isOfferAccepted,
            'status' => $isOfferAccepted ? ConsumerStatus::PAYMENT_ACCEPTED : ConsumerStatus::PAYMENT_SETUP,
            'custom_offer' => !$isOfferAccepted,
            'counter_offer' => false,
        ]);

        $isOfferAccepted = $this->isOfferAccepted($consumer, $validatedData, $minimumPpaDiscountedAmount);
        $campaignTrackerUpdateFieldName = 'custom_offer_count';

        if ($isOfferAccepted) {
            $consumer->consumerNegotiation->fill([
                'offer_accepted' => true,
            ]);
            $consumer->fill([
                'status' => ConsumerStatus::PAYMENT_ACCEPTED,
                'offer_accepted' => true,
                'custom_offer' => false,
            ]);

            $campaignTrackerUpdateFieldName = $validatedData['negotiation_type'] === NegotiationType::PIF->value
                ? 'pif_completed_count'
                : 'ppl_completed_count';
        }

        $consumer->consumerNegotiation->save();
        $consumer->save();


        $newOfferCount = null;
        // Update cache for new offer count
        if (!$isOfferAccepted) {
            $newOfferCount = app(ConsumerService::class)->getCountOfNewOffer($consumer->company_id);
            Cache::put(
                'new_offer_count_' . $consumer->company_id,
                $newOfferCount,
                now()->addHour(),
            );
        }

        // Update campaign tracker
        app(CampaignTrackerService::class)->updateTrackerCount($consumer, $campaignTrackerUpdateFieldName);

        // Delete scheduled transactions
        app(ScheduleTransactionService::class)->deleteScheduled($consumer->id);

        return $newOfferCount;
    }

    public function isOfferAccepted(
        Consumer $consumer,
        array $data,
        float $minimumPpaDiscountedAmount
    ): bool {
        ['max_first_pay_date' => $maxFirstPaymentDate] = $this->discountService->fetchMaxDateForFirstPayment($consumer);
        $isWithinMaxFirstPaymentDate = $maxFirstPaymentDate->gte($data['first_pay_date']);

        $negotiationTypeIsInstallment = $data['negotiation_type'] === NegotiationType::INSTALLMENT->value;

        $minimumMonthlyPayAmount = number_format(
            $this->discountService->fetchMonthlyAmount($consumer, $minimumPpaDiscountedAmount),
            2,
            thousands_separator: ''
        );
        $monthlyAmount = $this->calculateMonthlyAmount((float) $data['amount'], $data['installment_type']);
        $isMonthlyAmountSufficient = $negotiationTypeIsInstallment && ($monthlyAmount >= (float) $minimumMonthlyPayAmount);


        $negotiationTypeIsPIF = $data['negotiation_type'] === NegotiationType::PIF->value;

        ['discount' => $minimumPifDiscountedAmount] = $this->discountService->fetchAmountToPayWhenPif($consumer);
        $isEnteredAmountSufficient = $negotiationTypeIsPIF && (((float) $data['amount']) >= (float) $minimumPifDiscountedAmount);

        return $isWithinMaxFirstPaymentDate && ($isMonthlyAmountSufficient || $isEnteredAmountSufficient);
    }

    public function calculateMonthlyAmount(float $amount, string $installmentType): float
    {
        return $amount * match ($installmentType) {
            InstallmentType::BIMONTHLY->value => InstallmentType::BIMONTHLY->getAmountMultiplication(),
            InstallmentType::WEEKLY->value => InstallmentType::WEEKLY->getAmountMultiplication(),
            default => InstallmentType::MONTHLY->getAmountMultiplication()
        };
    }
}

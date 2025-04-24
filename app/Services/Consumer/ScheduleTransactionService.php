<?php

declare(strict_types=1);

namespace App\Services\Consumer;

use App\Enums\InstallmentType;
use App\Enums\NegotiationType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\PaymentProfile;
use App\Models\ScheduleTransaction;
use App\Services\CompanyMembershipService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ScheduleTransactionService
{
    protected CompanyMembershipService $companyMembershipService;

    public function __construct()
    {
        $this->companyMembershipService = app(CompanyMembershipService::class);
    }

    public function deleteScheduled(int $consumerId): void
    {
        ScheduleTransaction::query()
            ->where('consumer_id', $consumerId)
            ->where('status', TransactionStatus::SCHEDULED)
            ->delete();
    }

    public function fetchByConsumer(int $consumerId): Collection
    {
        return ScheduleTransaction::query()
            ->with(['paymentProfile', 'externalPaymentProfile'])
            ->where('consumer_id', $consumerId)
            ->whereIn('status', [TransactionStatus::SCHEDULED->value, TransactionStatus::FAILED->value, TransactionStatus::CANCELLED->value])
            ->orderBy('schedule_date')
            ->get();
    }

    public function fetchByConsumerExternalPayments(int $consumerId): Collection
    {
        return ScheduleTransaction::query()
            ->with(['paymentProfile', 'externalPaymentProfile'])
            ->where('consumer_id', $consumerId)
            ->whereIn('status', [TransactionStatus::SCHEDULED->value, TransactionStatus::FAILED->value])
            ->orderBy('schedule_date')
            ->get();
    }

    public function getForPayRemainingBalance(int $consumerId): Collection
    {
        return ScheduleTransaction::query()
            ->select('id', 'amount', 'stripe_payment_detail_id')
            ->where('consumer_id', $consumerId)
            ->whereIn('status', [TransactionStatus::SCHEDULED->value, TransactionStatus::FAILED->value])
            ->get();
    }

    public function nextScheduled(int $consumerId, string $scheduleDate): ?ScheduleTransaction
    {
        return ScheduleTransaction::query()
            ->where('consumer_id', $consumerId)
            ->where('schedule_date', '>', $scheduleDate)
            ->orderBy('schedule_date')
            ->first();
    }

    public function lastScheduled(int $consumerId): ScheduleTransaction
    {
        return ScheduleTransaction::query()
            ->where('status', TransactionStatus::SCHEDULED)
            ->where('consumer_id', $consumerId)
            ->latest('schedule_date')
            ->first();
    }

    public function fetchScheduledOfConsumer(Consumer $consumer): Collection
    {
        return ScheduleTransaction::query()
            ->where('company_id', $consumer->company_id)
            ->where('consumer_id', $consumer->id)
            ->where('status', TransactionStatus::SCHEDULED)
            ->get();
    }

    public function createScheduledPifIfNotCreated(Consumer $consumer, ConsumerNegotiation $consumerNegotiation, PaymentProfile $paymentProfile): void
    {
        $isAlreadyScheduled = ScheduleTransaction::query()
            ->where('consumer_id', $consumer->id)
            ->where('company_id', $consumer->company_id)
            ->where('transaction_type', NegotiationType::PIF)
            ->where('status', TransactionStatus::SCHEDULED)
            ->exists();

        if ($isAlreadyScheduled) {
            ScheduleTransaction::query()
                ->where('consumer_id', $consumer->id)
                ->where('company_id', $consumer->company_id)
                ->where('transaction_type', NegotiationType::PIF)
                ->whereIn('status', [TransactionStatus::SCHEDULED, TransactionStatus::FAILED])
                ->update([
                    'payment_profile_id' => $paymentProfile->id,
                    'stripe_payment_detail_id' => $paymentProfile->stripePaymentDetail?->id,
                ]);

            return;
        }

        if ($consumerNegotiation->negotiation_type === NegotiationType::PIF) {
            $revenueShareFee = $this->companyMembershipService->fetchFee($consumer);

            $installmentAmount = (float) ($consumerNegotiation->counter_offer_accepted ? $consumerNegotiation->counter_one_time_amount : $consumerNegotiation->one_time_settlement);

            ScheduleTransaction::query()
                ->create([
                    'consumer_id' => $consumer->id,
                    'company_id' => $consumer->company_id,
                    'subclient_id' => $consumer->subclient_id,
                    'schedule_date' => $consumerNegotiation->counter_offer_accepted ? $consumerNegotiation->counter_first_pay_date : $consumerNegotiation->first_pay_date,
                    'payment_profile_id' => $paymentProfile->id,
                    'status' => TransactionStatus::SCHEDULED,
                    'status_code' => '111',
                    'amount' => $installmentAmount,
                    'transaction_type' => TransactionType::PIF,
                    'revenue_share_percentage' => $revenueShareFee,
                    'schedule_time' => now()->addMinutes(30)->toDateTimeString(),
                    'stripe_payment_detail_id' => $paymentProfile->stripePaymentDetail?->id,
                ]);
        }
    }

    public function createInstallmentsIfNotCreated(Consumer $consumer, ConsumerNegotiation $consumerNegotiation, PaymentProfile $paymentProfile): void
    {
        $isAlreadyScheduled = ScheduleTransaction::query()
            ->where('consumer_id', $consumer->id)
            ->where('company_id', $consumer->company_id)
            ->where('transaction_type', NegotiationType::INSTALLMENT)
            ->where('status', TransactionStatus::SCHEDULED)
            ->exists();

        if ($isAlreadyScheduled) {
            ScheduleTransaction::query()
                ->where('consumer_id', $consumer->id)
                ->where('company_id', $consumer->company_id)
                ->where('transaction_type', NegotiationType::INSTALLMENT)
                ->whereIn('status', [TransactionStatus::SCHEDULED, TransactionStatus::FAILED])
                ->update([
                    'payment_profile_id' => $paymentProfile->id,
                    'stripe_payment_detail_id' => $paymentProfile->stripePaymentDetail?->id,
                ]);

            return;
        }

        $counterOfferPrefix = $consumerNegotiation->counter_offer_accepted ? 'counter_' : '';
        $noOfInstallments = (int) $consumerNegotiation->{$counterOfferPrefix . 'no_of_installments'};
        $lastInstallmentAmount = (float) $consumerNegotiation->{$counterOfferPrefix . 'last_month_amount'};
        $firstPaymentDate = $consumerNegotiation->{$counterOfferPrefix . 'first_pay_date'};
        $installmentAmount = $consumerNegotiation->{$counterOfferPrefix . 'monthly_amount'};

        if ($firstPaymentDate->isPast() && ! $consumer->payment_setup) {
            $firstPaymentDate = now();
        }

        $paymentDate = $firstPaymentDate->clone();

        $carbonMethod = $consumerNegotiation->installment_type->getCarbonMethod();

        $firstDateIsEndOfMonth = $consumerNegotiation->installment_type === InstallmentType::MONTHLY
            && $paymentDate->isSameDay($paymentDate->clone()->endOfMonth());

        $installmentDetails = collect(range(1, $noOfInstallments))->map(fn ($numberOfInstallment): array => [
            'amount' => number_format((float) $installmentAmount, 2, thousands_separator: ''),
            'schedule_date' => $this->getScheduleDate($paymentDate, $carbonMethod, $numberOfInstallment - 1, $firstDateIsEndOfMonth),
        ]);

        if ($lastInstallmentAmount > 0) {
            $installmentDetails->push([
                'amount' => number_format($lastInstallmentAmount, 2, thousands_separator: ''),
                'schedule_date' => $this->getScheduleDate($paymentDate, $carbonMethod, $noOfInstallments, $firstDateIsEndOfMonth),
            ]);
        }

        $revenueShareFee = $this->companyMembershipService->fetchFee($consumer);

        $scheduleInsertArray = $installmentDetails->map(fn ($installment) => [
            'consumer_id' => $consumer->id,
            'company_id' => $consumer->company_id,
            'subclient_id' => $consumer->subclient_id,
            'schedule_date' => $installment['schedule_date'],
            'payment_profile_id' => $paymentProfile->id,
            'status' => TransactionStatus::SCHEDULED->value,
            'status_code' => '111',
            'amount' => $installment['amount'],
            'transaction_type' => NegotiationType::INSTALLMENT,
            'schedule_time' => now()->toTimeString(),
            'stripe_payment_detail_id' => $paymentProfile->stripePaymentDetail?->id,
            'revenue_share_percentage' => $revenueShareFee,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ])->all();

        ScheduleTransaction::query()->insert($scheduleInsertArray);
    }

    private function getScheduleDate(Carbon $date, string $carbonMethod, int $increment, bool $forceEndOfMonth): Carbon
    {
        return $date->clone()->{$carbonMethod}($increment)->when($forceEndOfMonth, fn (Carbon $date): Carbon => $date->endOfMonth());
    }
}

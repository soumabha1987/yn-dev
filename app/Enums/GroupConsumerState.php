<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Values;
use Illuminate\Database\Eloquent\Builder;

enum GroupConsumerState: string
{
    use Values;

    case ALL_ACTIVE = 'all_active';
    case NOT_VIEWED_OFFER = 'not_viewed_offer';
    case VIEWED_OFFER_BUT_NO_RESPONSE = 'viewed_offer_but_no_response';
    case OPEN_NEGOTIATIONS = 'open_negotiations';
    case NOT_RESPONDED_TO_COUNTER_OFFER = 'not_responded_to_counter_offer';
    case NEGOTIATED_PAYOFF_BUT_PENDING_PAYMENT = 'negotiated_payoff_but_pending_payment';
    case NEGOTIATED_PLAN_BUT_PENDING_PAYMENT = 'negotiated_plan_but_pending_payment';
    case FAILED_OR_SKIP_MORE_THAN_TWO_PAYMENTS_CONSECUTIVELY = 'failed_or_skip_more_than_two_payments_consecutively';
    case REPORTED_NOT_PAYING = 'reported_not_paying';
    case DISPUTED = 'disputed';
    case DEACTIVATED = 'deactivated';

    /**
     * @return array<string, string>
     */
    public static function displaySelectionBox(): array
    {
        return [
            self::ALL_ACTIVE->value => __('All active'),
            self::NOT_VIEWED_OFFER->value => __('Not Viewed Offer'),
            self::VIEWED_OFFER_BUT_NO_RESPONSE->value => __('Viewed offer (no response)'),
            self::OPEN_NEGOTIATIONS->value => __('Open Negotiations'),
            self::NOT_RESPONDED_TO_COUNTER_OFFER->value => __('Not responded to Counteroffer'),
            self::NEGOTIATED_PAYOFF_BUT_PENDING_PAYMENT->value => __('Negotiated Payoff/pending payment'),
            self::NEGOTIATED_PLAN_BUT_PENDING_PAYMENT->value => __('Negotiated Plan/pending payment'),
            self::FAILED_OR_SKIP_MORE_THAN_TWO_PAYMENTS_CONSECUTIVELY->value => __('Failed/Skipped more than 2 payments Consecutively'),
            self::REPORTED_NOT_PAYING->value => __('Reported not paying'),
            self::DISPUTED->value => __('Disputed'),
            self::DEACTIVATED->value => __('Deactivated'),
        ];
    }

    public function displayName(): string
    {
        return collect(self::displaySelectionBox())->get($this->value);
    }

    public function getBuilder(Builder $query): void
    {
        match ($this) {
            self::ALL_ACTIVE => $query->whereNotIn('status', [ConsumerStatus::DEACTIVATED, ConsumerStatus::DISPUTE, ConsumerStatus::NOT_PAYING]),
            self::NOT_VIEWED_OFFER => $query->whereIn('status', ConsumerStatus::notVerified()),
            self::VIEWED_OFFER_BUT_NO_RESPONSE => $query->where('status', ConsumerStatus::JOINED),
            self::OPEN_NEGOTIATIONS => $query->where('status', ConsumerStatus::PAYMENT_SETUP),
            self::NOT_RESPONDED_TO_COUNTER_OFFER => $query->where('counter_offer', true)->where('status', ConsumerStatus::PAYMENT_SETUP),

            self::NEGOTIATED_PAYOFF_BUT_PENDING_PAYMENT => $query
                ->where(['status' => ConsumerStatus::PAYMENT_ACCEPTED, 'payment_setup' => true])
                ->whereRelation('consumerNegotiation', 'negotiation_type', NegotiationType::PIF),

            self::NEGOTIATED_PLAN_BUT_PENDING_PAYMENT => $query
                ->where(['status' => ConsumerStatus::PAYMENT_ACCEPTED, 'payment_setup' => true])
                ->whereRelation('consumerNegotiation', 'negotiation_type', NegotiationType::INSTALLMENT),

            self::FAILED_OR_SKIP_MORE_THAN_TWO_PAYMENTS_CONSECUTIVELY => $query
                ->where(['status' => ConsumerStatus::PAYMENT_ACCEPTED, 'payment_setup' => true])
                ->whereHas('scheduledTransactions', function (Builder $query): void {
                    $query->where('updated_at', '>=', now()->subDays(30))
                        ->whereNot('status', TransactionStatus::SUCCESSFUL)
                        ->where(function (Builder $query): void {
                            $query->where('status', TransactionStatus::FAILED)
                                ->orWhereNotNull('previous_schedule_date');
                        });
                }),

            self::REPORTED_NOT_PAYING => $query->whereNotNull('reason_id'),
            self::DISPUTED => $query->whereNotNull('disputed_at'),
            self::DEACTIVATED => $query->where('status', ConsumerStatus::DEACTIVATED),
        };
    }
}

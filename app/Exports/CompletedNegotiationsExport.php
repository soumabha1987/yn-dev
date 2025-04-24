<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\NegotiationType;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CompletedNegotiationsExport implements FromCollection, WithHeadingRow, WithHeadings
{
    public function __construct(
        private Collection $consumers,
    ) {}

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->consumers->map(function (Consumer $consumer): array {
            /** @var ConsumerNegotiation $consumerNegotiation */
            $consumerNegotiation = $consumer->consumerNegotiation;

            [$promiseAmount, $promiseDate, $payOfBalance] = match (true) {
                $consumerNegotiation->negotiation_type === NegotiationType::PIF && $consumerNegotiation->offer_accepted => [
                    $consumerNegotiation->one_time_settlement,
                    $consumerNegotiation->first_pay_date,
                    $consumerNegotiation->one_time_settlement,
                ],
                $consumerNegotiation->negotiation_type === NegotiationType::PIF && $consumerNegotiation->counter_offer_accepted => [
                    $consumerNegotiation->counter_one_time_amount,
                    $consumerNegotiation->counter_first_pay_date,
                    $consumerNegotiation->counter_one_time_amount,
                ],
                $consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT && $consumerNegotiation->offer_accepted => [
                    $consumerNegotiation->monthly_amount,
                    $consumerNegotiation->first_pay_date,
                    $consumerNegotiation->negotiate_amount,
                ],
                $consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT && $consumerNegotiation->counter_offer_accepted => [
                    $consumerNegotiation->counter_monthly_amount,
                    $consumerNegotiation->counter_first_pay_date,
                    $consumerNegotiation->counter_negotiate_amount,
                ],
                default => [null, null, null],
            };

            return [
                'consumer_name' => $consumer->first_name . ' ' . $consumer->last_name,
                'master_account_number' => $consumer->member_account_number,
                'account_name' => $consumer->original_account_name,
                'sub_account_name' => $consumer->subclient_name,
                'placement_date' => $consumer->placement_date?->format('M d, Y'),
                'offer_type' => $consumerNegotiation->negotiation_type === NegotiationType::PIF ? __('Settle') : __('Plan'),
                'total_balance' => Number::currency((float) $consumer->total_balance),
                'pay_of_balance' => Number::currency((float) $payOfBalance),
                'promise_amount' => Number::currency((float) $promiseAmount),
                'promise_payment_date' => $promiseDate ? $promiseDate->format('M d, Y') : '',
            ];
        });
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            __('Consumer Name'),
            __('Account #'),
            __('Account Name'),
            __('Sub Account Name'),
            __('Placement Date'),
            __('Offer Type'),
            __('Beg Balance'),
            __('Negotiate Pay Off Balance'),
            __('Amount'),
            __('Promise Date'),
        ];
    }
}

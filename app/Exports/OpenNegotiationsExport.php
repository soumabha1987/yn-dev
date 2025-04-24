<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\NegotiationType;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Services\Consumer\DiscountService;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OpenNegotiationsExport implements FromCollection, WithHeadingRow, WithHeadings
{
    protected DiscountService $discountService;

    public function __construct(
        private Collection $consumers,
        private bool $isRecentlyCompletedNegotiation = false,
    ) {
        $this->discountService = app(DiscountService::class);
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->consumers->map(function (Consumer $consumer): array {
            /** @var ConsumerNegotiation $consumerNegotiation */
            $consumerNegotiation = $consumer->consumerNegotiation;

            $ourLastOffer = match (true) {
                $consumer->counter_offer => $consumerNegotiation->negotiation_type === NegotiationType::PIF
                    ? $consumerNegotiation->counter_one_time_amount : $consumerNegotiation->counter_monthly_amount,
                $consumerNegotiation->negotiation_type === NegotiationType::PIF => $this->discountService->fetchAmountToPayWhenPif($consumer)['discount'],
                $consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT => $this->discountService
                    ->fetchMonthlyAmount($consumer, $this->discountService->fetchAmountToPayWhenPpa($consumer)),
                default => null,
            };

            $consumerLastOffer = $consumerNegotiation->negotiation_type === NegotiationType::PIF
                ? $consumerNegotiation->one_time_settlement : $consumerNegotiation->monthly_amount;

            $data = collect([
                'offer_date' => $consumerNegotiation->created_at->formatWithTimezone(),
                'master_account_number' => $consumer->member_account_number,
                'name' => $consumer->first_name . ' ' . $consumer->last_name,
                'original_account_name' => $consumer->original_account_name,
                'subclient_name' => $consumer->subclient_name,
                'placement_date' => $consumer->placement_date ? $consumer->placement_date->format('M d, Y') : '',
                'payment_setup' => $consumer->payment_setup ? __('Yes') : __('No'),
                'our_last_offer' => Number::currency((float) $ourLastOffer),
                'consumer_last_offer' => Number::currency((float) $consumerLastOffer),
                'pending_status' => $consumer->counter_offer ? __('Consumer Response') : __('Member Response'),
            ]);

            if ($this->isRecentlyCompletedNegotiation) {
                $negotiated_balance = match (true) {
                    $consumer->consumerNegotiation->negotiation_type === NegotiationType::PIF => $consumer->consumerNegotiation->counter_one_time_amount ?? $consumer->consumerNegotiation->one_time_settlement ?? 0,

                    $consumer->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT => $consumer->consumerNegotiation->counter_negotiate_amount ?? $consumer->consumerNegotiation->negotiate_amount ?? 0,

                    default => 0
                };

                $data->putAfter([
                    'negotiated_balance' => Number::currency((float) $negotiated_balance),
                ], 'consumer_last_offer');
            }

            return $data->all();
        });
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        $headings = collect([
            __('Offer Date'),
            __('Account #'),
            __('Consumer Name'),
            __('Account Name'),
            __('Sub Account Name'),
            __('Placement Date'),
            __('Payment Profile'),
            __('Our Last Offer'),
            __('Consumer Last Offer'),
            __('Pending Status'),
        ]);

        if ($this->isRecentlyCompletedNegotiation) {
            return $headings->putAfter([__('Negotiated Balance')], __('Consumer Last Offer'), true)->all();
        }

        return $headings->all();
    }
}

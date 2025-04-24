<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Consumer;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ConsumersPayTermOfferExport implements FromCollection, WithHeadings
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

            $amount = null;
            if ($consumer->pay_setup_discount_percent && $consumer->min_monthly_pay_percent) {
                $discountBalance = $consumer->total_balance - ($consumer->total_balance * $consumer->pay_setup_discount_percent / 100);
                $amount = $discountBalance * $consumer->min_monthly_pay_percent / 100;
            }

            return [
                'account_number' => $consumer->member_account_number,
                'consumer_name' => str($consumer->first_name . ' ' . $consumer->last_name)->title(),
                'subclient_name' => $consumer->subclient_name ? str($consumer->subclient_name . '/' . $consumer->subclient_account_number)->title() : '',
                'current_balance' => Number::currency($consumer->total_balance ?? 0),
                'settlement_offer' => $consumer->pif_discount_percent ? Number::percentage($consumer->pif_discount_percent, 2) : '',
                'payment_plan_offer' => $consumer->pay_setup_discount_percent ? Number::percentage($consumer->pay_setup_discount_percent, 2) : '',
                'min_monthly_payment' => $amount ? Number::currency((float) $amount) : '',
                'max_first_pay_days' => $consumer->max_days_first_pay ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            __('Master Account #'),
            __('Consumer Name'),
            __('Sub Name/ID'),
            __('Current Balance'),
            __('Settlement Offer'),
            __('Plan Balance Offer'),
            __('Min Monthly Payment'),
            __('Days/1st Payment'),
        ];
    }
}

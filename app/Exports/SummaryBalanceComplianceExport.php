<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\ConsumerStatus;
use App\Enums\NegotiationType;
use App\Models\Consumer;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SummaryBalanceComplianceExport implements FromCollection, WithHeadingRow, WithHeadings
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
            $status = match (true) {
                $consumer->status !== ConsumerStatus::PAYMENT_ACCEPTED => match ($consumer->status) {
                    ConsumerStatus::UPLOADED => __('Offer Delivered'),
                    ConsumerStatus::JOINED => __('Offer Viewed'),
                    ConsumerStatus::PAYMENT_SETUP => __('In Negotiations'),
                    ConsumerStatus::SETTLED => __('Settled/Paid'),
                    ConsumerStatus::DISPUTE => __('Disputed'),
                    ConsumerStatus::NOT_PAYING => __('Reported Not Paying'),
                    ConsumerStatus::PAYMENT_DECLINED => __('Negotiations Closed'),
                    ConsumerStatus::DEACTIVATED => __('Deactivated'),
                    ConsumerStatus::HOLD => __('Account in Hold'),
                    default => __('N/A'),
                },
                $consumer->payment_setup => __('Active Payment Plan'),
                $consumer->consumerNegotiation?->negotiation_type === NegotiationType::PIF => __('Agreed Settlement/Pending Payment'),
                $consumer->consumerNegotiation?->negotiation_type === NegotiationType::INSTALLMENT => __('Agreed Payment Plan/Pending Payment'),
                default => __('N/A'),
            };

            $inActiveStatues = [ConsumerStatus::DEACTIVATED, ConsumerStatus::DISPUTE, ConsumerStatus::NOT_PAYING];

            $ynAccountStatus = match (true) {
                in_array($consumer->status, $inActiveStatues) => __('No Active With Member'),
                in_array($consumer->status->value, ConsumerStatus::notVerified()) => __('Never Seen'),
                default => __('Active with Member'),
            };

            return [
                'original_account_number' => $consumer->account_number,
                'last_name' => $consumer->last_name,
                'date_of_birth' => $consumer->dob->format('M d, Y'),
                'last4ssn' => $consumer->last4ssn,
                'account_name' => $consumer->original_account_name,
                'last_negotiation_status' => $status,
                'yn_account_status' => $ynAccountStatus,
                'dispute_no_pay_status' => in_array($consumer->status, $inActiveStatues),
                'current_and_last_member_name' => $consumer->company->company_name,
                'last_recent_member' => $consumer->company->company_name,
                'beginning_balance' => Number::currency((float) $consumer->total_balance),
                'total_payments_made' => Number::currency((float) $consumer->total_balance - $consumer->current_balance),
                'current_balance' => Number::currency((float) $consumer->current_balance),
            ];
        });
    }

    public function headings(): array
    {
        return [
            __('Original Account Number'),
            __('Last Name'),
            __('Date Of Birth'),
            __('Last 4 Ssn'),
            __('Account Name'),
            __('Last Negotiation Status'),
            __('YN - Account Status'),
            __('Dispute No Pay Status'),
            __('Current / Last Member Name'),
            __('Last Recent Member'),
            __('Beginning Balance'),
            __('Total Payments Made'),
            __('Current Balance'),
        ];
    }
}

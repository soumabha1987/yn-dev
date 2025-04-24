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

class finalPaymentsBalanceSummaryExport implements FromCollection, WithHeadingRow, WithHeadings
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

            return [
                'first_name' => $consumer->first_name,
                'last_name' => $consumer->last_name,
                'date_of_birth' => $consumer->dob->format('M d, Y'),
                'last4ssn' => $consumer->last4ssn,
                'account_balance' => Number::currency((float) $consumer->current_balance),
                'account_name' => $consumer->original_account_name,
                'account_number' => $consumer->account_number,
                'member_account_number' => $consumer->member_account_number,
                'reference_number' => $consumer->reference_number,
                'statement_number' => $consumer->statement_id_number,
                'subclient_id' => $consumer->subclient_id,
                'subclient_name' => $consumer->subclient_name,
                'subclient_number' => $consumer->subclient_account_number,
                'placement_date' => $consumer->placement_date?->format('M d, Y'),
                'expiry_date' => $consumer->expiry_date?->format('M d, Y'),
                'current_negotiation_status' => $status,
                'dispute_no_pay' => in_array($consumer->status, [ConsumerStatus::DISPUTE, COnsumerStatus::DEACTIVATED, ConsumerStatus::NOT_PAYING]),
                'beginning_balance' => Number::currency((float) $consumer->total_balance),
                'total_payments_made' => Number::currency((float) $consumer->total_balance - $consumer->current_balance),
                'current_balance' => Number::currency((float) $consumer->current_balance),
            ];
        });
    }

    public function headings(): array
    {
        return [
            __('First Name'),
            __('Last Name'),
            __('Date Of Birth'),
            __('Last 4 Ssn'),
            __('Account Balance'),
            __('Account Name'),
            __('Original Account Number'),
            __('Member Account Number'),
            __('Reference Number'),
            __('Statement Number'),
            __('Sub Identification (ID)'),
            __('Subclient Name'),
            __('Subclient Account Number'),
            __('Placement Date'),
            __('Expiry Date'),
            __('Current Negotiation Status'),
            __('Dispute/No Pay Status'),
            __('Beginning Balance'),
            __('Total Payments Made'),
            __('Current Balance'),
        ];
    }
}

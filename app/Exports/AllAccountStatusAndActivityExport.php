<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\ConsumerStatus;
use App\Enums\NegotiationType;
use App\Enums\TransactionStatus;
use App\Models\Consumer;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AllAccountStatusAndActivityExport implements FromCollection, WithHeadingRow, WithHeadings
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
            $status = $this->consumerStatus($consumer);

            $offerSource = match (true) {
                $consumer->pif_discount_percent !== null => __('Individual'),
                $consumer->subclient?->pif_balance_discount_percent !== null => __('Subclient'),
                $consumer->company->pif_balance_discount_percent !== null => __('Master'),
                default => '',
            };

            $scheduleTransactionsData = $this->scheduleTransactionsData($consumer);
            $consumerNegotiateData = filled($consumer->consumerNegotiation)
                ? $this->consumerNegotiateData($consumer)
                : [];

            return [
                'yn_status' => $consumer->status->displayName(),
                'first_name' => $consumer->first_name,
                'last_name' => $consumer->last_name,
                'date_of_birth' => $consumer->dob->format('M d, Y'),
                'last4ssn' => $consumer->last4ssn,
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
                'email' => $consumer->email1,
                'email_status' => $consumer->consumerProfile?->email_permission ? __('Opt In') : __('Opt Out'),
                'mobile' => $consumer->mobile1,
                'mobile_status' => $consumer->consumerProfile?->text_permission ? __('Opt In') : __('Opt Out'),
                'address1' => $consumer->address1,
                'address2' => $consumer->address2,
                'city' => $consumer->city,
                'state' => $consumer->state,
                'zip' => $consumer->zip,
                'passthrough1' => $consumer->pass_through1,
                'passthrough2' => $consumer->pass_through2,
                'passthrough3' => $consumer->pass_through3,
                'passthrough4' => $consumer->pass_through4,
                'passthrough5' => $consumer->pass_through5,
                'current_negotiation_status' => $status,
                'beginning_balance' => Number::currency((float) $consumer->total_balance),
                'offer_source' => $offerSource,
                'pif_offer_discount' => Number::percentage(
                    $consumer->pif_discount_percent
                        ?? $consumer->subclient->pif_balance_discount_percent
                        ?? $consumer->company->pif_balance_discount_percent
                ),
                'negotiated_pif_discount' => $consumerNegotiateData && $consumer->consumerNegotiation?->negotiation_type === NegotiationType::PIF && $consumerNegotiateData['one_time_amount']
                    ? Number::currency((float) $consumerNegotiateData['one_time_amount'])
                    : '',
                'pif_due_date' => $consumerNegotiateData && $consumer->consumerNegotiation?->negotiation_type === NegotiationType::PIF && $consumerNegotiateData['first_pay_date']
                    ? $consumerNegotiateData['first_pay_date']->format('M d, Y')
                    : '',
                'pif_settlement_amount' => $consumerNegotiateData && $consumer->consumerNegotiation?->negotiation_type === NegotiationType::PIF
                    ? Number::currency((float) $consumer->total_balance - $consumer->current_balance)
                    : '',
                'ppa_discount_percentage' => Number::percentage(
                    $consumer->pay_setup_discount_percent
                    ?? $consumer->subclient->ppa_balance_discount_percent
                    ?? $consumer->company->ppa_balance_discount_percent
                ),
                'ppa_monthly_payment_percentage' => Number::percentage(
                    $consumer->min_monthly_pay_percent
                    ?? $consumer->subclient->min_monthly_pay_percent
                    ?? $consumer->company->min_monthly_pay_percent
                ),
                'first_pay_days' => $consumer->max_days_first_pay ?? $consumer->subclient->max_days_first_pay ?? $consumer->company->max_days_first_pay,
                'negotiate_pay_off_balance' => $consumerNegotiateData && $consumer->consumerNegotiation?->negotiation_type === NegotiationType::INSTALLMENT && $consumerNegotiateData['negotiate_amount']
                    ? Number::currency((float) $consumerNegotiateData['negotiate_amount'])
                    : '',
                'negotiate_monthly_payment' => $consumerNegotiateData && $consumer->consumerNegotiation?->negotiation_type === NegotiationType::INSTALLMENT && $consumerNegotiateData['monthly_amount']
                    ? Number::currency((float) $consumerNegotiateData['monthly_amount'])
                    : '',
                'negotiate_first_pay_date' => $consumerNegotiateData && $consumer->consumerNegotiation?->negotiation_type === NegotiationType::INSTALLMENT && $consumerNegotiateData['first_pay_date']
                    ? $consumerNegotiateData['first_pay_date']->format('M d, Y')
                    : '',
                'total_payment_to_pay_off' => $consumerNegotiateData && $consumer->consumerNegotiation?->negotiation_type === NegotiationType::INSTALLMENT && $consumerNegotiateData['no_of_installment']
                    ? $consumerNegotiateData['no_of_installment']
                    : '',
                'negotiate_by' => $consumer->company->company_name,
                'total_payments_to_date' => Number::currency((float) $consumer->total_balance - $consumer->current_balance),
                'current_balance' => Number::currency((float) $consumer->current_balance),
                'plan_last_payment_date' => $scheduleTransactionsData['last_successful_schedule_transaction']
                    ? $scheduleTransactionsData['last_successful_schedule_transaction']->schedule_date->format('M d, Y')
                    : '',
                'plan_last_payment_amount' => $scheduleTransactionsData['last_successful_schedule_transaction']
                    ? Number::currency((float) $scheduleTransactionsData['last_successful_schedule_transaction']->amount)
                    : '',
                'plan_next_payment_date' => $scheduleTransactionsData['next_schedule_transaction']
                    ? $scheduleTransactionsData['next_schedule_transaction']->schedule_date->format('M d, Y') : '',
                'plan_next_payment_amount' => $scheduleTransactionsData['next_schedule_transaction']
                    ? Number::currency((float) $scheduleTransactionsData['next_schedule_transaction']->amount)
                    : '',
                'total_skip_schedule' => $scheduleTransactionsData['total_skip_schedule'],
                'total_consumer_change_date' => $scheduleTransactionsData['total_consumer_change_date_schedule'],
                'total_creditor_change_date' => $scheduleTransactionsData['total_creditor_change_date_schedule'],
                'total_successful_schedule' => $scheduleTransactionsData['total_successful_schedule'],
            ];
        });
    }

    public function headings(): array
    {
        return [
            __('YN Status'),
            __('First Name'),
            __('Last Name'),
            __('Date Of Birth'),
            __('Last4Ssn'),
            __('Account Name'),
            __('Original Account Number'),
            __('Member Account Number'),
            __('Reference Number'),
            __('Statement Number'),
            __('Sub Identification(ID)'),
            __('Subclient Name'),
            __('Subclient Number'),
            __('Placement Date'),
            __('Expiry Date'),
            __('Consumer Email'),
            __('Email Status'),
            __('Phone Number'),
            __('Mobile Status'),
            __('Address1'),
            __('Address2'),
            __('City'),
            __('State'),
            __('Zip'),
            __('Passthrough1'),
            __('Passthrough2'),
            __('Passthrough3'),
            __('Passthrough4'),
            __('Passthrough5'),
            __('Current Negotiation Status'),
            __('Beginning Balance'),
            __('Offer Source'),
            __('Pay in Full Discount'),
            __('Negotiated Pay in Full Amount'),
            __('Pay in Full Due Date'),
            __('Pay in Full Settlement Payment'),
            __('Plan-1st Offer Payment-Discount %'),
            __('Plan-1st Offer Payment-Monthly-Balance %'),
            __('Plan-1st Offer Payment Days Pay'),
            __('Plan Negotiated Payoff Balance'),
            __('Plan Negotiated Monthly Payment'),
            __('Plan Negotiated 1st Payment Due Date'),
            __('Total Payments to Pay Off'),
            __('Negotiated By'),
            __('Total Payments to Date'),
            __('Current Balance'),
            __('Plan Date of Last Payment'),
            __('Plan Last Amount'),
            __('Plan Next Payment Due Date'),
            __('Plan Next Payment Amount'),
            __('Pay Plan Skipped Payments'),
            __('Pay Plan Consumer Changed Payment Dates'),
            __('Pay Plan Creditor Changed Payment Dates'),
            __('# Total Successful Payment'),
        ];
    }

    private function consumerStatus(Consumer $consumer): string
    {
        return match (true) {
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
    }

    private function scheduleTransactionsData(Consumer $consumer): array
    {
        $data['last_successful_schedule_transaction'] = $consumer
            ->scheduledTransactions
            ->where('schedule_date', '<', now())
            ->where('status', TransactionStatus::SUCCESSFUL)
            ->sortBy('schedule_date')
            ->first();

        $data['next_schedule_transaction'] = $consumer
            ->scheduledTransactions
            ->where('schedule_date', '>', now())
            ->where('status', TransactionStatus::SCHEDULED)
            ->sortBy('schedule_date')
            ->first();

        $data['total_skip_schedule'] = $consumer->scheduledTransactions
            ->where('status', TransactionStatus::RESCHEDULED)
            ->count();

        $data['total_creditor_change_date_schedule'] = $consumer->scheduledTransactions
            ->where('status', TransactionStatus::CREDITOR_CHANGE_DATE)
            ->count();

        $data['total_consumer_change_date_schedule'] = $consumer->scheduledTransactions
            ->where('status', TransactionStatus::CONSUMER_CHANGE_DATE)
            ->count();

        $data['total_successful_schedule'] = $consumer->scheduledTransactions
            ->where('status', TransactionStatus::SUCCESSFUL)
            ->count();

        return $data;
    }

    private function consumerNegotiateData(Consumer $consumer): array
    {
        $consumerNegotiation = $consumer->consumerNegotiation;

        if ($consumer->offer_accepted && $consumerNegotiation->counter_offer_accepted) {
            return [
                'one_time_amount' => $consumerNegotiation->counter_one_time_amount,
                'negotiate_amount' => $consumerNegotiation->counter_negotiate_amount,
                'monthly_amount' => $consumerNegotiation->counter_monthly_amount,
                'first_pay_date' => $consumerNegotiation->counter_first_pay_date,
                'no_of_installment' => $consumerNegotiation->counter_no_of_installments + ($consumerNegotiation->counter_last_month_amount ? 1 : 0),
            ];
        }

        return [
            'one_time_amount' => $consumerNegotiation->one_time_settlement,
            'negotiate_amount' => $consumerNegotiation->negotiate_amount,
            'monthly_amount' => $consumerNegotiation->monthly_amount,
            'first_pay_date' => $consumerNegotiation->first_pay_date,
            'no_of_installment' => $consumerNegotiation->no_of_installments + ($consumerNegotiation->last_month_amount ? 1 : 0),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ManageConsumers\ConsumerProfile;

use App\Enums\MembershipTransactionStatus;
use App\Enums\TransactionStatus;
use App\Livewire\Traits\WithPagination;
use App\Models\Consumer;
use App\Models\ScheduleTransaction;
use App\Models\YnTransaction;
use App\Services\ConsumerNegotiationService;
use App\Services\MembershipPaymentProfileService;
use App\Services\PartnerService;
use App\Services\ScheduleTransactionService;
use App\Services\TilledPaymentService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithoutUrlPagination;

class ScheduledTransactions extends Component
{
    use Reschedule;
    use SkipPayments;
    use WithoutUrlPagination;
    use WithPagination;

    public Consumer $consumer;

    public function mount(): void
    {
        $this->consumer->loadMissing(['consumerNegotiation', 'company.activeCompanyMembership.membership']);
    }

    public function cancelScheduled(ScheduleTransaction $scheduleTransaction): void
    {
        $this->consumer->loadMissing('company.partner');

        // Handling decimal amounts: Direct multiplication by 100 and division by 100 is avoided
        $amount = intval((float) $scheduleTransaction->amount * (float) $scheduleTransaction->revenue_share_percentage);

        if ($amount > 0) {
            $membershipPaymentProfile = app(MembershipPaymentProfileService::class)->fetchByCompany($this->consumer->company_id);

            if (! $membershipPaymentProfile) {
                $this->error(__('Something went wrong!'));

                return;
            }

            $response = app(TilledPaymentService::class)->createPaymentIntents($amount, $membershipPaymentProfile->tilled_payment_method_id);

            $lastYnTransaction = YnTransaction::query()->latest()->value('rnn_invoice_id');

            $rnnInvoiceId = $lastYnTransaction ? $lastYnTransaction + 1 : 5000;

            $partnerRevenueShare = 0;

            if ($this->consumer->company->partner_id) {
                $partnerRevenueShare = app(PartnerService::class)->calculatePartnerRevenueShare($this->consumer->company->partner, $amount / 100);
            }

            $ynTransaction = new YnTransaction([
                'company_id' => $this->consumer->company_id,
                'amount' => number_format($amount / 100, 2, thousands_separator: ''),
                'response' => $response,
                'billing_cycle_start' => now()->toDateTimeString(),
                'billing_cycle_end' => now()->toDateTimeString(),
                'email_count' => 0,
                'sms_count' => 0,
                'phone_no_count' => 0,
                'email_cost' => 0,
                'sms_cost' => 0,
                'rnn_invoice_id' => $rnnInvoiceId,
                'schedule_transaction_id' => $scheduleTransaction->id,
                'reference_number' => mt_rand(100000000, 999999999),
                'partner_revenue_share' => number_format($partnerRevenueShare, 2, thousands_separator: ''),
            ]);

            $transactionStatus = optional($response)['status'];

            $ynTransaction->status = (! $transactionStatus || ! in_array($transactionStatus, ['processing', 'succeeded']))
                ? MembershipTransactionStatus::FAILED
                : MembershipTransactionStatus::SUCCESS;

            if ($ynTransaction->status === MembershipTransactionStatus::FAILED) {
                $this->error(data_get($response, 'last_payment_error.message', __('Something went wrong!')));

                return;
            }

            $this->cancelTransaction($scheduleTransaction);

            $ynTransaction->save();

            return;
        }

        $this->cancelTransaction($scheduleTransaction);
    }

    private function cancelTransaction(ScheduleTransaction $scheduleTransaction): void
    {
        $scheduleTransaction->update(['status' => TransactionStatus::CANCELLED]);

        $this->consumer->update([
            'current_balance' => max(0, ((float) $this->consumer->current_balance) - ((float) $scheduleTransaction->amount)),
        ]);

        app(ConsumerNegotiationService::class)
            ->updateAfterSuccessFullInstallmentPayment($this->consumer->consumerNegotiation, (float) $scheduleTransaction->amount);

        $this->success(__('Consumer payment canceled.'));

        $this->dispatch('refresh-please');
    }

    public function render(): View
    {
        return view('livewire.creditor.manage-consumers.consumer-profile.scheduled-transactions')
            ->with(
                'scheduledTransactions',
                app(ScheduleTransactionService::class)->fetchScheduledOfConsumer($this->consumer->id, $this->perPage)
            );
    }
}

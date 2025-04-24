<?php

declare(strict_types=1);

namespace App\Livewire\Consumer;

use App\Enums\ConsumerStatus;
use App\Enums\InstallmentType;
use App\Enums\MerchantName;
use App\Enums\TransactionStatus;
use App\Livewire\Consumer\Traits\Agreement;
use App\Livewire\Consumer\Traits\CreditorDetails;
use App\Models\Consumer;
use App\Models\ScheduleTransaction;
use App\Services\Consumer\AuthorizePaymentService;
use App\Services\Consumer\MerchantService;
use App\Services\Consumer\ScheduleTransactionService;
use App\Services\Consumer\StripePaymentService;
use App\Services\Consumer\TilledPaymentService;
use App\Services\Consumer\TransactionService;
use App\Services\Consumer\USAEpayPaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.consumer.app-layout')]
class SchedulePlan extends Component
{
    use Agreement;
    use CreditorDetails;

    public Consumer $consumer;

    /**
     * @var array{
     *     subclient_name: string,
     *     company_name: string,
     *     contact_person_phone: string,
     *     email: string,
     *     custom_content: string
     * }|array
     */
    public array $creditorDetails = [];

    public string $new_date = '';

    protected ScheduleTransactionService $scheduleTransactionService;

    public function __construct()
    {
        $this->scheduleTransactionService = app(ScheduleTransactionService::class);
    }

    public function mount(): void
    {
        $this->new_date = now()->addDay()->toDateString();

        $this->consumer->loadMissing(['paymentProfile', 'consumerNegotiation']);

        if ($this->consumer->status === ConsumerStatus::DEACTIVATED || ! $this->consumer->offer_accepted) {
            $this->redirectRoute('consumer.account', navigate: true);

            return;
        }

        if ($this->consumer->paymentProfile === null || $this->consumer->consumerNegotiation === null) {
            $this->error($this->consumer->paymentProfile === null ? __('Please finish your payment setup first.') : __('There is no active plan.'));
            $this->redirectRoute('consumer.account', navigate: true);

            return;
        }

        $this->creditorDetails = $this->setCreditorDetails($this->consumer);
    }

    public function payInstallmentAmount(ScheduleTransaction $scheduleTransaction): void
    {
        $this->consumer->loadMissing(['company', 'paymentProfile', 'subclient', 'consumerNegotiation']);

        $merchant = app(MerchantService::class)
            ->getMerchant($this->consumer, $this->consumer->paymentProfile->method)
            ->first();

        $isTransactionSuccessful = match ($merchant->merchant_name) {
            MerchantName::AUTHORIZE => app(AuthorizePaymentService::class)->payInstallment($scheduleTransaction, $merchant, $this->consumer, $this->consumer->consumerNegotiation),
            MerchantName::USA_EPAY => app(USAEpayPaymentService::class)->payInstallment($scheduleTransaction, $merchant, $this->consumer, $this->consumer->consumerNegotiation),
            MerchantName::STRIPE => app(StripePaymentService::class)->payInstallment($scheduleTransaction, $merchant, $this->consumer, $this->consumer->consumerNegotiation),
            MerchantName::YOU_NEGOTIATE => app(TilledPaymentService::class)->payInstallment($scheduleTransaction, $this->consumer, $this->consumer->consumerNegotiation),
        };

        $handleSuccess = $isTransactionSuccessful
            ? function () {
                $this->success(__('Way to knock out your payment. Payment processed and you are good to go.'));
                $this->js('$confetti()');
            }
        : fn () => $this->error(__("The plan  payment method didn't work, please try again or update your method to knock out this payment. You got this."));

        $handleSuccess();

        $this->dispatch('close-confirmation-box');
    }

    public function payRemainingAmount(): void
    {
        $this->consumer->loadMissing(['company', 'paymentProfile', 'subclient', 'consumerNegotiation']);

        $merchant = app(MerchantService::class)
            ->getMerchant($this->consumer, $this->consumer->paymentProfile->method)
            ->first();

        $scheduleTransactions = $this->scheduleTransactionService->getForPayRemainingBalance($this->consumer->id);

        $isTransactionSuccessful = match ($merchant->merchant_name) {
            MerchantName::AUTHORIZE => app(AuthorizePaymentService::class)->payRemainingAmount($merchant, $this->consumer, $this->consumer->consumerNegotiation, $scheduleTransactions),
            MerchantName::USA_EPAY => app(USAEpayPaymentService::class)->payRemainingAmount($merchant, $this->consumer, $this->consumer->consumerNegotiation, $scheduleTransactions),
            MerchantName::STRIPE => app(StripePaymentService::class)->payRemainingAmount($merchant, $this->consumer, $this->consumer->consumerNegotiation, $scheduleTransactions),
            MerchantName::YOU_NEGOTIATE => app(TilledPaymentService::class)->payRemainingAmount($this->consumer, $this->consumer->consumerNegotiation, $scheduleTransactions),
        };

        $handleSuccess = $isTransactionSuccessful
            ? function () {
                $this->success(__('Way to knock out your payment. Payment processed and you are good to go.'));
                $this->js('$confetti()');
            }
        : fn () => $this->error(__("The plan  payment method didn't work, please try again or update your method to knock out this payment. You got this."));

        $handleSuccess();

        $this->dispatch('close-confirmation-box');
    }

    public function reschedule(ScheduleTransaction $scheduleTransaction): void
    {
        if ($scheduleTransaction->status !== TransactionStatus::FAILED) {
            $this->error(__('This change/action was already processed/completed.'));

            return;
        }

        $rescheduleTransaction = $scheduleTransaction->replicate(['last_attempted_at', 'attempt_count'])->fill([
            'schedule_date' => today()->toDateString(),
            'status' => TransactionStatus::SCHEDULED,
            'previous_schedule_date' => $scheduleTransaction->schedule_date->toDateString(),
        ]);

        $rescheduleTransaction->save();

        $scheduleTransaction->update(['status' => TransactionStatus::RESCHEDULED]);

        $this->success(__('Your payment is rescheduled, you will get email notification once payment processed.'));
    }

    public function skipPayment(ScheduleTransaction $scheduleTransaction): void
    {
        if ($scheduleTransaction->schedule_date->isSameDay(today())) {
            $this->dispatch('close-confirmation-box');

            return;
        }

        $this->consumer->increment('skip_schedules');

        $lastScheduleTransaction = $this->scheduleTransactionService->lastScheduled($this->consumer->id);

        /** @var InstallmentType $installmentType */
        $installmentType = $this->consumer->consumerNegotiation->installment_type;

        $firstDateIsEndOfMonth = $installmentType === InstallmentType::MONTHLY
            && $lastScheduleTransaction->schedule_date->isSameDay($lastScheduleTransaction->schedule_date->endOfMonth());

        $scheduleTransaction->update([
            'previous_schedule_date' => $scheduleTransaction->schedule_date->toDateString(),
            'schedule_date' => $this->getScheduleDate($lastScheduleTransaction->schedule_date, $installmentType, $firstDateIsEndOfMonth)->toDateString(),
            'status' => TransactionStatus::SCHEDULED,
        ]);

        $this->success(__('Your payment has been skipped.'));

        $this->dispatch('close-confirmation-box');
    }

    private function getScheduleDate(Carbon $date, InstallmentType $installmentType, bool $forceEndOfMonth): Carbon
    {
        return $date->{$installmentType->getCarbonMethod()}()->when($forceEndOfMonth, fn (Carbon $date): Carbon => $date->endOfMonth());
    }

    public function updateScheduleDate(ScheduleTransaction $scheduleTransaction): void
    {
        $nextScheduleTransaction = $this->scheduleTransactionService
            ->nextScheduled($this->consumer->id, $scheduleTransaction->schedule_date->toDateString());

        $validatedData = $this->validate(['new_date' => [
            'required',
            'date',
            'date_format:Y-m-d',
            'after_or_equal:today',
            Rule::unique(ScheduleTransaction::class, 'schedule_date')->where('id', $scheduleTransaction->id),
            Rule::when($nextScheduleTransaction, fn () => ['before:' . $nextScheduleTransaction->schedule_date->toDateString()]),
        ]]);

        $rescheduleTransaction = $scheduleTransaction->replicate(['last_attempted_at', 'attempt_count'])->fill([
            'schedule_date' => $validatedData['new_date'],
            'status' => TransactionStatus::SCHEDULED,
            'previous_schedule_date' => $scheduleTransaction->schedule_date->toDateString(),
        ]);

        $rescheduleTransaction->save();

        $scheduleTransaction->update(['status' => TransactionStatus::CONSUMER_CHANGE_DATE]);

        $this->success(__('Your payment is rescheduled, you will get email notification once payment processed.'));

        $this->new_date = now()->addDay()->toDateString();

        $this->dispatch('close-dialog');
    }

    public function render(): View
    {
        [$cancelledScheduledTransactions, $scheduledTransactions] = $this->scheduleTransactionService->fetchByConsumer($this->consumer->id)
            ->partition(fn (ScheduleTransaction $scheduleTransaction) => $scheduleTransaction->status === TransactionStatus::CANCELLED);

        return view('livewire.consumer.schedule-plan')
            ->with([
                'scheduleTransactions' => $scheduledTransactions,
                'cancelledScheduledTransactions' => $cancelledScheduledTransactions,
                'transactions' => app(TransactionService::class)->fetchSuccessTransactions($this->consumer->id),
            ])
            ->title(__('Schedule Plan'));
    }
}

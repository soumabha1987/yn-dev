<?php

declare(strict_types=1);

namespace App\Livewire\Consumer\MyAccount;

use App\Enums\ConsumerStatus;
use App\Enums\NegotiationType;
use App\Enums\TransactionStatus;
use App\Livewire\Consumer\Forms\ConsumerOfferForm;
use App\Livewire\Consumer\Traits\Agreement;
use App\Livewire\Consumer\Traits\MyAccounts\Offers;
use App\Livewire\Consumer\Traits\ValidateCounterOffer;
use App\Models\Consumer;
use App\Models\ScheduleTransaction;
use App\Services\CompanyMembershipService;
use App\Services\Consumer\ConsumerNegotiationService;
use App\Services\Consumer\DiscountService;
use App\Services\Consumer\PaymentProfileService;
use App\Services\Consumer\ScheduleTransactionService;
use App\Services\ConsumerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class ViewOffer extends Component
{
    use Agreement;
    use Offers;
    use ValidateCounterOffer;

    public Consumer $consumer;

    public string $view = '';

    public string $tabClasses = '';

    public string $tabLabel = '';

    public ConsumerOfferForm $form;

    protected ConsumerService $consumerService;

    protected DiscountService $discountService;

    public float $minimumPifDiscountedAmount;

    public float $minimumPpaDiscountedAmount;

    public function __construct()
    {
        $this->consumerService = app(ConsumerService::class);

        $this->discountService = app(DiscountService::class);
    }

    public function mount(): void
    {
        $this->minimumPpaDiscountedAmount = $this->discountService->fetchAmountToPayWhenPpa($this->consumer);
        $this->minimumPifDiscountedAmount = $this->discountService->fetchAmountToPayWhenPif($this->consumer)['discount'];
        $this->form->counter_first_pay_date = $this->consumer->consumerNegotiation->counter_first_pay_date?->toDateString()
            ?? $this->consumer->consumerNegotiation->first_pay_date?->toDateString() ?? '';
    }

    public function acceptPayment(): void
    {
        $consumerNegotiation = app(ConsumerNegotiationService::class)->fetchActive($this->consumer->id);

        if (! $consumerNegotiation) {
            $this->error(__('Your response was already processed, please check your dashboard . If you need help please contact help@younegotiate.com!'));

            return;
        }

        $consumerNegotiation->update([
            'counter_offer_accepted' => true,
            'approved_by' => $this->consumer->first_name . ' ' . $this->consumer->last_name,
        ]);

        $this->consumer->update([
            'status' => ConsumerStatus::PAYMENT_ACCEPTED,
            'offer_accepted' => true,
        ]);

        Cache::put(
            'new_offer_count_' . $this->consumer->company_id,
            $newOfferCount = $this->consumerService->getCountOfNewOffer($this->consumer->company_id),
            now()->addHour(),
        );

        $this->dispatch('new-offer-count-updated', $newOfferCount);

        $paymentProfile = app(PaymentProfileService::class)->findByConsumer($this->consumer->id);

        if (! $paymentProfile) {
            $this->redirectRoute('consumer.payment', ['consumer' => $this->consumer->id], navigate: true);

            return;
        }

        app(ScheduleTransactionService::class)->deleteScheduled($this->consumer->id);

        if ($consumerNegotiation->negotiation_type === NegotiationType::PIF) {
            $revenueShareFee = app(CompanyMembershipService::class)->fetchFee($this->consumer);

            ScheduleTransaction::query()
                ->create([
                    'consumer_id' => $this->consumer->id,
                    'company_id' => $this->consumer->company_id,
                    'subclient_id' => $this->consumer->subclient_id,
                    'schedule_date' => $consumerNegotiation->counter_first_pay_date,
                    'payment_profile_id' => $paymentProfile->id,
                    'status' => TransactionStatus::SCHEDULED,
                    'status_code' => '111',
                    'amount' => $consumerNegotiation->counter_one_time_amount,
                    'transaction_type' => NegotiationType::PIF,
                    'schedule_time' => now()->addMinutes(30)->toTimeString(),
                    'stripe_payment_detail_id' => $paymentProfile->stripePaymentDetail?->id,
                    'revenue_share_percentage' => $revenueShareFee,
                ]);
        }

        if ($consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT) {
            app(ScheduleTransactionService::class)->createInstallmentsIfNotCreated($this->consumer, $consumerNegotiation, $paymentProfile);
        }

        $this->success(__('Offer Accepted!'));

        $this->redirectRoute('consumer.schedule_plan', ['consumer' => $this->consumer->id], navigate: true);
    }

    public function submitCounterOffer(): void
    {
        $validatedData = $this->form->validate();

        $installments = null;
        $lastInstallmentAmount = null;

        $amount = (float) $validatedData['monthly_amount'];
        $consumerNegotiation = $this->consumer->consumerNegotiation;
        $negotiationTypeIsInstallment = $consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT;

        if ($negotiationTypeIsInstallment) {
            $installmentType = $consumerNegotiation->installment_type;

            [$installments, $lastInstallmentAmount] = $this->discountService->calculateInstallments(
                $this->minimumPpaDiscountedAmount,
                $amount
            );

            $validatedData['monthly_amount'] = $validatedData['monthly_amount'] * $installmentType->getAmountMultiplication();
        }

        if ($negotiationTypeIsInstallment && $amount > $this->minimumPpaDiscountedAmount) {
            $amount = $this->minimumPpaDiscountedAmount;
            $validatedData['monthly_amount'] = $this->minimumPpaDiscountedAmount;
            $lastInstallmentAmount = null;
            $installments = 1;
        }

        if (! $negotiationTypeIsInstallment && $amount > $this->minimumPifDiscountedAmount) {
            $amount = $this->minimumPifDiscountedAmount;
            $validatedData['monthly_amount'] = $this->minimumPifDiscountedAmount;
        }

        $consumerNegotiationData = [
            'first_pay_date' => $validatedData['counter_first_pay_date'],
            'note' => filled($validatedData['counter_note']) ? $validatedData['counter_note'] : null,
            'one_time_settlement' => $negotiationTypeIsInstallment ? null : number_format((float) $amount, 2, thousands_separator: ''),
            'no_of_installments' => $installments,
            'active_negotiation' => true,
            'monthly_amount' => $negotiationTypeIsInstallment ? number_format((float) $amount, 2, thousands_separator: '') : null,
            'last_month_amount' => $lastInstallmentAmount ? number_format((float) $lastInstallmentAmount, 2, thousands_separator: '') : null,
            'counter_one_time_amount' => null,
            'counter_negotiate_amount' => null,
            'counter_monthly_amount' => null,
            'counter_first_pay_date' => null,
            'counter_last_month_amount' => null,
            'counter_no_of_installments' => null,
            'counter_note' => null,
            'offer_accepted' => false,
        ];

        if ($this->isOfferAccepted($validatedData)) {
            $consumerNegotiationData['offer_accepted'] = true;
        }

        $consumerNegotiation->update($consumerNegotiationData);

        $consumerData = [
            'offer_accepted' => $consumerNegotiationData['offer_accepted'],
            'status' => $consumerNegotiationData['offer_accepted'] ? ConsumerStatus::PAYMENT_ACCEPTED : ConsumerStatus::PAYMENT_SETUP,
            'custom_offer' => ! $consumerNegotiationData['offer_accepted'],
            'counter_offer' => false,
        ];

        $this->consumer->update($consumerData);

        Cache::put(
            'new_offer_count_' . $this->consumer->company_id,
            $newOfferCount = $this->consumerService->getCountOfNewOffer($this->consumer->company_id),
            now()->addHour(),
        );

        $this->dispatch('new-offer-count-updated', $newOfferCount);

        app(ScheduleTransactionService::class)->deleteScheduled($this->consumer->id);

        $this->dispatch('close-dialog-of-counter-offer');

        $this->form->reset();
        $this->form->resetValidation();

        $this->success(__('Your counter offer was sent! Great job taking care of business!!'));
    }

    /**
     * @param array{
     *  negotiation_type: string,
     *  installment_type: ?string,
     *  amount: float|string,
     *  first_pay_date: string,
     * } $data
     */
    private function isOfferAccepted(array $data): bool
    {
        ['max_first_pay_date' => $maxFirstPaymentDate] = $this->discountService->fetchMaxDateForFirstPayment($this->consumer);
        $isWithinMaxFirstPaymentDate = $maxFirstPaymentDate->gt($data['counter_first_pay_date']);

        $negotiationTypeIsInstallment = $this->consumer->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT;

        if ($negotiationTypeIsInstallment) {
            $minimumMonthlyPayAmount = $this->discountService->fetchMonthlyAmount($this->consumer, $this->minimumPpaDiscountedAmount);
            $isMonthlyAmountSufficient = (float) $data['monthly_amount'] > $minimumMonthlyPayAmount && $negotiationTypeIsInstallment;

            return $isWithinMaxFirstPaymentDate && $isMonthlyAmountSufficient;
        }

        ['discount' => $minimumPifDiscountedAmount] = $this->discountService->fetchAmountToPayWhenPif($this->consumer);
        $isEnteredAmountSufficient = ((float) $data['monthly_amount']) >= round((float) $minimumPifDiscountedAmount);

        return $isWithinMaxFirstPaymentDate && $isEnteredAmountSufficient;
    }

    public function render(): View
    {
        $this->consumer->setAttribute('offerDetails', $this->offerDetails($this->consumer));

        return view('livewire.consumer.my-account.view-offer');
    }
}

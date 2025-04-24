<?php

declare(strict_types=1);

namespace App\Livewire\Consumer;

use App\Enums\ConsumerStatus;
use App\Enums\NegotiationType;
use App\Livewire\Consumer\Forms\CustomOfferForm;
use App\Livewire\Consumer\Traits\ValidateCounterOffer;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Services\CampaignTrackerService;
use App\Services\Consumer\DiscountService;
use App\Services\Consumer\ScheduleTransactionService;
use App\Services\ConsumerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.consumer.app-layout')]
class CustomOffer extends Component
{
    use ValidateCounterOffer;

    public Consumer $consumer;

    public ?string $type = null;

    public CustomOfferForm $form;

    public float $minimumPpaDiscountedAmount;

    public float $minimumPifDiscountedAmount;

    protected DiscountService $discountService;

    public function __construct()
    {
        $this->discountService = app(DiscountService::class);
    }

    public function boot(): void
    {
        if ($this->consumer->status === ConsumerStatus::SETTLED) {
            $this->redirectRoute('consumer.complete_payment', ['consumer' => $this->consumer], navigate: true);
        }
    }

    public function mount(): void
    {
        $this->form->negotiation_type = NegotiationType::PIF->value;

        $this->consumer->loadMissing(['company', 'subclient', 'consumerNegotiation']);
        $this->minimumPpaDiscountedAmount = $this->discountService->fetchAmountToPayWhenPpa($this->consumer);
        $this->minimumPifDiscountedAmount = $this->discountService->fetchAmountToPayWhenPif($this->consumer)['discount'];

        $this->form->init($this->type, $this->consumer);
    }

    public function createCustomOffer(): void
    {
        $validatedData = $this->form->validate();

        $amount = (float) $validatedData['amount'];

        if ($amount > $this->minimumPifDiscountedAmount && $validatedData['negotiation_type'] === NegotiationType::PIF->value) {
            $amount = $this->minimumPifDiscountedAmount;
        }

        if ($amount > $this->minimumPpaDiscountedAmount && $validatedData['negotiation_type'] === NegotiationType::INSTALLMENT->value) {
            $amount = $this->minimumPpaDiscountedAmount;
        }

        $installments = null;
        $lastInstallmentAmount = null;
        $negotiationTypeIsInstallment = $validatedData['negotiation_type'] === NegotiationType::INSTALLMENT->value;

        if ($negotiationTypeIsInstallment) {
            [$installments, $lastInstallmentAmount] = $this->discountService->calculateInstallments($this->minimumPpaDiscountedAmount, $amount);
        }

        ConsumerNegotiation::query()->updateOrCreate(
            [
                'company_id' => $this->consumer->company_id,
                'consumer_id' => $this->consumer->id,
            ],
            [
                'first_pay_date' => $validatedData['first_pay_date'],
                'reason' => filled($validatedData['reason']) ? $validatedData['reason'] : null,
                'note' => filled($validatedData['note']) ? $validatedData['note'] : null,
                'negotiation_type' => $validatedData['negotiation_type'],
                'installment_type' => $negotiationTypeIsInstallment ? $validatedData['installment_type'] : null,
                'one_time_settlement' => $negotiationTypeIsInstallment ? null : number_format($amount, 2, thousands_separator: ''),
                'no_of_installments' => $installments,
                'active_negotiation' => true,
                'monthly_amount' => number_format($validatedData['amount'], 2, thousands_separator: ''),
                'negotiate_amount' => $negotiationTypeIsInstallment ? number_format($this->minimumPpaDiscountedAmount, 2, thousands_separator: '') : null,
                'last_month_amount' => $lastInstallmentAmount ? number_format((float) $lastInstallmentAmount, 2, thousands_separator: '') : null,
            ]
        );

        $this->consumer->refresh()->consumerNegotiation->fill([
            'offer_accepted' => false,
        ]);

        $this->consumer->fill([
            'offer_accepted' => false,
            'status' => ConsumerStatus::PAYMENT_SETUP,
            'custom_offer' => true,
            'counter_offer' => false,
        ]);

        $this->form->isOfferAccepted = $this->isOfferAccepted($validatedData);

        $campaignTrackerUpdateFieldName = 'custom_offer_count';

        if ($this->form->isOfferAccepted) {
            $this->consumer->consumerNegotiation->fill([
                'offer_accepted' => true,
            ]);
            $this->consumer->fill([
                'status' => ConsumerStatus::PAYMENT_ACCEPTED,
                'offer_accepted' => true,
                'custom_offer' => false,
            ]);

            $campaignTrackerUpdateFieldName = $validatedData['negotiation_type'] === NegotiationType::PIF->value
                ? 'pif_completed_count'
                : 'ppl_completed_count';
        }

        $this->consumer->consumerNegotiation->save();
        $this->consumer->save();

        if (! $this->form->isOfferAccepted) {
            Cache::put(
                'new_offer_count_' . $this->consumer->company_id,
                $newOfferCount = app(ConsumerService::class)->getCountOfNewOffer($this->consumer->company_id),
                now()->addHour(),
            );

            $this->dispatch('new-offer-count-updated', $newOfferCount);
        }

        app(CampaignTrackerService::class)->updateTrackerCount($this->consumer, $campaignTrackerUpdateFieldName);

        app(ScheduleTransactionService::class)->deleteScheduled($this->consumer->id);

        $this->form->offerSent = $this->form->isOfferAccepted === false;
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
        $isWithinMaxFirstPaymentDate = $maxFirstPaymentDate->gte($data['first_pay_date']);

        $negotiationTypeIsInstallment = $data['negotiation_type'] === NegotiationType::INSTALLMENT->value;

        $minimumMonthlyPayAmount = number_format($this->discountService->fetchMonthlyAmount($this->consumer, $this->minimumPpaDiscountedAmount), 2, thousands_separator: '');
        $monthlyAmount = $this->calculateMonthlyAmount((float) $data['amount'], $data['installment_type']);
        $isMonthlyAmountSufficient = $negotiationTypeIsInstallment && ($monthlyAmount >= (float) $minimumMonthlyPayAmount);

        $negotiationTypeIsPIF = $data['negotiation_type'] === NegotiationType::PIF->value;
        ['discount' => $minimumPifDiscountedAmount] = $this->discountService->fetchAmountToPayWhenPif($this->consumer);
        $isEnteredAmountSufficient = $negotiationTypeIsPIF && (((float) $data['amount']) >= (float) $minimumPifDiscountedAmount);

        return $isWithinMaxFirstPaymentDate && ($isMonthlyAmountSufficient || $isEnteredAmountSufficient);
    }

    public function render(): View
    {
        return view('livewire.consumer.custom-offer')->title(__('Custom Offer'));
    }
}

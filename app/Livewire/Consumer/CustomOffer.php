<?php

declare(strict_types=1);

namespace App\Livewire\Consumer;

use App\Enums\ConsumerStatus;
use App\Enums\NegotiationType;
use App\Livewire\Consumer\Forms\CustomOfferForm;
use App\Livewire\Consumer\Traits\ValidateCounterOffer;
use App\Services\Consumer\DiscountService;
use App\Services\Consumer\ConsumerNegotiationService;
use App\Models\Consumer;
use Illuminate\Contracts\View\View;
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
    protected ConsumerNegotiationService $consumerNegotiationService;

    public function __construct()
    {
        $this->discountService = app(DiscountService::class);
        $this->consumerNegotiationService = app(ConsumerNegotiationService::class);
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
        $newOfferCount = $this->consumerNegotiationService->updateConsumerNegotiation(
            $this->consumer,
            $validatedData,
            $this->form->isOfferAccepted,
            $this->minimumPifDiscountedAmount,
            $this->minimumPpaDiscountedAmount
        );

        if ($newOfferCount !== null) {
            $this->dispatch('new-offer-count-updated', $newOfferCount);
        }


        $this->form->isOfferAccepted = $this->consumerNegotiationService->isOfferAccepted($this->consumer, $validatedData,  $this->minimumPpaDiscountedAmount);

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

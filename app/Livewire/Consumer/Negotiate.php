<?php

declare(strict_types=1);

namespace App\Livewire\Consumer;

use App\Enums\ConsumerStatus;
use App\Enums\InstallmentType;
use App\Enums\NegotiationType;
use App\Livewire\Consumer\Traits\Agreement;
use App\Livewire\Consumer\Traits\CreditorDetails;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Services\CampaignTrackerService;
use App\Services\Consumer\DiscountService;
use App\Services\Consumer\ReasonService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.consumer.app-layout')]
class Negotiate extends Component
{
    // This is not used for download Agreement but,
    // It is used only for the `negotiateAmount` functions.
    use Agreement;
    use CreditorDetails;

    public Consumer $consumer;

    public string $first_pay_date = '';

    protected DiscountService $discountService;

    protected CampaignTrackerService $campaignTrackerService;

    public function __construct()
    {
        $this->discountService = app(DiscountService::class);

        $this->campaignTrackerService = app(CampaignTrackerService::class);
    }

    public function boot(): void
    {
        if ($this->consumer->status === ConsumerStatus::SETTLED) {
            $this->redirectRoute('consumer.complete_payment', ['consumer' => $this->consumer], navigate: true);
        }

        $this->withValidator(function (Validator $validator): void {
            $validator->after(function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    $this->reset('first_pay_date');
                }
            });
        });
    }

    public function mount(): void
    {
        if (! in_array($this->consumer->status, [ConsumerStatus::JOINED, ConsumerStatus::NOT_PAYING])) {
            $this->redirectRoute('consumer.account', navigate: true);
        }
    }

    public function createSettlementOffer(): void
    {
        $maxFirstPayDate = $this->discountService->fetchMaxDateForFirstPayment($this->consumer)['max_first_pay_date']->toDateString();
        $accountBalance = $this->discountService->fetchAmountToPayWhenPif($this->consumer);

        $validatedData = $this->validate(['first_pay_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:' . $maxFirstPayDate]]);

        ConsumerNegotiation::query()->updateOrCreate(
            [
                'consumer_id' => $this->consumer->id,
                'company_id' => $this->consumer->company_id,
            ],
            [
                'first_pay_date' => $validatedData['first_pay_date'],
                'negotiation_type' => NegotiationType::PIF,
                'installment_type' => null,
                'no_of_installments' => null,
                'account_number' => $this->consumer->account_number,
                'one_time_settlement' => number_format((float) $accountBalance['discount'], 2, thousands_separator: ''),
                'offer_accepted' => false,
            ]
        );

        $this->consumer->update([
            'offer_accepted' => false,
            'custom_offer' => false,
            'counter_offer' => false,
        ]);

        $this->redirectRoute('consumer.payment', $this->consumer->id, navigate: true);
    }

    public function createSettlementOfferWithToday(): void
    {
        $this->first_pay_date = now()->toDateString();
        $this->createSettlementOffer();
    }


    public function createInstallmentOffer(): void
    {
        $maxFirstPayDate = $this->discountService->fetchMaxDateForFirstPayment($this->consumer)['max_first_pay_date']->toDateString();

        $validatedData = $this->validate(['first_pay_date' => ['required', 'date', 'date_format:Y-m-d', 'after:today', 'before_or_equal:' . $maxFirstPayDate]]);

        $minimumPpaDiscountedAmount = (float) ($this->discountService->fetchAmountToPayWhenPpa($this->consumer));
        $installmentDetails = $this->discountService->fetchInstallmentDetails($this->consumer);

        ConsumerNegotiation::query()
            ->updateOrCreate(
                [
                    'consumer_id' => $this->consumer->id,
                    'company_id' => $this->consumer->company_id,
                ],
                [
                    'first_pay_date' => $validatedData['first_pay_date'],
                    'negotiation_type' => NegotiationType::INSTALLMENT,
                    'installment_type' => InstallmentType::MONTHLY,
                    'offer_accepted' => false,
                    'negotiate_amount' => number_format($minimumPpaDiscountedAmount, 2, thousands_separator: ''),
                    'no_of_installments' => $installmentDetails['installments'],
                    'monthly_amount' => number_format($installmentDetails['monthly_amount'], 2, thousands_separator: ''),
                    'last_month_amount' => $installmentDetails['last_month_amount'] > 0 ? number_format((float) $installmentDetails['last_month_amount'], 2, thousands_separator: '') : null,
                ]
            );

        $this->consumer->update([
            'offer_accepted' => false,
            'custom_offer' => false,
            'counter_offer' => false,
        ]);

        $this->redirectRoute('consumer.payment', ['consumer' => $this->consumer->id], navigate: true);
    }

    public function render(): View
    {
        $accountBalance = $this->discountService->fetchAmountToPayWhenPif($this->consumer);
        $maxFirstPayDate = $this->discountService->fetchMaxDateForFirstPayment($this->consumer)['max_first_pay_date']->toDateString();

        $installmentDetails = $this->discountService->fetchInstallmentDetails($this->consumer);

        return view('livewire.consumer.negotiate')
            ->with([
                'creditorDetails' => $this->setCreditorDetails($this->consumer),
                'maxFirstPayDate' => $maxFirstPayDate,
                'payOffDiscount' => $accountBalance['discount'],
                'payOffDiscountPercentage' => $accountBalance['percentage'],
                'payOffDiscountedAmount' => (float) $accountBalance['discountedAmount'],
                'installmentDetails' => $installmentDetails,
                'reasons' => app(ReasonService::class)->fetch(),
            ])
            ->title(__('Let\'s Negotiate'));
    }
}

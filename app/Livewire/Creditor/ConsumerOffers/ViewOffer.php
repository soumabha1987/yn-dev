<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ConsumerOffers;

use App\Enums\CommunicationCode;
use App\Enums\ConsumerStatus;
use App\Enums\InstallmentType;
use App\Enums\NegotiationType;
use App\Enums\TransactionStatus;
use App\Jobs\TriggerEmailAndSmsServiceJob;
use App\Livewire\Creditor\Forms\ConsumerOffers\ViewOffer\CreditorOfferForm;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\ScheduleTransaction;
use App\Services\CompanyMembershipService;
use App\Services\ConsumerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Validator;
use Livewire\Component;

class ViewOffer extends Component
{
    public Consumer $consumer;

    public bool $isNotEditable = false;

    public ?ConsumerNegotiation $consumerNegotiation;

    public bool $isMenuItem = false;

    public array $calculatedData = [];

    public bool $sendCounterOffer = false;

    public CreditorOfferForm $form;

    protected ConsumerService $consumerService;

    public string $payTermSource = '';

    protected int $companyId;

    public function boot(): void
    {
        $this->form->withValidator(function (Validator $validator): void {
            $validator->after(function (Validator $validator): void {
                if ($validator->errors()->has('counter_note')) {
                    $this->dispatch('scroll-into-counter-note');
                }
            });
        });
    }

    public function mount(): void
    {
        $this->consumer->loadMissing(['consumerNegotiation', 'subclient', 'company', 'unsubscribe', 'paymentProfile:id']);

        $this->consumerNegotiation = $this->consumer->consumerNegotiation;

        if (! $this->consumerNegotiation) {
            // TODO: How we can resolve it for phpstan!!
            return;
        }

        $this->actualOffer();
        $this->consumerOffer();
        $this->creditorOffer();
        $this->payTermSource = $this->checkSource();

        $this->calculatedData['creditor_offer']['counter_note'] = $this->consumerNegotiation->counter_note;

        $this->form->init($this->calculatedData['creditor_offer']);
    }

    public function __construct()
    {
        $this->consumerService = app(ConsumerService::class);

        $this->companyId = Auth::user()->company_id;
    }

    public function acceptOffer(): void
    {
        if ($this->consumer->scheduledTransactions()->doesntExist()) {
            $this->createScheduleTransaction();
        }

        $user = Auth::user();

        $this->consumerNegotiation->update([
            'offer_accepted' => true,
            'offer_accepted_at' => now(),
            'approved_by' => $user->id,
        ]);

        $this->consumer->update([
            'offer_accepted' => true,
            'status' => ConsumerStatus::PAYMENT_ACCEPTED,
        ]);

        $this->success(__('Offer accepted.'));

        $communicationCode = match (true) {
            ! $this->consumer->payment_setup => CommunicationCode::OFFER_APPROVED_BUT_NO_PAYMENT_SETUP,
            $this->consumerNegotiation->negotiation_type === NegotiationType::PIF => CommunicationCode::PAY_IN_PIF_AND_PAYMENT_SETUP_DONE,
            $this->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT => CommunicationCode::PAY_IN_INSTALLMENT_AND_PAYMENT_SETUP_DONE,
            default => null,
        };

        TriggerEmailAndSmsServiceJob::dispatchIf($communicationCode !== null, $this->consumer, $communicationCode);

        Cache::put(
            'new_offer_count_' . $this->companyId,
            $newOfferCount = $this->consumerService->getCountOfNewOffer($this->companyId),
            now()->addHour(),
        );

        $this->dispatch('new-offer-count-updated', $newOfferCount);

        $this->dispatch('close-dialog');
    }

    public function submitCounterOffer(): void
    {
        $validatedData = $this->form->validate();
        $lastInstallmentAmount = null;
        $counterMonthlyAmount = null;
        $noOfInstallments = null;

        if ($this->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT) {
            $counterMonthlyAmount = ((float) $validatedData['monthly_amount']);

            if ($counterMonthlyAmount > 0) {
                $noOfInstallments = intval($validatedData['payment_plan_discount_amount'] / $counterMonthlyAmount);

                if (($noOfInstallments * $counterMonthlyAmount) > $validatedData['payment_plan_discount_amount']) {
                    $noOfInstallments = $noOfInstallments - 1;
                }

                $lastInstallmentAmount = number_format($validatedData['payment_plan_discount_amount'] - ($counterMonthlyAmount * $noOfInstallments), 2, thousands_separator: '');

                if ($lastInstallmentAmount < 10 && $lastInstallmentAmount > 0) {
                    $noOfInstallments--;
                    $lastInstallmentAmount = number_format($counterMonthlyAmount + (float) $lastInstallmentAmount, 2, thousands_separator: '');
                }
            }
        }

        $this->consumerNegotiation->update([
            'counter_one_time_amount' => $this->consumerNegotiation->negotiation_type === NegotiationType::PIF ? $validatedData['settlement_discount_amount'] : null,
            'counter_negotiate_amount' => $this->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT ? $validatedData['payment_plan_discount_amount'] : null,
            'counter_monthly_amount' => $counterMonthlyAmount,
            'counter_first_pay_date' => $validatedData['counter_first_pay_date'],
            'counter_last_month_amount' => $lastInstallmentAmount,
            'counter_no_of_installments' => $noOfInstallments,
            'counter_note' => $validatedData['counter_note'],
        ]);

        $this->consumer->update(['counter_offer' => true]);

        Cache::put(
            'new_offer_count_' . $this->companyId,
            $newOfferCount = $this->consumerService->getCountOfNewOffer($this->companyId),
            now()->addHour(),
        );

        $this->dispatch('new-offer-count-updated', $newOfferCount);

        TriggerEmailAndSmsServiceJob::dispatch($this->consumer, CommunicationCode::COUNTER_OFFER_BUT_NO_RESPONSE);

        $this->success(__('Your counter offer has been delivered to :consumerFirstName.', ['consumerFirstName' => $this->consumer->first_name]));

        $this->creditorOffer();

        $this->reset('sendCounterOffer');

        $this->dispatch('close-dialog-of-counter-offer');
    }

    public function declineOffer(Consumer $consumer): void
    {
        $consumer->consumerNegotiation()->update(['offer_accepted' => false]);

        $consumer->update(['status' => ConsumerStatus::PAYMENT_DECLINED->value]);

        Cache::put(
            'new_offer_count_' . $this->companyId,
            $newOfferCount = $this->consumerService->getCountOfNewOffer($this->companyId),
            now()->addHour(),
        );

        $this->dispatch('new-offer-count-updated', $newOfferCount);

        $consumer->scheduledTransactions()->delete();
        $consumer->paymentProfiles()->delete();

        TriggerEmailAndSmsServiceJob::dispatch($consumer, CommunicationCode::OFFER_DECLINED);

        $this->success(__('Consumer offer declined successfully.'));

        $this->dispatch('close-dialog');
    }

    private function actualOffer(): void
    {
        $settlementDiscountOffer = $this->settlementDiscountOffer();
        $this->calculatedData['offer']['settlement_discount_offer_amount'] = $settlementDiscountOffer[0];
        $this->calculatedData['offer']['settlement_discount_offer_percentage'] = $settlementDiscountOffer[1];

        $paymentPlanDiscountOffer = $this->paymentPlanDiscountAmount();
        $this->calculatedData['offer']['payment_plan_offer_amount'] = $paymentPlanDiscountOffer[0];
        $this->calculatedData['offer']['payment_plan_offer_percentage'] = $paymentPlanDiscountOffer[1];

        $minMonthlyPayment = $this->minimumMonthlyPayment();
        $this->calculatedData['offer']['minimum_monthly_payment'] = $minMonthlyPayment[0];
        $this->calculatedData['offer']['minimum_monthly_payment_percentage'] = $minMonthlyPayment[1];

        $firstPaymentDays = $this->firstPaymentDate();
        $this->calculatedData['offer']['first_payment_date'] = $firstPaymentDays[0];
        $this->calculatedData['offer']['first_payment_day'] = $firstPaymentDays[1];
    }

    private function consumerOffer(): void
    {
        $this->calculatedData['consumer_offer']['settlement_discount_offer_amount'] = $this->consumerNegotiation->one_time_settlement ?? null;

        $this->calculatedData['consumer_offer']['payment_plan_offer_amount'] = $this->consumerNegotiation->negotiate_amount ?? null;

        $this->calculatedData['consumer_offer']['minimum_monthly_payment'] = $this->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT
            ? ((float) $this->consumerNegotiation->monthly_amount)
            : null;

        $this->calculatedData['consumer_offer']['first_payment_date'] = $this->consumerNegotiation->first_pay_date;
    }

    private function creditorOffer(): void
    {
        $this->calculatedData['creditor_offer']['settlement_discount_offer_amount'] = $this->consumerNegotiation->counter_one_time_amount;

        $this->calculatedData['creditor_offer']['payment_plan_offer_amount'] = $this->consumerNegotiation->counter_negotiate_amount;

        if ($this->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT) {
            $this->calculatedData['creditor_offer']['minimum_monthly_payment'] = ((float) $this->consumerNegotiation->counter_monthly_amount);
        }

        $this->calculatedData['creditor_offer']['first_payment_date'] = $this->consumerNegotiation->counter_first_pay_date;
    }

    private function createScheduleTransaction(): void
    {
        $firstPaymentDate = $this->consumerNegotiation->first_pay_date;

        $revenueShareFee = app(CompanyMembershipService::class)->fetchFee($this->consumer);

        if ($this->consumerNegotiation->negotiation_type === NegotiationType::PIF) {
            $amount = (float) $this->consumerNegotiation->one_time_settlement;

            ScheduleTransaction::query()
                ->create([
                    'consumer_id' => $this->consumer->id,
                    'company_id' => $this->consumer->company_id,
                    'schedule_date' => $firstPaymentDate->toDateString(),
                    'payment_profile_id' => $this->consumer->paymentProfile ? $this->consumer->paymentProfile->id : null,
                    'subclient_id' => $this->consumer->subclient_id,
                    'status' => TransactionStatus::SCHEDULED,
                    'amount' => number_format($amount, 2, thousands_separator: ''),
                    'transaction_type' => NegotiationType::PIF,
                    'revenue_share_percentage' => $revenueShareFee,
                ]);
        }

        if ($this->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT) {
            $installmentAmount = (float) $this->consumerNegotiation->monthly_amount;
            $noOfInstallments = (int) $this->consumerNegotiation->no_of_installments;

            /** @var InstallmentType $installmentType */
            $installmentType = $this->consumerNegotiation->installment_type;
            $lastInstallmentAmount = (float) $this->consumerNegotiation->last_month_amount;

            if ($firstPaymentDate->isPast() && ! $this->consumer->payment_setup) {
                $firstPaymentDate = now();
            }

            $paymentDate = $firstPaymentDate->clone();

            $installmentDetails = collect(range(1, $noOfInstallments))->map(fn ($number): array => [
                'amount' => number_format($installmentAmount, 2, thousands_separator: ''),
                'schedule_date' => $paymentDate->clone()->{$installmentType->getCarbonMethod()}($number - 1),
            ]);

            if ($lastInstallmentAmount) {
                $installmentDetails->push([
                    'amount' => number_format($lastInstallmentAmount, 2, thousands_separator: ''),
                    'schedule_date' => $paymentDate->{$installmentType->getCarbonMethod()}($noOfInstallments),
                ]);
            }

            $scheduleTransactions = $installmentDetails->map(fn ($installment) => [
                'consumer_id' => $this->consumer->id,
                'company_id' => $this->consumer->company_id,
                'subclient_id' => $this->consumer->subclient_id,
                'schedule_date' => $installment['schedule_date'],
                'payment_profile_id' => $this->consumer->paymentProfile ? $this->consumer->paymentProfile->id : null,
                'status' => TransactionStatus::SCHEDULED,
                'amount' => $installment['amount'],
                'transaction_type' => NegotiationType::INSTALLMENT,
                'revenue_share_percentage' => $revenueShareFee,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            ScheduleTransaction::query()->insert($scheduleTransactions->all());
        }
    }

    private function firstPaymentDate(): array
    {
        $days = (int) ($this->consumer->max_days_first_pay
            ?? $this->consumer->subclient->max_days_first_pay
            ?? $this->consumer->company->max_days_first_pay);

        return [now()->addDays($days), $days];
    }

    private function minimumMonthlyPayment(): array
    {
        $percentage = (float) ($this->consumer->min_monthly_pay_percent
            ?? $this->consumer->subclient->min_monthly_pay_percent
            ?? $this->consumer->company->min_monthly_pay_percent);

        return [(float) ($this->paymentPlanDiscountAmount()[0] * $percentage / 100), $percentage];
    }

    private function settlementDiscountOffer(): array
    {
        $percentage = $this->consumer->pif_discount_percent
            ?? $this->consumer->subclient->pif_balance_discount_percent
            ?? $this->consumer->company->pif_balance_discount_percent;

        return [(float) ($this->consumer->current_balance - ($this->consumer->current_balance * $percentage / 100)), $percentage];
    }

    private function paymentPlanDiscountAmount(): array
    {
        $percentage = (float) ($this->consumer->pay_setup_discount_percent
            ?? $this->consumer->subclient->ppa_balance_discount_percent
            ?? $this->consumer->company->ppa_balance_discount_percent);

        return [(float) ($this->consumer->current_balance - ($this->consumer->current_balance * $percentage / 100)), $percentage];
    }

    private function checkSource(): string
    {
        $forPifSources = [
            'Individual Custom Term' => $this->consumer->pif_discount_percent,
            $this->consumer->subclient->subclient_name ?? 'Subclient Term' => $this->consumer->subclient->pif_balance_discount_percent ?? null,
            'Master Term' => $this->consumer->company->pif_balance_discount_percent,
        ];

        $forInstallmentSources = [
            'Individual Custom Term' => $this->consumer->pay_setup_discount_percent,
            $this->consumer->subclient->subclient_name ?? 'Subclient Term' => $this->consumer->subclient->ppa_balance_discount_percent ?? null,
            'Master Term' => $this->consumer->company->ppa_balance_discount_percent,
        ];

        if ($this->consumerNegotiation->negotiation_type === NegotiationType::PIF) {
            $filtered = array_filter($forPifSources, fn ($value) => $value !== null);

            return array_key_first($filtered);
        }

        $filtered = array_filter($forInstallmentSources, fn ($value) => $value !== null);

        return array_key_first($filtered);
    }

    public function render(): View
    {
        return view('livewire.creditor.consumer-offers.view-offer');
    }
}

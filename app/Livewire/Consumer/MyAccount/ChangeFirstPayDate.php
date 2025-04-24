<?php

declare(strict_types=1);

namespace App\Livewire\Consumer\MyAccount;

use App\Enums\ConsumerStatus;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Services\Consumer\DiscountService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ChangeFirstPayDate extends Component
{
    public Consumer $consumer;

    public string $first_pay_date = '';

    public function mount(): void
    {
        $this->consumer->loadMissing('consumerNegotiation');

        if ($this->consumer->consumerNegotiation->offer_accepted) {
            $this->first_pay_date = $this->consumer->consumerNegotiation->first_pay_date->toDateString();
        }

        if ($this->consumer->consumerNegotiation->counter_offer_accepted) {
            $this->first_pay_date = $this->consumer->consumerNegotiation->counter_first_pay_date?->toDateString();
        }
    }

    public function changeFirstPayDate(): void
    {
        $validatedData = $this->validate(['first_pay_date' => ['required', 'date', 'date_format:Y-m-d', 'after:today']]);

        /** @var ConsumerNegotiation $consumerNegotiation */
        $consumerNegotiation = $this->consumer->consumerNegotiation;

        if (app(DiscountService::class)->fetchMaxDateForFirstPayment($this->consumer)['max_first_pay_date']->gt($validatedData['first_pay_date'])) {
            $consumerNegotiation->update([
                'first_pay_date' => $consumerNegotiation->offer_accepted ? $validatedData['first_pay_date'] : $consumerNegotiation->first_pay_date,
                'counter_first_pay_date' => $consumerNegotiation->offer_accepted ? $consumerNegotiation->counter_first_pay_date : $validatedData['first_pay_date'],
            ]);

            $this->success(__('Your first pay date update successfully.'));

            $this->dispatch('close-dialog');

            return;
        }

        $consumerNegotiation->update([
            'first_pay_date' => $validatedData['first_pay_date'],
            'offer_accepted' => false,
            'counter_first_pay_date' => null,
            'counter_offer_accepted' => false,
            'counter_one_time_amount' => null,
            'counter_monthly_amount' => null,
            'counter_last_month_amount' => null,
            'counter_no_of_installments' => null,
            'counter_note' => null,
            'counter_negotiate_amount' => null,
        ]);

        $this->consumer->update([
            'offer_accepted' => false,
            'status' => ConsumerStatus::PAYMENT_SETUP,
            'custom_offer' => true,
            'counter_offer' => false,
        ]);

        $this->success(__('Awesome! Your offer was sent to your creditor!'));

        $this->dispatch('close-dialog');
    }

    public function render(): View
    {
        return view('livewire.consumer.my-account.change-first-pay-date');
    }
}

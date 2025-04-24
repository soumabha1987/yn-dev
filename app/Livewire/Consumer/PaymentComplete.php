<?php

declare(strict_types=1);

namespace App\Livewire\Consumer;

use App\Enums\ConsumerStatus;
use App\Livewire\Consumer\Traits\Agreement;
use App\Livewire\Consumer\Traits\CreditorDetails;
use App\Models\Consumer;
use App\Services\Consumer\ConsumerService;
use App\Services\Consumer\TransactionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.consumer.app-layout')]
class PaymentComplete extends Component
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

    public function mount(): void
    {
        if ($this->consumer->status !== ConsumerStatus::SETTLED) {
            $this->redirectRoute('consumer.account', navigate: true);

            return;
        }

        if (Session::has('complete-payment')) {
            $this->js('$confetti()');
        }

        $this->creditorDetails = $this->setCreditorDetails($this->consumer);

        $this->consumer->loadMissing(['consumerNegotiation', 'paymentProfile', 'externalPaymentProfile']);
    }

    public function render(): View
    {
        return view('livewire.consumer.payment-complete')
            ->with('transactions', app(TransactionService::class)->fetchSuccessTransactions($this->consumer->id))
            ->with('address', app(ConsumerService::class)->generateAddress($this->consumer))
            ->title(__('Successfully Paid'));
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Consumer;

use App\Enums\ConsumerStatus;
use App\Livewire\Consumer\Traits\Agreement;
use App\Livewire\Consumer\Traits\CreditorDetails;
use App\Models\Consumer;
use App\Services\Consumer\TransactionService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.consumer.app-layout')]
class PaymentHistory extends Component
{
    use Agreement;
    use CreditorDetails;

    public Consumer $consumer;

    /**
     * @var array<string, mixed>
     */
    public array $creditorDetails = [];

    public function mount(): void
    {
        $this->consumer->loadMissing('paymentProfile');

        if ($this->consumer->status !== ConsumerStatus::DEACTIVATED || $this->consumer->paymentProfile === null) {
            $this->redirectRoute('consumer.account', navigate: true);

            return;
        }

        $this->creditorDetails = $this->setCreditorDetails($this->consumer);
    }

    public function render(): View
    {
        return view('livewire.consumer.payment-history')
            ->with('transactions', app(TransactionService::class)->fetch($this->consumer))
            ->title(__('Payment History'));
    }
}

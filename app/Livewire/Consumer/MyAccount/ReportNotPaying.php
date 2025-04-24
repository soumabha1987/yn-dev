<?php

declare(strict_types=1);

namespace App\Livewire\Consumer\MyAccount;

use App\Enums\ConsumerStatus;
use App\Models\Consumer;
use App\Models\Reason;
use App\Services\CampaignTrackerService;
use App\Services\Consumer\ReasonService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ReportNotPaying extends Component
{
    public Consumer $consumer;

    public string $reason = '';

    public string $other = '';

    public string $view = '';

    public bool $dialogOpenReport = false;

    public function reportNotPaying(): void
    {
        $validatedData = $this->validate([
            'reason' => ['required', 'integer', Rule::exists(Reason::class, 'id')],
            'other' => ['nullable', 'string', 'max:255'],
        ]);

        if (filled($validatedData['other'])) {
            $reason = Reason::query()
                ->create(['label' => $validatedData['other']]);

            $validatedData['reason'] = $reason->id;
        }

        $this->consumer->update([
            'reason_id' => $validatedData['reason'],
            'status' => ConsumerStatus::NOT_PAYING,
            'disputed_at' => now(),
            'custom_offer' => false,
            'counter_offer' => false,
            'payment_setup' => false,
        ]);

        $this->consumer->consumerNegotiation()->delete();

        $this->success(__('We have updated your account status and sent your response to the hosting YN member.'));

        app(CampaignTrackerService::class)->updateTrackerCount($this->consumer, 'no_pay_count');

        $this->redirectRoute('consumer.account', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.consumer.my-account.report-not-paying')
            ->with('reasons', app(ReasonService::class)->fetch());
    }
}

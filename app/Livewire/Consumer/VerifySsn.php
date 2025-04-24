<?php

declare(strict_types=1);

namespace App\Livewire\Consumer;

use App\Livewire\Consumer\Forms\VerifySsnForm;
use App\Models\CampaignTracker;
use App\Models\Consumer;
use App\Services\Consumer\ConsumerService;
use App\Services\GroupService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.consumer.app-layout')]
class VerifySsn extends Component
{
    public Consumer $consumer;

    public VerifySsnForm $form;

    public bool $resetCaptcha = false;

    protected ConsumerService $consumerService;

    public function __construct()
    {
        $this->consumerService = app(ConsumerService::class);

        $this->consumer = Auth::guard('consumer')->user();

        $this->consumer->loadMissing(['company', 'subclient']);
    }

    public function mount(): void
    {
        if (! session('required_ssn_verification', false)) {
            $this->redirectRoute('consumer.account', navigate: true);
        }
    }

    public function checkSsn(): void
    {
        $this->resetCaptcha = true;

        $this->form->validate();

        $notRequiredToVerifySsn = $this->form->authenticate($this->consumer);

        if ($notRequiredToVerifySsn) {
            $this->success(__('Logged in.'));
            $this->redirectIntended(navigate: true);
        }
    }

    public function render(): View
    {
        return view('livewire.consumer.verify-ssn');
    }

    public function rendered(): void
    {
        app(GroupService::class)
            ->fetchByConsumer($this->consumer)
            ->each(function (CampaignTracker $campaignTracker): void {
                $firstClickExists = $campaignTracker->campaignTrackerConsumers()
                    ->where('consumer_id', $this->consumer->id)
                    ->where('click', 0)
                    ->exists();

                if ($firstClickExists) {
                    $campaignTracker->campaignTrackerConsumers()
                        ->where('consumer_id', $this->consumer->id)
                        ->where('click', 0)
                        ->update(['click' => 1]);

                    $campaignTracker->increment('clicks_count');
                }
            });
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\AutomatedCommunication\AutomationCampaign;

use App\Livewire\Creditor\Forms\AutomatedCommunication\AutomationCampaignForm;
use App\Services\AutomationCampaignService;
use App\Services\CommunicationStatusService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Livewire\Component;

class CreatePage extends Component
{
    public AutomationCampaignForm $form;

    public Collection $communicationStatusCodes;

    public function mount(): void
    {
        $this->communicationStatusCodes = app(CommunicationStatusService::class)->getCommunicationCode();

        if ($this->communicationStatusCodes->isEmpty()) {
            $this->error(__('Campaign with statuses already exists.'));

            $this->redirectRoute('super-admin.automation-campaigns', navigate: true);
        }
    }

    public function create(): void
    {
        $validatedData = $this->form->validate();

        $validatedData['start_at'] = now()->startOfDay()->addHours($validatedData['hours'])->toDateTimeString();

        Arr::forget($validatedData, ['hours']);

        app(AutomationCampaignService::class)->create($validatedData);

        $this->success(__('Schedule is set up!'));

        $this->redirectRoute('super-admin.automation-campaigns', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.creditor.automated-communication.automation-campaign.create-page')
            ->title(__('Create Automation Campaign'));
    }
}

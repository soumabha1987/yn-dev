<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\AutomatedCommunication\AutomationCampaign;

use App\Livewire\Creditor\Forms\AutomatedCommunication\AutomationCampaignForm;
use App\Models\AutomationCampaign;
use App\Services\CommunicationStatusService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Livewire\Component;

class EditPage extends Component
{
    public AutomationCampaignForm $form;

    public AutomationCampaign $automationCampaign;

    public Collection $communicationStatusCodes;

    public function mount(): void
    {
        $this->communicationStatusCodes = app(CommunicationStatusService::class)
            ->getCommunicationCode($this->automationCampaign);

        $this->form->setData($this->automationCampaign);
    }

    public function update(): void
    {
        $validatedData = $this->form->validate();

        $validatedData['start_at'] = now()->startOfDay()->addHours($validatedData['hours'])->toDateTimeString();

        if ($this->automationCampaign->frequency->value !== $validatedData['frequency']) {
            $validatedData['last_sent_at'] = null;
            // TODO: We also set null other fields which related to frequency.
        }

        Arr::forget($validatedData, ['hours']);

        $this->automationCampaign->update($validatedData);

        $this->success(__('Schedule alerts updated.'));

        $this->redirectRoute('super-admin.automation-campaigns', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.creditor.automated-communication.automation-campaign.edit-page')
            ->title(__('Edit Automation Campaign'));
    }
}

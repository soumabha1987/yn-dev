<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\AutomatedCommunication\CommunicationStatus;

use App\Enums\AutomatedTemplateType;
use App\Models\AutomatedTemplate;
use App\Models\CommunicationStatus;
use App\Services\CommunicationStatusService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Row extends Component
{
    public CommunicationStatus $communicationStatus;

    public null|string|int $automated_email_template_id = '';

    public null|string|int $automated_sms_template_id = '';

    public object $loop;

    public function mount(): void
    {
        $this->automated_email_template_id = $this->communicationStatus->automated_email_template_id ?? '';
        $this->automated_sms_template_id = $this->communicationStatus->automated_sms_template_id ?? '';
    }

    public function updatedAutomatedEmailTemplateId(): void
    {
        $validatedData = $this->validate([
            'automated_email_template_id' => [
                'required',
                'integer',
                Rule::exists(AutomatedTemplate::class, 'id')->where('type', AutomatedTemplateType::EMAIL),
            ],
        ]);

        $this->communicationStatus->update($validatedData);

        $this->dispatch('refresh-email');

        $this->success(__('Email template updated successfully!'));
    }

    public function updatedAutomatedSmsTemplateId(): void
    {
        $validatedData = $this->validate([
            'automated_sms_template_id' => [
                'required',
                'integer',
                Rule::exists(AutomatedTemplate::class, 'id')->where('type', AutomatedTemplateType::SMS),
            ],
        ]);

        $this->communicationStatus->update($validatedData);

        $this->dispatch('refresh-sms');

        $this->success(__('SMS template updated successfully!'));
    }

    public function render(): View
    {
        $templates = app(CommunicationStatusService::class)->getAutomatedTemplates();

        $emailTemplateDropdownList = $templates
            ->filter(fn ($template) => $template->type === AutomatedTemplateType::EMAIL)
            ->pluck('name', 'id');

        $smsTemplateDropdownList = $templates
            ->filter(fn ($template) => $template->type === AutomatedTemplateType::SMS)
            ->pluck('name', 'id');

        return view('livewire.creditor.automated-communication.communication-status.row', [
            'smsTemplateDropdownList' => $smsTemplateDropdownList,
            'emailTemplateDropdownList' => $emailTemplateDropdownList,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\AutomatedCommunication;

use App\Enums\AutomationCampaignFrequency;
use App\Enums\CommunicationStatusTriggerType;
use App\Models\AutomationCampaign;
use App\Models\CommunicationStatus;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Form;

class AutomationCampaignForm extends Form
{
    public string|int $communication_status_id = '';

    public string $frequency = '';

    public ?string $weekly = '';

    public string|int $hours = '';

    public string|int $hourly = '';

    public function setData(AutomationCampaign $automationCampaign): void
    {
        /** @var ?Carbon $startAt */
        $startAt = $automationCampaign->start_at;

        $this->fill([
            'communication_status_id' => $automationCampaign->communicationStatus->id ?? '',
            'frequency' => $automationCampaign->frequency->value,
            'weekly' => $automationCampaign->weekly ?? '',
            'hourly' => $automationCampaign->hourly ?? '',
            'hours' => $startAt?->format('g') ?? '',
        ]);
    }

    public function rules(): array
    {
        $rules = [
            'communication_status_id' => [
                'required',
                'integer',
                Rule::exists(CommunicationStatus::class, 'id')
                    ->whereNot('trigger_type', CommunicationStatusTriggerType::AUTOMATIC)
                    ->whereNotNull('automated_email_template_id')
                    ->whereNotNull('automated_sms_template_id'),
            ],
            'frequency' => ['required', 'string', Rule::in(AutomationCampaignFrequency::values())],
            'hours' => ['required', 'integer', 'max:23'],
        ];

        if ($this->frequency === AutomationCampaignFrequency::WEEKLY->value) {
            $rules['weekly'] = ['required', 'integer', Rule::in(array_keys(Carbon::getDays()))];
        }

        if ($this->frequency === AutomationCampaignFrequency::HOURLY->value) {
            $rules['hourly'] = ['required', 'integer', Rule::in([12, 36, 48, 72])];
        }

        return $rules;
    }
}

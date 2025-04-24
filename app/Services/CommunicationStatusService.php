<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AutomatedTemplateType;
use App\Enums\CommunicationCode;
use App\Enums\CommunicationStatusTriggerType;
use App\Models\AutomatedTemplate;
use App\Models\AutomationCampaign;
use App\Models\CommunicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class CommunicationStatusService
{
    /**
     * @param array{
     *  search: string,
     *  column: string,
     *  direction: string,
     * } $data
     */
    public function fetch(array $data): Collection
    {
        return CommunicationStatus::query()
            ->select(['id', 'description', 'automated_email_template_id', 'automated_sms_template_id', 'code', 'trigger_type'])
            ->with('emailTemplate:id,name', 'smsTemplate:id,name')
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->search('code', $data['search'])
                    ->orWhereHas('emailTemplate', function (Builder $query) use ($data): void {
                        $query->search('name', $data['search']);
                    })
                    ->orWhereHas('smsTemplate', function (Builder $query) use ($data): void {
                        $query->search('name', $data['search']);
                    });
            })
            ->when($data['column'], function (Builder $query) use ($data): void {
                $query->when(
                    ! in_array($data['column'], ['trigger_type', 'automated_email_template_name', 'automated_sms_template_name']),
                    function (Builder $query) use ($data): void {
                        $query->orderBy($data['column'], $data['direction'])->orderBy('id');
                    },
                    function (Builder $query) use ($data): void {
                        $query->when($data['column'] === 'trigger_type', function (Builder $query) use ($data): void {
                            $query->orderByRaw("FIELD(trigger_type, 1,3,2) {$data['direction']}")->orderBy('id');
                        });
                        $query->when($data['column'] === 'automated_email_template_name', function (Builder $query) use ($data): void {
                            $query->orderBy(
                                AutomatedTemplate::select('name')
                                    ->where('type', AutomatedTemplateType::EMAIL)
                                    ->whereColumn('communication_statuses.automated_email_template_id', 'automated_templates.id'),
                                $data['direction']
                            )->orderBy('id');
                        });
                        $query->when($data['column'] === 'automated_sms_template_name', function (Builder $query) use ($data): void {
                            $query->orderBy(
                                AutomatedTemplate::select('name')
                                    ->where('type', AutomatedTemplateType::SMS)
                                    ->whereColumn('communication_statuses.automated_sms_template_id', 'automated_templates.id'),
                                $data['direction']
                            )->orderBy('id');
                        });
                    },
                );
            })
            ->get();
    }

    public function getAutomatedTemplates(): Collection
    {
        return AutomatedTemplate::query()
            ->select('id', 'name', 'type')
            ->get();
    }

    public function getCommunicationCode(?AutomationCampaign $automationCampaign = null): Collection
    {
        return CommunicationStatus::query()
            ->whereNot('trigger_type', CommunicationStatusTriggerType::AUTOMATIC)
            ->whereNotNull('automated_email_template_id')
            ->whereNotNull('automated_sms_template_id')
            ->whereDoesntHave('automationCampaigns', function (Builder $query) use ($automationCampaign) {
                $query->when($automationCampaign, fn (Builder $query) => $query->whereNot('id', $automationCampaign->id));
            })
            ->pluck('code', 'id');
    }

    public function isAutomatedTemplateExists(int $automatedTemplateId): bool
    {
        return CommunicationStatus::query()
            ->where(function (Builder $query) use ($automatedTemplateId): void {
                $query->where('automated_email_template_id', $automatedTemplateId)
                    ->orWhere('automated_sms_template_id', $automatedTemplateId);
            })
            ->exists();
    }

    /**
     * @throws ModelNotFoundException<CommunicationStatus>
     */
    public function findByCode(CommunicationCode $communicationCode): CommunicationStatus
    {
        return CommunicationStatus::query()
            ->whereNotNull('automated_email_template_id')
            ->whereNotNull('automated_sms_template_id')
            ->where('code', $communicationCode)
            ->select('id', 'automated_email_template_id', 'automated_sms_template_id', 'code')
            ->with(['emailTemplate:id,name,type,subject,content', 'smsTemplate:id,name,type,content'])
            ->firstOrFail();
    }
}

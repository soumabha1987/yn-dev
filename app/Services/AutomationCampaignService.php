<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CommunicationStatusTriggerType;
use App\Models\AutomatedTemplate;
use App\Models\AutomationCampaign;
use App\Models\CommunicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AutomationCampaignService
{
    /**
     * @param array{
     *  per_page: int,
     *  search: string,
     *  column: string,
     *  direction: string
     * } $data
     */
    public function fetch(array $data): LengthAwarePaginator
    {
        return AutomationCampaign::query()
            ->withWhereHas('communicationStatus', function (Builder|BelongsTo $query): void {
                $query->whereNotNull('automated_email_template_id')
                    ->whereNotNull('automated_sms_template_id')
                    ->select(['id', 'automated_email_template_id', 'automated_sms_template_id', 'code', 'description'])
                    ->with(['emailTemplate:id,name,subject,content', 'smsTemplate:id,name,content']);
            })
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->where(function (Builder $query) use ($data): void {
                    $query->search('frequency', $data['search'])
                        ->orWhereHas('communicationStatus', function (Builder $query) use ($data): void {
                            $query->search('code', $data['search'])
                                ->orWhereHas('emailTemplate', function (Builder $query) use ($data): void {
                                    $query->search('name', $data['search']);
                                })
                                ->orWhereHas('smsTemplate', function (Builder $query) use ($data): void {
                                    $query->search('name', $data['search']);
                                });
                        });
                });
            })
            ->when($data['column'], function (Builder $query) use ($data): void {
                $query->when(
                    in_array($data['column'], ['frequency', 'enabled']),
                    function (Builder $query) use ($data): void {
                        $query->orderBy($data['column'], $data['direction'])->orderBy('id');
                    },
                    function (Builder $query) use ($data): void {
                        $query->when($data['column'] === 'code', function (Builder $query) use ($data): void {
                            $query->orderBy(
                                CommunicationStatus::select('code')
                                    ->whereColumn('automation_campaigns.communication_status_id', 'communication_statuses.id'),
                                $data['direction']
                            )->orderBy('id');
                        });

                        $query->when($data['column'] === 'automated_email_template_name', function (Builder $query) use ($data): void {
                            $query->orderBy(
                                AutomatedTemplate::query()
                                    ->select('name')
                                    ->join('communication_statuses', 'communication_statuses.automated_email_template_id', '=', 'automated_templates.id')
                                    ->whereColumn('communication_statuses.id', 'automation_campaigns.communication_status_id')
                                    ->limit(1),
                                $data['direction']
                            )->orderBy('id');
                        });

                        $query->when($data['column'] === 'automated_sms_template_name', function (Builder $query) use ($data): void {
                            $query->orderBy(
                                AutomatedTemplate::query()
                                    ->select('name')
                                    ->join('communication_statuses', 'communication_statuses.automated_sms_template_id', '=', 'automated_templates.id')
                                    ->whereColumn('communication_statuses.id', 'automation_campaigns.communication_status_id')
                                    ->limit(1),
                                $data['direction']
                            )->orderBy('id');
                        });
                    }
                );
            })
            ->paginate($data['per_page']);
    }

    public function fetchEnabled(): Collection
    {
        return AutomationCampaign::query()
            ->select(['id', 'communication_status_id', 'frequency', 'weekly', 'hourly', 'start_at', 'last_sent_at'])
            ->withWhereHas('communicationStatus', function (Builder|BelongsTo $query): void {
                $query->whereNot('trigger_type', CommunicationStatusTriggerType::AUTOMATIC)
                    ->whereNotNull('automated_email_template_id')
                    ->whereNotNull('automated_sms_template_id')
                    ->select(['id', 'automated_email_template_id', 'automated_sms_template_id', 'code'])
                    ->with(['emailTemplate:id,name,type,subject,content', 'smsTemplate:id,name,type,content']);
            })
            ->where('enabled', true)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): void
    {
        AutomationCampaign::query()->create($data);
    }
}

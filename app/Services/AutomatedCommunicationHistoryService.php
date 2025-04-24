<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AutomatedCommunicationHistoryStatus;
use App\Enums\AutomatedTemplateType;
use App\Models\AutomatedCommunicationHistory;
use App\Models\AutomatedTemplate;
use App\Models\AutomationCampaign;
use App\Models\CommunicationStatus;
use App\Models\Company;
use App\Models\Consumer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AutomatedCommunicationHistoryService
{
    /**
     * @param array{
     *  search_term: string,
     *  per_page: int,
     *  column: string,
     *  direction: string,
     *  communication_code: string,
     *  template_type: string,
     *  status: string,
     *  company: string,
     *  subclient: string
     * } $data
     */
    public function fetch(array $data): LengthAwarePaginator
    {
        return AutomatedCommunicationHistory::query()
            ->select([
                'id', 'automation_campaign_id', 'consumer_id', 'company_id',
                'automated_template_id', 'automated_template_type', 'communication_status_id',
                'phone', 'email', 'cost', 'status',
            ])
            ->with([
                'communicationStatus:id,code',
                'consumer:id,first_name,last_name',
                'automatedTemplate:id,name,user_id',
            ])
            ->withWhereHas('company:id,company_name')
            ->when($data['search_term'], function (Builder $query) use ($data): void {
                $query->where(function (Builder $query) use ($data): void {
                    $query->whereHas('company', function (Builder $query) use ($data): void {
                        $query->search('company_name', $data['search_term']);
                    })
                        ->orWhereHas('automatedTemplate', function (Builder $query) use ($data): void {
                            $query->search('name', $data['search_term']);
                        })
                        ->orWhereHas('consumer', function (Builder $query) use ($data): void {
                            $searchTerm = '%' . $data['search_term'] . '%';
                            $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$searchTerm]);
                        });
                });
            })
            ->when($data['company'], function (Builder $query) use ($data): void {
                $query->where('company_id', $data['company']);
            })
            ->when($data['subclient'], function (Builder $query) use ($data): void {
                $query->where('subclient_id', $data['subclient']);
            })
            ->when($data['template_type'], function (Builder $query) use ($data): void {
                $query->search('automated_template_type', $data['template_type']);
            })
            ->when($data['status'], function (Builder $query) use ($data): void {
                $query->search('status', $data['status']);
            })
            ->when($data['communication_code'], function (Builder $query) use ($data): void {
                $query->whereHas('communicationStatus', function (Builder $query) use ($data): void {
                    $query->search('code', $data['communication_code']);
                });
            })
            ->when($data['column'] === 'communication_code', function (Builder $query) use ($data): void {
                $query->orderBy(
                    CommunicationStatus::query()
                        ->select('code')
                        ->whereColumn('automated_communication_histories.communication_status_id', 'communication_statuses.id'),
                    $data['direction']
                )->orderBy('id');
            })
            ->when($data['column'] === 'company_name', function (Builder $query) use ($data): void {
                $query->orderBy(
                    Company::query()
                        ->select('company_name')
                        ->whereColumn('automated_communication_histories.company_id', 'companies.id'),
                    $data['direction']
                )->orderBy('id');
            })
            ->when($data['column'] === 'consumer_name', function (Builder $query) use ($data): void {
                $query->orderBy(
                    Consumer::query()
                        ->selectRaw("TRIM(CONCAT_WS(' ', first_name, last_name))")
                        ->whereColumn('automated_communication_histories.consumer_id', 'consumers.id'),
                    $data['direction']
                )->orderBy('id');
            })
            ->when(in_array($data['column'], ['automated_template_type', 'cost']), function (Builder $query) use ($data): void {
                $query->orderBy($data['column'], $data['direction'])->orderBy('id');
            })
            ->when($data['column'] === 'automated_template_name', function (Builder $query) use ($data): void {
                $query->orderBy(
                    AutomatedTemplate::query()
                        ->select('name')
                        ->whereColumn('automated_communication_histories.automated_template_id', 'automated_templates.id'),
                    $data['direction']
                )->orderBy('id');
            })
            ->when($data['column'] === 'status', function (Builder $query) use ($data): void {
                $query->orderByRaw("FIELD(status, 2,3,1) {$data['direction']}")->orderBy('id');
            })
            ->paginate($data['per_page']);
    }

    public function createInProgress(
        Consumer $consumer,
        CommunicationStatus $communicationStatus,
        AutomatedTemplateType $automatedTemplateType,
        ?AutomationCampaign $automationCampaign = null
    ): AutomatedCommunicationHistory {
        return AutomatedCommunicationHistory::query()->create([
            'automation_campaign_id' => $automationCampaign?->id,
            'communication_status_id' => $communicationStatus->id,
            'consumer_id' => $consumer->id,
            'company_id' => $consumer->company_id,
            'subclient_id' => $consumer->subclient_id,
            'status' => AutomatedCommunicationHistoryStatus::IN_PROGRESS,
            'automated_template_type' => $automatedTemplateType,
        ]);
    }

    /**
     * @param  array{company_id: int, from: string, to: string}  $data
     */
    public function fetchForProcessCreditorPaymentsCommand($data): Collection
    {
        return AutomatedCommunicationHistory::query()
            ->where('company_id', $data['company_id'])
            ->where('status', AutomatedCommunicationHistoryStatus::SUCCESS)
            ->whereRaw('DATE(created_at) BETWEEN ? and ?', [$data['from'], $data['to']])
            ->get();
    }
}

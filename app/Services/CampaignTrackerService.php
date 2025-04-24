<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignTracker;
use App\Models\Consumer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignTrackerService
{
    /**
     * @param array{
     *  company_id: int,
     *  column: string,
     *  direction: string,
     *  per_page: int,
     * } $data
     */
    public function fetch(array $data): LengthAwarePaginator
    {
        return CampaignTracker::query()
            ->withWhereHas('campaign', function (Builder|BelongsTo $query) use ($data): void {
                $query->with(['template:id,name', 'group:id,name'])
                    ->where('company_id', $data['company_id']);
            })
            ->when(
                in_array($data['column'], ['created_at', 'total_balance_of_consumers', 'consumer_count', 'delivered_count']),
                function (Builder $query) use ($data): void {
                    $query->orderBy($data['column'], $data['direction'])->orderBy('id');
                }
            )
            ->when(
                in_array($data['column'], ['clicks_count', 'pif_completed_count', 'ppl_completed_count', 'custom_offer_count']),
                function (Builder $query) use ($data): void {
                    $query
                        ->orderByRaw(<<<SQL
                            CASE
                                WHEN consumer_count > 0 THEN ({$data['column']} * 100.0 / consumer_count)
                                ELSE 0
                            END {$data['direction']}
                        SQL)
                        ->orderBy('id');
                }
            )
            ->when($data['column'] === 'delivered_percentage', function (Builder $query) use ($data): void {
                $query
                    ->orderByRaw(<<<SQL
                        CASE
                            WHEN consumer_count > 0 THEN (delivered_count * 100.0 / consumer_count)
                            ELSE 0
                        END {$data['direction']}
                    SQL)
                    ->orderBy('id');
            })
            ->when($data['column'] === 'template_name', function (Builder $query) use ($data): void {
                $query
                    ->orderBy(
                        Campaign::query()
                            ->select('templates.name')
                            ->join('templates', 'campaigns.template_id', '=', 'templates.id')
                            ->whereColumn('campaigns.id', 'campaign_trackers.campaign_id'),
                        $data['direction']
                    )
                    ->orderBy('id');
            })
            ->when($data['column'] === 'group_name', function (Builder $query) use ($data): void {
                $query
                    ->orderBy(
                        Campaign::query()
                            ->select('groups.name')
                            ->join('groups', 'campaigns.group_id', '=', 'groups.id')
                            ->whereColumn('campaigns.id', 'campaign_trackers.campaign_id'),
                        $data['direction']
                    )
                    ->orderBy('id');
            })
            ->paginate($data['per_page']);
    }

    public function updateTrackerCount(Consumer $consumer, $column): void
    {
        app(GroupService::class)
            ->fetchByConsumer($consumer)
            ->each(function (CampaignTracker $campaignTracker) use ($consumer, $column): void {
                $exists = $campaignTracker->campaignTrackerConsumers()
                    ->where('consumer_id', $consumer->id)
                    ->exists();

                if ($exists) {
                    $campaignTracker->increment($column);
                }
            });
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CampaignFrequency;
use App\Models\Campaign;
use App\Models\Group;
use App\Models\Template;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CampaignService
{
    /**
     * @param array{
     *  company_id: int,
     *  search: string,
     *  per_page: int,
     *  column: string,
     *  direction: string,
     * } $data
     */
    public function fetch(array $data): LengthAwarePaginator
    {
        return Campaign::query()
            ->with('group:id,name', 'template:id,name,type')
            ->withExists('campaignTrackers')
            ->where('company_id', $data['company_id'])
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->where(function (Builder $query) use ($data): void {
                    $query->search('frequency', $data['search'])
                        ->orWhereHas('template', function (Builder $query) use ($data): void {
                            $query->search('name', $data['search']);
                        })
                        ->orWhereHas('group', function (Builder $query) use ($data): void {
                            $query->search('name', $data['search']);
                        });
                });
            })
            ->when(
                in_array($data['column'], ['frequency', 'start_date', 'end_date']),
                function (Builder $query) use ($data): void {
                    $query->orderBy($data['column'], $data['direction'])->orderBy('id');
                }
            )
            ->when(in_array($data['column'], ['name', 'type']), function (Builder $query) use ($data): void {
                $query->orderBy(
                    Template::query()
                        ->select($data['column'])
                        ->whereColumn('id', 'campaigns.template_id'),
                    $data['direction']
                )->orderBy('id');
            })
            ->when($data['column'] === 'group_name', function (Builder $query) use ($data): void {
                $query->orderBy(
                    Group::query()
                        ->select('name')
                        ->whereColumn('id', 'campaigns.group_id'),
                    $data['direction']
                )->orderBy('id');
            })
            ->paginate($data['per_page']);
    }

    public function fetchTodayRun(): Collection
    {
        return Campaign::query()
            ->with('company')
            ->withWhereHas('template')
            ->withWhereHas('group')
            ->where('is_run_immediately', false)
            ->where(function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('frequency', CampaignFrequency::ONCE)
                        ->where('start_date', today());
                })
                    ->orWhere(function (Builder $query): void {
                        $query->where('frequency', CampaignFrequency::DAILY)
                            ->where('start_date', '<=', today()->addMonthNoOverflow())
                            ->where('end_date', '>=', today()->addMonthNoOverflow());
                    })
                    ->orWhere(function (Builder $query): void {
                        $query->where('frequency', CampaignFrequency::WEEKLY)
                            ->where('start_date', '<=', today()->addMonthNoOverflow())
                            ->where('end_date', '>=', today()->addMonthNoOverflow())
                            ->where('day_of_week', today()->format('w'));
                    })
                    ->orWhere(function (Builder $query): void {
                        $query->where('frequency', CampaignFrequency::MONTHLY)
                            ->where('start_date', '<=', today()->addMonthNoOverflow())
                            ->where('end_date', '>=', today()->addMonthNoOverflow())
                            ->where('day_of_month', today()->format('d'));
                    });
            })
            ->get();
    }
}

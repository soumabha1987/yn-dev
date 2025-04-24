<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TemplateType;
use App\Models\Template;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TemplateService
{
    /**
     * @param  array{
     *  search: string,
     *  is_creditor: bool,
     *  company_id: int,
     *  column: string,
     *  direction: string,
     *  per_page: int
     * }  $data
     */
    public function fetchELetter(array $data): LengthAwarePaginator
    {
        return Template::query()
            ->select('id', 'user_id', 'name', 'type', 'subject', 'description', 'created_at')
            ->with('user:id,name')
            ->when($data['is_creditor'], function (Builder $query): void {
                $query->where('type', TemplateType::E_LETTER);
            })
            ->where('company_id', $data['company_id'])
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->search('name', $data['search']);
            })
            ->when($data['column'], function (Builder $query) use ($data): void {
                $query->when(
                    $data['column'] === 'user_name',
                    function (Builder $query) use ($data): void {
                        $query->orderBy(
                            User::query()
                                ->selectRaw('name')
                                ->whereColumn('id', 'user_id'),
                            $data['direction']
                        );
                    },
                    function (Builder $query) use ($data): void {
                        $query->orderBy($data['column'], $data['direction']);
                    }
                );
            })
            ->paginate($data['per_page']);
    }

    /**
     * @param  array{is_creditor: bool, company_id: int}  $data
     */
    public function fetchForCampaignSelectionBox(array $data): Collection
    {
        return Template::query()
            ->when(
                $data['is_creditor'],
                function (Builder $query): void {
                    $query->where('type', TemplateType::E_LETTER);
                },
                function (Builder $query) {
                    $query->whereIn('type', [TemplateType::EMAIL, TemplateType::SMS]);
                }
            )
            ->where('company_id', $data['company_id'])
            ->get();
    }
}

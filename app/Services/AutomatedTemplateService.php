<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AutomatedTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class AutomatedTemplateService
{
    /**
     * @param array{
     *  search: string,
     *  column: string,
     *  direction: string,
     *  per_page: int,
     * } $data
     */
    public function fetch(array $data): LengthAwarePaginator
    {
        return AutomatedTemplate::query()
            ->where('enabled', true)
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->where(function (Builder $query) use ($data): void {
                    $query->search('type', $data['search'])->orSearch('name', $data['search']);
                });
            })
            ->when($data['column'], function (Builder $query) use ($data): void {
                $query->orderBy($data['column'], $data['direction'])->orderBy('id');
            })
            ->paginate($data['per_page']);
    }
}

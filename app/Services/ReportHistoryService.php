<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ReportHistory;
use App\Models\Subclient;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Pagination\LengthAwarePaginator;

class ReportHistoryService
{
    /**
     * @param array{
     *  user_id: int,
     *  per_page: int,
     *  column: string,
     *  direction: string,
     * } $data
     */
    public function fetch(array $data): LengthAwarePaginator
    {
        return ReportHistory::query()
            ->with([
                'subclient' => fn (BelongsTo $belongsTo) => $belongsTo
                    ->select('id', 'subclient_name', 'unique_identification_number'),
            ])
            ->where(function (Builder $query): void {
                $query->whereNull('subclient_id')
                    ->orWhereHas('subclient');
            })
            ->where('user_id', $data['user_id'])
            ->whereNotNull(['start_date', 'end_date'])
            ->when(
                $data['column'] === 'subclient_id',
                function (Builder $query) use ($data): void {
                    $query->orderBy(
                        Subclient::query()
                            ->select('subclient_name')
                            ->whereColumn('id', 'report_histories.subclient_id'),
                        $data['direction']
                    )
                        ->orderBy('id');
                },
                function (Builder $query) use ($data): void {
                    $query->orderBy($data['column'], $data['direction'])->orderBy('id');
                }
            )
            ->paginate($data['per_page']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ReportHistory
    {
        return ReportHistory::query()->create($data);
    }
}

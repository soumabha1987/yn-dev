<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ConsumerStatus;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\ConsumerELetter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Pagination\LengthAwarePaginator;

class ConsumerELetterService
{
    public function fetchByConsumer(int $consumerId, int $perPage): LengthAwarePaginator
    {
        return ConsumerELetter::query()
            ->select('id', 'e_letter_id', 'read_by_consumer', 'enabled', 'created_at')
            ->with([
                'eLetter.company:id,company_name',
                'eLetter.subclient:id,subclient_name',
            ])
            ->where('consumer_id', $consumerId)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @param array{
     *  consumer: Consumer,
     *  per_page: int,
     *  only_read_by_consumer: bool,
     *  search: string,
     *  column: string,
     *  direction: string,
     * } $data
     */
    public function fetch(array $data): LengthAwarePaginator
    {
        return ConsumerELetter::query()
            ->with('eLetter.company')
            ->withWhereHas('consumer', function (Builder|BelongsTo $relation) use ($data): void {
                $relation
                    ->where('dob', $data['consumer']->dob->toDateString())
                    ->where('last4ssn', $data['consumer']->last4ssn)
                    ->where('last_name', $data['consumer']->last_name);
            })
            ->when($data['only_read_by_consumer'], function (Builder $query): void {
                $query->where('read_by_consumer', false);
            })
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->whereHas('eLetter.company', function (Builder $query) use ($data): void {
                    $query->search('company_name', $data['search']);
                });
            })
            ->when($data['column'] === 'created_at', function (Builder $query) use ($data): void {
                $query->orderBy($data['column'], $data['direction']);
            })
            ->when($data['column'] === 'company_name', function (Builder $query) use ($data): void {
                $query->orderBy(
                    Company::query()
                        ->select($data['column'])
                        ->join('e_letters', 'e_letters.company_id', '=', 'companies.id')
                        ->whereColumn('e_letters.id', 'consumer_e_letter.e_letter_id'),
                    $data['direction']
                );
            })
            ->when($data['column'] === 'account_offer', function (Builder $query) use ($data): void {
                $query->orderBy(
                    Consumer::query()
                        ->selectRaw(
                            <<<'SQL'
                            CASE
                            WHEN consumers.status IN (?, ?, ?) THEN 1
                            ELSE 0  END
                        SQL,
                            [
                                ConsumerStatus::JOINED,
                                ConsumerStatus::UPLOADED,
                                ConsumerStatus::RENEGOTIATE,
                            ]
                        )
                        ->whereColumn('consumers.id', 'consumer_e_letter.consumer_id'),
                    $data['direction']
                );
            })
            ->paginate($data['per_page']);
    }

    public function unreadCount(Consumer $consumer): int
    {
        return ConsumerELetter::query()
            ->withWhereHas('consumer', function (Builder|BelongsTo $relation) use ($consumer): void {
                $relation->where('dob', $consumer->dob->toDateString())
                    ->where('last4ssn', $consumer->last4ssn)
                    ->where('last_name', $consumer->last_name);
            })
            ->where('read_by_consumer', false)
            ->count();
    }
}

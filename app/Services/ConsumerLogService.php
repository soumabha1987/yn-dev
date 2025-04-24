<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ConsumerLog;
use Illuminate\Pagination\LengthAwarePaginator;

class ConsumerLogService
{
    /**
     * @param array{
     *     consumer_id: int,
     *     per_page: int,
     * } $data
     */
    public function fetch(array $data): LengthAwarePaginator
    {
        return ConsumerLog::query()
            ->where('consumer_id', $data['consumer_id'])
            ->latest()
            ->paginate($data['per_page']);
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Consumer;

use App\Models\Reason;
use Illuminate\Support\Collection;

class ReasonService
{
    public function fetch(): Collection
    {
        return Reason::query()
            ->where('is_system', true)
            ->pluck('label', 'id');
    }
}

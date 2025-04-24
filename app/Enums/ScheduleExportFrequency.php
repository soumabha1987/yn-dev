<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\SelectionBox;
use App\Enums\Traits\Values;
use Illuminate\Support\Str;

enum ScheduleExportFrequency: string
{
    use SelectionBox;
    use Values;

    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';

    public function filename(string $reportType): string
    {
        return Str::of('schedule')
            ->append('-', $this->value, '-', 'export', '-')
            ->append(Str::of($reportType)->camel()->kebab()->append('-')->toString())
            ->append(now()->format('Y-m-d-H:i:s'))
            ->append('.csv')
            ->toString();
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Values;

enum ReportHistoryStatus: int
{
    use Values;

    case SUCCESS = 1;
    case FAILED = 2;
}

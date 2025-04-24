<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\SelectionBox;
use App\Enums\Traits\Values;

enum AutomatedCommunicationHistoryStatus: int
{
    use SelectionBox;
    use Values;

    case SUCCESS = 1;
    case FAILED = 2;
    case IN_PROGRESS = 3;
}

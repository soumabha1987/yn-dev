<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Names;
use App\Enums\Traits\Values;

enum TransactionStatus: string
{
    use Names;
    use Values;

    case SUCCESSFUL = 'successful';
    case FAILED = 'failed';
    case SCHEDULED = 'scheduled';
    case RESCHEDULED = 'rescheduled';
    case CONSUMER_CHANGE_DATE = 'consumer_change_date';
    case CREDITOR_CHANGE_DATE = 'creditor_change_date';
    case CANCELLED = 'cancelled';
}

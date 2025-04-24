<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Names;
use App\Enums\Traits\Values;

enum ScheduleExportDeliveryType: string
{
    use Names;
    use Values;

    case EMAIL = 'email';
    case SFTP = 'sftp';
}

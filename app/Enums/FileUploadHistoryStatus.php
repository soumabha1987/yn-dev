<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Names;
use App\Enums\Traits\Values;

enum FileUploadHistoryStatus: string
{
    use Names;
    use Values;

    case COMPLETE = 'complete';
    case VALIDATING = 'validating';
    case FAILED = 'failed';

    public function displayStatus(): string
    {
        return match ($this) {
            self::COMPLETE => __('Completed'),
            self::VALIDATING => __('In-Progress'),
            self::FAILED => __('Failed'),
        };
    }
}

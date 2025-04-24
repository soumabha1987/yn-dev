<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

enum FeatureName: string
{
    case SCHEDULE_EXPORT = 'schedule_export';
    case SCHEDULE_IMPORT = 'schedule_import';
    case CREDITOR_COMMUNICATION = 'creditor_communication';

    public function displayName(): string
    {
        return Str::title(Str::replaceFirst('_', ' ', $this->value));
    }
}

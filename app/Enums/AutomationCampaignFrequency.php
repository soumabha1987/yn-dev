<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\SelectionBox;
use App\Enums\Traits\Values;

enum AutomationCampaignFrequency: string
{
    use SelectionBox;
    use Values;

    case ONCE = 'once';
    case HOURLY = 'hourly';
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
}

<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\SelectionBox;
use App\Enums\Traits\Values;

enum AutomatedTemplateType: string
{
    use SelectionBox;
    use Values;

    case EMAIL = 'email';
    case SMS = 'sms';
}

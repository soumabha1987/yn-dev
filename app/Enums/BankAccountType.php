<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\SelectionBox;
use App\Enums\Traits\Values;

enum BankAccountType: string
{
    use SelectionBox;
    use Values;

    case CHECKING = 'checking';
    case SAVINGS = 'savings';
}

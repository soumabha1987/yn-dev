<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Values;

enum MerchantType: string
{
    use Values;

    case CC = 'cc';
    case ACH = 'ach';

    public function displayName(): string
    {
        return match ($this) {
            self::CC => 'CARD',
            self::ACH => 'ACH',
        };
    }
}

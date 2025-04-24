<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Names;
use App\Enums\Traits\Values;

enum MerchantName: string
{
    use Names;
    use Values;

    case YOU_NEGOTIATE = 'younegotiate';
    case STRIPE = 'stripe';
    case USA_EPAY = 'usaepay';
    case AUTHORIZE = 'authorize';

    public static function filterACHAndCCMerchants(): array
    {
        return [
            self::AUTHORIZE->value,
            self::USA_EPAY->value,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Names;
use App\Enums\Traits\Values;

enum NegotiationType: string
{
    use Names;
    use Values;

    case PIF = 'pif'; // Full payment
    case INSTALLMENT = 'installment';

    public function selectionBox(): string
    {
        return match ($this) {
            self::PIF => __('One-Time Settlement Offer'),
            self::INSTALLMENT => __('Setup Payment Plan'),
        };
    }

    public function displayOfferBadge(): string
    {
        return match ($this) {
            self::PIF => __('Settlement'),
            self::INSTALLMENT => __('Pay Plan'),
        };
    }
}

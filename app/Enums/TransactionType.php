<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Names;
use App\Enums\Traits\Values;

enum TransactionType: string
{
    use Names;
    use Values;

    case PIF = 'pif';
    case INSTALLMENT = 'installment';
    case PARTIAL_PIF = 'partial_pif'; // Installment payment or Full payment of remaining installments.

    public function displayOfferBadge(): string
    {
        return match ($this) {
            self::PIF => __('Settle'),
            self::INSTALLMENT => __('Plan'),
            self::PARTIAL_PIF => __('Partial Payment'),
        };
    }
}

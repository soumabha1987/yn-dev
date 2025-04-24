<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\SelectionBox;
use App\Enums\Traits\Values;

enum MembershipFrequency: string
{
    use SelectionBox;
    use Values;

    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';

    /**
     * @return array<string>
     */
    public static function orderByForMembershipSettings(): array
    {
        return [
            self::WEEKLY->value,
            self::MONTHLY->value,
            self::YEARLY->value,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Values;

enum Role: string
{
    use Values;

    case SUPERADMIN = 'superadmin';
    case CREDITOR = 'creditor';
    case SUBCLIENT = 'subclient';

    /**
     * @return array<string>
     */
    public static function mainRoles(): array
    {
        return [
            self::SUPERADMIN->value,
            self::CREDITOR->value,
        ];
    }
}

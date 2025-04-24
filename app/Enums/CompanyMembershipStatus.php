<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Values;

enum CompanyMembershipStatus: string
{
    use Values;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

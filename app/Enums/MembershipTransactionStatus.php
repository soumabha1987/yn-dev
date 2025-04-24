<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Names;
use App\Enums\Traits\Values;

enum MembershipTransactionStatus: string
{
    use Names;
    use Values;

    case SUCCESS = 'success';
    case FAILED = 'failed';
}

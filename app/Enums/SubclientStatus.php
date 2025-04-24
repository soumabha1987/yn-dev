<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Names;
use App\Enums\Traits\Values;

enum SubclientStatus: string
{
    use Names;
    use Values;

    case CREATED = 'created';
    case STARTED = 'started';
    case SUBMITTED = 'submitted';
    case ACTIVE = 'active';
    case DISABLED = 'disabled';
    case IN_REVIEW = 'in_review';
    case REJECTED = 'rejected';
    case WITHDRAWN = 'withdrawn';
}

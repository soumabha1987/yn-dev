<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Values;

enum ELetterType: string
{
    use Values;

    case NORMAL = 'normal';
    case CFPB_WITH_QR = 'cfpb_with_qr';
    case CFPB_WITHOUT_QR = 'cfpb_without_qr';
}

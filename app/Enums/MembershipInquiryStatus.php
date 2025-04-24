<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Values;

enum MembershipInquiryStatus: int
{
    use Values;

    case NEW_INQUIRY = 0;
    case RESOLVED = 1;
    case CLOSE = 2;

    public function displayName(): string
    {
        return match ($this) {
            self::NEW_INQUIRY => __('New Inquiry'),
            self::RESOLVED => __('Plan Created'),
            self::CLOSE => __('Closed/No Deal'),
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Values;

enum MembershipFeatures: string
{
    use Values;

    case FEATURE_ONE = '24x7 Support';
    case FEATURE_TWO = 'Resource Library';
    case FEATURE_THREE = 'Consumer Communication Tools Consumer';
    case FEATURE_FOUR = 'One-on-One Coaching';
    case FEATURE_FIVE = 'Priority Access';
    case FEATURE_SIX = 'Community Access';

    public static function displayFeatures(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->name => __($case->value)])
            ->sort()
            ->toArray();
    }

    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }
}

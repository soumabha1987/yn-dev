<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\SelectionBox;
use App\Enums\Traits\Values;

enum InstallmentType: string
{
    use SelectionBox;
    use Values;

    case WEEKLY = 'weekly'; // 7 Days
    case BIMONTHLY = 'bimonthly'; // 15 Days
    case MONTHLY = 'monthly'; // 30 or 31 Days

    public function getAmountMultiplication(): int
    {
        return match ($this) {
            self::WEEKLY => 4,
            self::BIMONTHLY => 2,
            self::MONTHLY => 1,
        };
    }

    public function getCarbonMethod(): string
    {
        return match ($this) {
            self::WEEKLY => 'addWeek',
            self::BIMONTHLY => 'addBimonthly',
            self::MONTHLY => 'addMonthsNoOverflow',
        };
    }

    /**
     * Gets the Enum by value, if it exists.
     */
    public static function tryFromValue(string $value): ?static
    {
        $cases = array_filter(self::cases(), fn ($case): bool => $case->value === $value);

        return array_values($cases)[0] ?? null;
    }
}

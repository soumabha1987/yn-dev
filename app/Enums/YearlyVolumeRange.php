<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Names;
use App\Enums\Traits\Values;

enum YearlyVolumeRange: string
{
    use Names;
    use Values;

    case LOW = 'LOW';
    case MEDIUM = 'MEDIUM';
    case HIGH = 'HIGH';
    case VERY_HIGH = 'VERY_HIGH';

    public function range(): string
    {
        return match ($this) {
            self::LOW => '(0-50k)',
            self::MEDIUM => '(50-100k)',
            self::HIGH => '(100-250k)',
            self::VERY_HIGH => '(250k+)'
        };
    }

    public static function displaySelectionBox(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case): array => [
            $case->value => $case->displayName() . ' ' . $case->range(),
        ])->toArray();
    }
}

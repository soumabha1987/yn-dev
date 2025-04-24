<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Values;

enum FileUploadHistoryDateFormat: string
{
    use Values;

    case DATE_MONTH_YEAR = 'd/m/Y';
    case MONTH_DATE_YEAR = 'm/d/Y';
    case YEAR_MONTH_DATE = 'Y/m/d';
    case HYPHEN_DATE_MONTH_YEAR = 'd-m-Y';
    case HYPHEN_MONTH_DATE_YEAR = 'm-d-Y';
    case HYPHEN_YEAR_MONTH_DATE = 'Y-m-d';

    /**
     * @return array<string>
     */
    public static function displaySelectionBox(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->displayHumanFormat()])
            ->toArray();
    }

    public function displayHumanFormat(): string
    {
        return match ($this) {
            self::DATE_MONTH_YEAR => 'DD/MM/YYYY',
            self::MONTH_DATE_YEAR => 'MM/DD/YYYY',
            self::YEAR_MONTH_DATE => 'YYYY/MM/DD',
            self::HYPHEN_DATE_MONTH_YEAR => 'DD-MM-YYYY',
            self::HYPHEN_MONTH_DATE_YEAR => 'MM-DD-YYYY',
            self::HYPHEN_YEAR_MONTH_DATE => 'YYYY-MM-DD',
        };
    }
}

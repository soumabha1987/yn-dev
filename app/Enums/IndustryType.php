<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Names;
use App\Enums\Traits\Values;

enum IndustryType: string
{
    use Names;
    use Values;

    case CHARITY = 'CHARITY';
    case COMMUNITY_INTEREST_COMPANY = 'CIC';
    case CORPORATION = 'CORP';
    case LIMITED = 'LTD';
    case LIMITED_LIABILITY_COMPANY = 'LLC';
    case LIMITED_LIABILITY_PARTNERSHIP = 'LLP';
    case NON_PROFIT_CORPORATION = 'NPCORP';
    case PARTNERSHIP = 'PARTNERSHIP';
    case PUBLIC_LIMITED_COMPANY = 'PLC';
    case GOVERNMENT = 'GOV';
    case SOLE_PROPRIETORSHIP = 'SOLEPROP';
    case TRUST = 'TRUST';

    public static function displaySelectionBox(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case): array => [
                $case->value => $case->displayName(),
            ])
            ->sortBy(fn ($case) => $case, SORT_REGULAR)
            ->toArray();
    }

    public static function ssnIsNotRequired(): array
    {
        return [
            self::CHARITY->value,
            self::GOVERNMENT->value,
            self::NON_PROFIT_CORPORATION->value,
        ];
    }
}

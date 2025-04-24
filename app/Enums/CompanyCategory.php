<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Names;
use App\Enums\Traits\Values;

enum CompanyCategory: string
{
    use Names;
    use Values;

    case ACCOUNTING = 'ACCT';
    case ART = 'ART';
    case BEAUTY = 'BEAUTY';
    case CATERING = 'CATERING';
    case CHARITY = 'CHARITY';
    case CLEANING = 'CLEANING';
    case CONSULTANT = 'CONSULTANT';
    case CONTRACTOR = 'CONTRACTOR';
    case DENTIST = 'DENTIST';
    case EDUCATION = 'EDU';
    case FOOD = 'FOOD';
    case LANDSCAPING = 'LANDSCAPING';
    case LEGAL = 'LEGAL';
    case MEDICAL_PRACTICE = 'MEDICAL_PRACT';
    case MEDICAL_SERVICES = 'MEDICAL_SERV';
    case MEMBERSHIP = 'MEMBERSHIP';
    case MISCELLANEOUS_FOOD_STORES = 'MISC_FOOD_STORES';
    case MISCELLANEOUS_MERCHANDISE = 'MISC_MERCH';
    case MISCELLANEOUS_SERVICES = 'MISC_SERV';
    case MUSIC = 'MUSIC';
    case PERSONAL_COMPUTER = 'PC';
    case PHOTOGRAPHY_AND_FILM = 'PHOTO_FILM';
    case PROFESSIONAL_SERVICES = 'PROF_SERV';
    case REAL_ESTATE = 'REAL_ESTATE';
    case RECREATION = 'RECREATION';
    case REPAIR = 'REPAIR';
    case RESTAURANT = 'RESTO';
    case RETAIL = 'RETAIL';
    case TAXI = 'TAXI';
    case UTILITY = 'UTILITY';
    case VETERINARY = 'VET';
    case WEB_DEVELOPMENT = 'WEB_DEV';
    case WEB_HOSTING = 'WEB_HOSTING';
    case OTHER = 'OTHER';

    public static function displaySelectionBox(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case): array => [
                $case->value => $case->displayName(),
            ])
            ->sortBy(fn ($case) => $case, SORT_REGULAR)
            ->toArray();
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Values;

enum CompanyBusinessCategory: string
{
    use Values;

    case FIRST_PARTY = 'first_party';
    case DEBT_BUYER = 'debt_buyer';
    case THIRD_PARTY_COLLECTION_AGENCY = 'third_party_collection_agency';
    case THIRD_PARTY_COLLECTION_LAW_FIRM = 'third_party_collection_law_firm';
    case THIRD_PARTY_DEBT_SERVICE = 'third_party_debt_service';
    case AR_OUT_SOURCE = 'ar_out_source';
    case AUTO_REPOSSESSION = 'auto_repossession';
    case OTHER = 'other';

    public static function displaySelectionBox(): array
    {
        return [
            self::FIRST_PARTY->value => __('First Party/Creditor'),
            self::DEBT_BUYER->value => __('Debt Buyer'),
            self::THIRD_PARTY_COLLECTION_AGENCY->value => __('3rd Party Collection Agency'),
            self::THIRD_PARTY_COLLECTION_LAW_FIRM->value => __('3rd Party Collection Law Firm'),
            self::THIRD_PARTY_DEBT_SERVICE->value => __('3rd Party Debt Servicer'),
            self::AR_OUT_SOURCE->value => __('AR Outsourcer'),
            self::AUTO_REPOSSESSION->value => __('Auto Repossession'),
            self::OTHER->value => __('Other'),
        ];
    }

    /**
     * @return array<int, self>
     */
    public static function notAllowedYouNegotiateMerchant(): array
    {
        return [
            self::THIRD_PARTY_COLLECTION_AGENCY,
            self::THIRD_PARTY_COLLECTION_LAW_FIRM,
            self::DEBT_BUYER,
        ];
    }
}

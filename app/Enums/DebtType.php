<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Names;
use App\Enums\Traits\Values;

enum DebtType: string
{
    use Names;
    use Values;

    case AUTO = 'auto';
    case CREDIT_CARD = 'credit_card';
    case MEDICAL = 'medical';
    case MORTGAGE = 'mortgage';
    case PETS = 'pets';
    case RENT = 'rent';
    case RETAIL = 'retail';
    case STUDENT_LOAN = 'student_loan';
    case TELECOM_CABLE = 'telecom_cable';
    case UTILITIES = 'utilities';
    case OTHER = 'other';

    /**
     * @return array<string, string>
     */
    public static function displaySelectionBox(): array
    {
        return [
            self::AUTO->value => __('Auto'),
            self::CREDIT_CARD->value => __('Credit Card'),
            self::MEDICAL->value => __('Medical'),
            self::MORTGAGE->value => __('Mortgage'),
            self::PETS->value => __('Pets'),
            self::RETAIL->value => __('Retail'),
            self::STUDENT_LOAN->value => __('Student Loan'),
            self::TELECOM_CABLE->value => __('Telecom/Cable'),
            self::UTILITIES->value => __('Utilities'),
            self::OTHER->value => __('Other'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\SelectionBox;
use App\Enums\Traits\Values;

enum ReportType: string
{
    use SelectionBox;
    use Values;

    /**
     * Generate A Report
     */
    case TRANSACTION_HISTORY = 'transaction_history';
    case SCHEDULED_TRANSACTIONS = 'scheduled_transactions';

    /**
     * Exports
     */
    case CONSUMERS = 'consumers';
    case PROFILE_PERMISSIONS = 'profile_permissions';
    case COUNTER_OFFERS = 'counter_offers';
    case DEACTIVATED_AND_DISPUTE_CONSUMERS = 'deactivated_and_dispute_consumers';
    case RECENT_TRANSACTIONS = 'recent_transactions';
    case UPCOMING_TRANSACTIONS = 'upcoming_transactions';
    /**
     * If the value is greater than 1000, we will send an email to authenticate the user.
     */
    case CONSUMER_ACTIVITIES = 'consumer_activities';
    case BILLING_HISTORIES = 'billing_histories';

    /**
     * @return array<int, ReportType>
     */
    public static function dateRangeReports(): array
    {
        return [
            self::TRANSACTION_HISTORY,
            self::SCHEDULED_TRANSACTIONS,
        ];
    }
}

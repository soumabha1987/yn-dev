<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\SelectionBox;
use App\Enums\Traits\Values;

enum NewReportType: string
{
    use SelectionBox;
    use Values;

    case ALL_ACCOUNTS_STATUS_AND_ACTIVITY = 'all_accounts_status_and_activity';
    case CONSUMER_PAYMENTS = 'consumer_payments';
    case DISPUTE_NO_PAY = 'dispute_no_pay';
    case CONSUMER_OPT_OUT = 'consumer_opt_out';
    case FINAL_PAYMENTS_BALANCE_SUMMARY = 'final_payments_balance_summary';
    case SUMMARY_BALANCE_COMPLIANCE = 'summary_balance_compliance';
    case BILLING_HISTORIES = 'billing_histories';
}

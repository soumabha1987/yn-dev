<?php

declare(strict_types=1);

namespace App\Services\Consumer;

use App\Models\CompanyMembership;
use App\Models\Consumer;

class CompanyMembershipService
{
    /**
     * @return array{
     *  yn_share: string,
     *  company_share: string
     *  share_percentage: string
     * }
     */
    public function fetchShares(Consumer $consumer, float $amount): array
    {
        $membershipFee = CompanyMembership::query()
            ->with('membership:id,fee')
            ->where('company_id', $consumer->company_id)
            ->orderByDesc('current_plan_end')
            ->first()
            ->membership
            ->fee;

        $ynShare = number_format(($amount * $membershipFee / 100), 2, thousands_separator: '');
        $companyShare = number_format(($amount - $ynShare), 2, thousands_separator: '');

        return ['yn_share' => $ynShare, 'company_share' => $companyShare, 'share_percentage' => $membershipFee];
    }
}

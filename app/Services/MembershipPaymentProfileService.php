<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MembershipPaymentProfile;

class MembershipPaymentProfileService
{
    public function fetchByCompany(int $companyId): ?MembershipPaymentProfile
    {
        return MembershipPaymentProfile::query()
            ->where('company_id', $companyId)
            ->first();
    }
}

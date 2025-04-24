<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CompanyMembershipStatus;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Pagination\LengthAwarePaginator;

class CompanyMembershipService
{
    /**
     * @throws ModelNotFoundException<CompanyMembership>
     */
    public function findByCompany(int $companyId): CompanyMembership
    {
        return CompanyMembership::query()
            ->select('id', 'membership_id')
            ->with('membership:id,fee,upload_accounts_limit')
            ->where('company_id', $companyId)
            ->where('status', CompanyMembershipStatus::ACTIVE)
            ->whereNull('cancelled_at')
            ->firstOrFail();
    }

    public function hasInActiveMembership(int $companyId): bool
    {
        return CompanyMembership::query()
            ->where('company_id', $companyId)
            ->where('status', CompanyMembershipStatus::INACTIVE)
            ->whereNotNull('current_plan_start')
            ->whereNotNull('current_plan_end')
            ->whereNull('cancelled_at')
            ->exists();
    }

    public function findInActiveByCompany(int $companyId): ?CompanyMembership
    {
        return CompanyMembership::query()
            ->withWhereHas('membership')
            ->select('id', 'membership_id')
            ->where('status', CompanyMembershipStatus::INACTIVE)
            ->where('company_id', $companyId)
            ->first();
    }

    /**
     * @param array{
     *  membership_id: int,
     *  per_page: int
     * } $data
     */
    public function fetchByMembership(array $data): LengthAwarePaginator
    {
        return CompanyMembership::query()
            ->with('company', fn (BelongsTo $relation) => $relation->withTrashed()) // @phpstan-ignore-line
            ->where('membership_id', $data['membership_id'])
            ->paginate($data['per_page']);
    }

    /**
     * @return array{
     *  yn_share: string,
     *  company_share: string,
     * }
     */
    public function fetchShares(float $membershipFee, float $amount): array
    {
        $ynShare = number_format(($amount * $membershipFee / 100), 2, thousands_separator: '');
        $companyShare = number_format(($amount - $ynShare), 2, thousands_separator: '');

        return ['yn_share' => $ynShare, 'company_share' => $companyShare];
    }

    public function fetchFee(Consumer $consumer): float
    {
        return (float) CompanyMembership::query()
            ->with('membership:id,fee')
            ->where('company_id', $consumer->company_id)
            ->orderByDesc('current_plan_end')
            ->first()
            ->membership
            ->fee;
    }

    public function fetchELetterFee(int $companyId): float
    {
        return (float) CompanyMembership::query()
            ->with('membership:id,e_letter_fee')
            ->where('company_id', $companyId)
            ->orderByDesc('current_plan_end')
            ->first()
            ->membership
            ->e_letter_fee;
    }

    public function latestPlanEndDate(int $companyId)
    {
        return CompanyMembership::query()
            ->where('company_id', $companyId)
            ->latest('current_plan_end')
            ->first();
    }
}

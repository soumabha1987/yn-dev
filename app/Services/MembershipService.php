<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MembershipFrequency;
use App\Models\Membership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MembershipService
{
    /**
     * @param array{
     *  search: string,
     * } $data
     */
    public function fetch(array $data): Collection
    {
        return Membership::query()
            ->with('company')
            ->select('id', 'company_id', 'name', 'description', 'fee', 'price', 'e_letter_fee', 'frequency', 'upload_accounts_limit', 'status')
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->search('name', $data['search'])
                    ->orSearch('frequency', $data['search']);
            })
            ->where(function (Builder $query) {
                $query->whereNull('company_id')
                    ->orWhereHas('company');
            })
            ->withExists('companyMemberships')
            ->orderBy('position')
            ->get();
    }

    /**
     * @return Collection<Membership>
     */
    public function membershipsWithPricePerDay(): Collection
    {
        $companyId = Auth::user()->company_id;

        return Membership::query()
            ->select(
                'id',
                'company_id',
                'name',
                'price',
                'e_letter_fee',
                'frequency',
                'upload_accounts_limit',
                'description',
                'fee',
                'status',
                'meta_data',
                DB::raw(
                    'CASE
                    WHEN frequency = ? THEN price/7
                    WHEN frequency = ? THEN price/30
                    WHEN frequency = ? THEN price/365
                    END as price_per_day'
                )
            )
            ->addBinding(MembershipFrequency::values(), 'select')
            ->where(function (Builder $query) use ($companyId) {
                $query->where('status', true)
                    ->orWhereHas('companyMemberships', fn (Builder $query) => $query->where('company_id', $companyId));
            })
            ->where(function (Builder $query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            })
            ->with('companyMemberships', function (HasMany $query) use ($companyId): void {
                $query->select('id', 'company_id', 'membership_id', 'next_membership_plan_id', 'current_plan_end', 'auto_renew')
                    ->with('nextMembershipPlan:id,name,price,fee')
                    ->where('company_id', $companyId);
            })
            ->get();
    }

    public function fetchEnabled(int $companyId): SupportCollection
    {
        return Membership::query()
            ->select('id', 'company_id', 'name', 'description', 'price', 'frequency', 'fee', 'upload_accounts_limit', 'meta_data')
            ->where('status', true)
            ->where(function (Builder $query) use ($companyId): void {
                $query->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            })
            ->get();
    }

    /**
     * @throws ModelNotFoundException<Membership>
     */
    public function findById(int $id): Membership
    {
        return Membership::query()
            ->where('status', true)
            ->findOrFail($id);
    }
}

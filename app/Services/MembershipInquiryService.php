<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MembershipInquiryStatus;
use App\Models\Company;
use App\Models\MembershipInquiry;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class MembershipInquiryService
{
    /**
     * @param array{
     *     per_page: int,
     *     search: string,
     *     column: string,
     *     direction: string,
     * } $data
     */
    public function fetchWithCompany(array $data): LengthAwarePaginator
    {
        return MembershipInquiry::query()
            ->withWhereHas('company:id,company_name,owner_email,owner_phone')
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->whereHas('company', function (Builder $query) use ($data): void {
                    $query->search('company_name', $data['search'])
                        ->orSearch('owner_email', $data['search'])
                        ->orSearch('owner_phone', $data['search']);
                });
            })
            ->when(
                in_array($data['column'], ['created_at', 'accounts_in_scope', 'status']),
                function (Builder $query) use ($data): void {
                    $query->orderBy($data['column'], $data['direction']);
                }
            )
            ->when(
                in_array($data['column'], ['company_name', 'owner_email', 'owner_phone']),
                function (Builder $query) use ($data): void {
                    $query->orderBy(
                        Company::query()->select($data['column'])->whereColumn('companies.id', 'membership_inquiries.company_id'),
                        $data['direction']
                    );
                }
            )
            ->orderBy('id')
            ->paginate($data['per_page']);
    }

    public function membershipCreatedAt(int $companyId): ?Carbon
    {
        return MembershipInquiry::query()
            ->where('company_id', $companyId)
            ->where('status', MembershipInquiryStatus::NEW_INQUIRY)
            ->value('created_at');
    }

    public function newInquiresCount(): int
    {
        return MembershipInquiry::query()
            ->whereRelation('company', 'deleted_at', '=', null)
            ->where('status', MembershipInquiryStatus::NEW_INQUIRY)
            ->count();
    }
}

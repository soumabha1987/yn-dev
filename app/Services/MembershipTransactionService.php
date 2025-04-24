<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MembershipTransactionStatus;
use App\Models\MembershipTransaction;
use App\Models\YnTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MembershipTransactionService
{
    /**
     * @param  array{company_id: int, is_plan_expire:bool}  $data
     */
    public function exportBillingHistory(array $data): Collection
    {
        return $this->builderBillingHistory($data)->get();
    }

    /**
     * @param  array{company_id: int, is_plan_expire:bool, per_page: int}  $data
     */
    public function fetchBillingHistory(array $data): LengthAwarePaginator
    {
        return $this->builderBillingHistory($data)->paginate($data['per_page']);
    }

    /**
     * @param  array{company_id: int, is_plan_expire:bool}  $data
     */
    private function builderBillingHistory(array $data): Builder
    {
        $membershipTransactionBuilder = MembershipTransaction::query()
            ->select(
                'id',
                'status',
                'price as amount',
                'response',
                'created_at',
                DB::raw("CONCAT('M', id) as invoice_id, 'membership' as type")
            )
            ->where('company_id', $data['company_id'])
            ->where(function (Builder $query) {
                $query->where('status', MembershipTransactionStatus::SUCCESS);
            })
            ->when(
                $data['is_plan_expire'],
                function (Builder $query) use ($data) {
                    $query->orWhere(function (Builder $subQuery) use ($data) {
                        $subQuery->where('status', MembershipTransactionStatus::FAILED)
                            ->where('id', function (QueryBuilder $queryBuilder) use ($data) {
                                $queryBuilder->select('id')
                                    ->from('membership_transactions')
                                    ->where('company_id', $data['company_id'])
                                    ->latest()
                                    ->limit(1);
                            });
                    });
                }
            );

        /** @var Builder $ynTransactionBuilder */
        $ynTransactionBuilder = YnTransaction::query()
            ->select(
                'id',
                'status',
                'amount',
                'response',
                'created_at',
                DB::raw("CONCAT('Y', id) as invoice_id, 'yn' as type")
            )
            ->where('status', MembershipTransactionStatus::SUCCESS)
            ->where('company_id', $data['company_id']);

        return $membershipTransactionBuilder
            ->unionAll($ynTransactionBuilder)
            ->latest();
    }

    public function isSuccessDoesntExists(int $companyId, int $membershipId): bool
    {
        return MembershipTransaction::query()
            ->where('company_id', $companyId)
            ->where('membership_id', $membershipId)
            ->where('status', MembershipTransactionStatus::SUCCESS->value)
            ->doesntExist();
    }

    public function isSuccessExistsOfCompany(int $companyId): bool
    {
        return MembershipTransaction::query()
            ->where('company_id', $companyId)
            ->where('status', MembershipTransactionStatus::SUCCESS)
            ->exists();
    }

    public function isLastFailedTransaction(int $companyId, int $membershipId): bool
    {
        return MembershipTransaction::query()
            ->where('id', function (QueryBuilder $query) use ($companyId, $membershipId): void {
                $query->select('id')
                    ->from('membership_transactions')
                    ->where('company_id', $companyId)
                    ->where('membership_id', $membershipId)
                    ->latest()
                    ->limit(1);
            })
            ->where('status', MembershipTransactionStatus::FAILED)
            ->exists();
    }

    /**
     * @param  array{
     *  company_id: ?int,
     *  start_date: string,
     *  end_date: string,
     *  } $data
     */
    public function generateReports(array $data): Collection
    {
        $membershipTransactionHistory = MembershipTransaction::query()
            ->select(
                'id',
                'company_id',
                'membership_id',
                'price as amount',
                'response',
                'created_at',
                DB::raw("CONCAT('M', id) as invoice_id")
            )
            ->withWhereHas('membership:id,name')
            ->withWhereHas('company:id,company_name')
            ->when($data['company_id'], function (Builder $query) use ($data): void {
                $query->where('company_id', $data['company_id']);
            })
            ->where('status', MembershipTransactionStatus::SUCCESS)
            ->whereBetween('created_at', [$data['start_date'], $data['end_date']]);

        /** @var Builder $ynTransactionHistory */
        $ynTransactionHistory = YnTransaction::query()
            ->select(
                'id',
                'company_id',
                DB::raw('NULL as membership_id'),
                'amount',
                'response',
                'created_at',
                DB::raw("CONCAT('Y', id) as invoice_id")
            )
            ->withWhereHas('company:id,company_name')
            ->when($data['company_id'], function (Builder $query) use ($data): void {
                $query->where('company_id', $data['company_id']);
            })
            ->where('status', MembershipTransactionStatus::SUCCESS)
            ->whereBetween('created_at', [$data['start_date'], $data['end_date']]);

        return $membershipTransactionHistory
            ->unionAll($ynTransactionHistory)
            ->latest()
            ->get();
    }
}

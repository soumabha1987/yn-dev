<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MembershipTransactionStatus;
use App\Models\MembershipTransaction;
use App\Models\Partner;
use App\Models\YnTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PartnerService
{
    /**
     * @param  array{
     *  column: string,
     *  direction: string,
     *  per_page: int,
     * }  $data
     */
    public function fetch(array $data): LengthAwarePaginator
    {
        return $this->fetchBuilder($data)->paginate($data['per_page']);
    }

    /**
     * @param  array{
     *  column: string,
     *  direction: string,
     * }  $data
     */
    public function exportReports(array $data): Collection
    {
        return $this->fetchBuilder($data)->get();
    }

    /**
     * @param  array{
     *  column: string,
     *  direction: string
     * }  $data
     */
    private function fetchBuilder(array $data): Builder
    {
        return Partner::query()
            ->withCount('companies')
            ->addSelect([
                'total_yn_transactions_amount' => YnTransaction::query()
                    ->selectRaw('SUM(amount)')
                    ->where('status', MembershipTransactionStatus::SUCCESS)
                    ->whereHas('company', function (Builder $query): void {
                        $query->whereColumn('companies.partner_id', 'partners.id');
                    }),
                'total_membership_transactions_amount' => MembershipTransaction::query()
                    ->selectRaw('SUM(price)')
                    ->where('status', MembershipTransactionStatus::SUCCESS)
                    ->whereHas('company', function (Builder $query): void {
                        $query->whereColumn('companies.partner_id', 'partners.id');
                    }),
                'total_yn_transaction_partner_revenue' => YnTransaction::query()
                    ->selectRaw('SUM(partner_revenue_share)')
                    ->where('status', MembershipTransactionStatus::SUCCESS)
                    ->whereHas('company', function (Builder $query): void {
                        $query->whereColumn('companies.partner_id', 'partners.id');
                    }),
                'total_membership_transactions_partner_revenue' => MembershipTransaction::query()
                    ->selectRaw('SUM(partner_revenue_share)')
                    ->where('status', MembershipTransactionStatus::SUCCESS)
                    ->whereHas('company', function (Builder $query): void {
                        $query->whereColumn('companies.partner_id', 'partners.id');
                    }),
            ])
            ->when(
                in_array($data['column'], ['name', 'contact_first_name', 'contact_last_name', 'contact_email', 'contact_phone', 'revenue_share', 'creditors_quota', 'companies_count']),
                function (Builder $query) use ($data): void {
                    $query->orderBy($data['column'], $data['direction'])->orderBy('id');
                }
            )
            ->when($data['column'] === 'yn_total_revenue', function (Builder $query) use ($data) {
                $query->orderByRaw(<<<SQL
                    (IFNULL(total_yn_transactions_amount, 0) + IFNULL(total_membership_transactions_amount, 0)) {$data['direction']}
                SQL);
            })
            ->when($data['column'] === 'partner_total_revenue', function (Builder $query) use ($data) {
                $query->orderByRaw(<<<SQL
                    (IFNULL(total_yn_transaction_partner_revenue, 0) + IFNULL(total_membership_transactions_partner_revenue, 0)) {$data['direction']}
                SQL);
            })
            ->when($data['column'] === 'yn_net_revenue', function (Builder $query) use ($data) {
                $query->orderByRaw(<<<SQL
                    (
                        (IFNULL(total_yn_transactions_amount, 0) + IFNULL(total_membership_transactions_amount, 0)) -
                        (IFNULL(total_yn_transaction_partner_revenue, 0) + IFNULL(total_membership_transactions_partner_revenue, 0))
                    ) {$data['direction']}
                SQL);
            })
            ->when($data['column'] === 'quota_percentage', function (Builder $query) use ($data) {
                $query->orderByRaw(<<<SQL
                    (
                        IFNULL(companies_count, 0) * 100 / NULLIF(IFNULL(creditors_quota, 0), 0)
                    ) {$data['direction']}
                SQL);
            });

    }

    public function fetchMonthlyReports(): Collection
    {
        $data = [
            'from' => today()->subMonth()->toDateString(),
            'to' => today()->subDay()->toDateString(),
        ];

        return Partner::query()
            ->with([
                'companies' => function (HasMany $query) use ($data): void {
                    $query->select('id', 'partner_id', 'company_name', 'owner_full_name', 'created_at')
                        ->withSum(['ynTransactions as total_yn_transactions_amount' => function (Builder $query) use ($data): void {
                            $query->where('status', MembershipTransactionStatus::SUCCESS)
                                ->whereBetween('created_at', [$data['from'], $data['to']]);
                        }], 'amount')
                        ->withSum(['membershipTransactions as total_membership_transactions_amount' => function (Builder $query) use ($data): void {
                            $query->where('status', MembershipTransactionStatus::SUCCESS)
                                ->whereBetween('created_at', [$data['from'], $data['to']]);
                        }], 'price')
                        ->withSum(['ynTransactions as total_yn_transaction_partner_revenue' => function (Builder $query) use ($data): void {
                            $query->where('status', MembershipTransactionStatus::SUCCESS)
                                ->whereBetween('created_at', [$data['from'], $data['to']]);
                        }], 'partner_revenue_share')
                        ->withSum(['membershipTransactions as total_membership_transactions_partner_revenue' => function (Builder $query) use ($data): void {
                            $query->where('status', MembershipTransactionStatus::SUCCESS)
                                ->whereBetween('created_at', [$data['from'], $data['to']]);
                        }], 'partner_revenue_share');
                },
            ])
            ->get();
    }

    public function calculatePartnerRevenueShare(Partner $partner, float $amount): float
    {
        $partnerRevenueShare = 0;

        if ($partner->revenue_share > 0) {
            $partnerRevenueShare = ($amount * $partner->revenue_share) / 100;
        }

        return (float) $partnerRevenueShare;
    }
}

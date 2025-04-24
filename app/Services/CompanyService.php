<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ConsumerStatus;
use App\Enums\MembershipTransactionStatus;
use App\Models\Company;
use App\Models\Group;
use App\Models\Subclient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CompanyService
{
    /**
     * @return array<string, mixed>
     */
    public function fetchForFilters(): array
    {
        return Company::query()
            ->has('automatedCommunicationHistories')
            ->pluck('company_name', 'id')
            ->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchForSelectionBox(): array
    {
        return Company::query()
            ->has('membershipPaymentProfile')
            ->pluck('company_name', 'id')
            ->toArray();
    }

    /**
     * @param array{
     *    per_page: int,
     *    search: string,
     *    only_trashed: bool,
     *    column: string,
     *    direction: string,
     * } $data
     */
    public function fetchWithTrashed(array $data): LengthAwarePaginator
    {
        return Company::query()
            ->with('creditorUser.company')
            ->select('id', 'company_name', 'owner_full_name', 'status', 'is_wizard_steps_completed', 'pif_balance_discount_percent', 'ppa_balance_discount_percent', 'min_monthly_pay_percent', 'max_days_first_pay', 'current_step', 'business_category', 'is_deactivate', 'created_at', 'deleted_at')
            ->withCount(['consumers' => function (Builder $query): void {
                $query->whereNotIn('status', [ConsumerStatus::DEACTIVATED, ConsumerStatus::NOT_PAYING, ConsumerStatus::DISPUTE]);
            }])
            ->withSum(['consumers' => function (Builder $query): void {
                $query->whereNotIn('status', [ConsumerStatus::DEACTIVATED, ConsumerStatus::NOT_PAYING, ConsumerStatus::DISPUTE]);
            }], 'current_balance')
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->where(function (Builder $query) use ($data): void {
                    $query->search('company_name', $data['search'])
                        ->orSearch('owner_full_name', $data['search']);
                });
            })
            ->when(
                $data['only_trashed'],
                function (Builder $query): void {
                    $query->withTrashed()->whereNotNull('deleted_at');
                },
                function (Builder $query): void {
                    $query->withWhereHas('creditorUser');
                }
            )
            ->when($data['column'], function (Builder $query) use ($data): void {
                $query->orderBy($data['column'], $data['direction'])->orderBy('id');
            })
            ->paginate($data['per_page']);
    }

    /**
     * @param  array{
     *  pif_balance_discount_percent: float,
     *  ppa_balance_discount_percent: float,
     *  min_monthly_pay_percent: float,
     *  max_days_first_pay: float,
     *  minimum_settlement_percentage: int,
     *  minimum_payment_plan_percentage: int,
     *  max_first_pay_days: int
     * }  $data
     */
    public function updateTerms(int $companyId, array $data): void
    {
        Company::query()
            ->where('id', $companyId)
            ->update([
                'pif_balance_discount_percent' => $data['pif_balance_discount_percent'],
                'ppa_balance_discount_percent' => $data['ppa_balance_discount_percent'],
                'min_monthly_pay_percent' => $data['min_monthly_pay_percent'],
                'max_days_first_pay' => $data['max_days_first_pay'],
                'minimum_settlement_percentage' => $data['minimum_settlement_percentage'],
                'minimum_payment_plan_percentage' => $data['minimum_payment_plan_percentage'],
                'max_first_pay_days' => $data['max_first_pay_days'],
            ]);
    }

    public function fetchByTilledMerchantForWebhookCreation(string $tilledMerchantAccountId): ?Company
    {
        return Company::query()
            ->select('id', 'tilled_merchant_account_id')
            ->whereNull('tilled_webhook_secret')
            ->where('tilled_merchant_account_id', $tilledMerchantAccountId)
            ->first();
    }

    public function fetchWebhookSecretForValidateSignature(string $tilledMerchantAccountId): ?Company
    {
        return Company::query()
            ->select('id', 'tilled_webhook_secret')
            ->whereNotNull('tilled_webhook_secret')
            ->where('tilled_merchant_account_id', $tilledMerchantAccountId)
            ->first();
    }

    /**
     * @param array{
     *     company_id: int,
     *     column: string,
     *     direction: string,
     *     per_page: int
     * } $data
     */
    public function fetchPayTerms(array $data): LengthAwarePaginator
    {
        $companyQuery = Company::query()
            ->select(
                'id',
                'company_name as terms_name',
                'pif_balance_discount_percent',
                'ppa_balance_discount_percent',
                'min_monthly_pay_percent',
                'max_days_first_pay',
                'minimum_settlement_percentage',
                'minimum_payment_plan_percentage',
                'max_first_pay_days',
                DB::raw("'master' as type, NULL as unique_identification_number")
            )
            ->where('id', $data['company_id'])
            ->whereNotNull('pif_balance_discount_percent')
            ->whereNotNull('ppa_balance_discount_percent')
            ->whereNotNull('min_monthly_pay_percent')
            ->whereNotNull('max_days_first_pay')
            ->whereNotNull('minimum_settlement_percentage')
            ->whereNotNull('minimum_payment_plan_percentage')
            ->whereNotNull('max_first_pay_days');

        /** @var Builder $subclientQuery */
        $subclientQuery = Subclient::query()
            ->select(
                'id',
                'subclient_name as terms_name',
                'pif_balance_discount_percent',
                'ppa_balance_discount_percent',
                'min_monthly_pay_percent',
                'max_days_first_pay',
                'minimum_settlement_percentage',
                'minimum_payment_plan_percentage',
                'max_first_pay_days',
                DB::raw("'sub account' as type"),
                'unique_identification_number'
            )
            ->where('company_id', $data['company_id'])
            ->where(function (Builder $query): void {
                $query->whereNotNull('pif_balance_discount_percent')
                    ->orWhereNotNull('ppa_balance_discount_percent')
                    ->orWhereNotNull('min_monthly_pay_percent')
                    ->orWhereNotNull('max_days_first_pay')
                    ->orWhereNotNull('minimum_settlement_percentage')
                    ->orWhereNotNull('minimum_payment_plan_percentage')
                    ->orWhereNotNull('max_first_pay_days');
            });

        /** @var Builder $groupQuery */
        $groupQuery = Group::query()
            ->select(
                'id',
                'name as terms_name',
                'pif_balance_discount_percent',
                'ppa_balance_discount_percent',
                'min_monthly_pay_percent',
                'max_days_first_pay',
                'minimum_settlement_percentage',
                'minimum_payment_plan_percentage',
                'max_first_pay_days',
                DB::raw("'group' as type, NULL as unique_identification_number"),
            )
            ->where('company_id', $data['company_id'])
            ->where(function (Builder $query): void {
                $query->whereNotNull('pif_balance_discount_percent')
                    ->orWhereNotNull('ppa_balance_discount_percent')
                    ->orWhereNotNull('min_monthly_pay_percent')
                    ->orWhereNotNull('max_days_first_pay')
                    ->orWhereNotNull('minimum_settlement_percentage')
                    ->orWhereNotNull('minimum_payment_plan_percentage')
                    ->orWhereNotNull('max_first_pay_days');
            });

        return $companyQuery
            ->unionAll($subclientQuery)
            ->unionAll($groupQuery)
            ->when(
                $data['column'] === 'unique_identification_number',
                function (Builder $query) use ($data): void {
                    $query->orderByRaw(<<<SQL
                        CASE
                            WHEN unique_identification_number IS NOT NULL THEN 1
                            ELSE 0
                        END,
                        CAST(unique_identification_number AS UNSIGNED) {$data['direction']}
                    SQL);
                }
            )
            ->orderBy($data['column'], $data['direction'])
            ->orderBy('id')
            ->paginate($data['per_page']);
    }

    public function fetchForPartner(int $partnerId): Collection
    {
        return Company::query()
            ->select('id', 'partner_id', 'company_name', 'owner_full_name', 'created_at')
            ->with(['partner:id,name', 'activeCompanyMembership.membership:id,name'])
            ->withSum(['ynTransactions as total_yn_transactions_amount' => function (Builder $query): void {
                $query->where('status', MembershipTransactionStatus::SUCCESS);
            }], 'amount')
            ->withSum(['membershipTransactions as total_membership_transactions_amount' => function (Builder $query): void {
                $query->where('status', MembershipTransactionStatus::SUCCESS);
            }], 'price')
            ->withSum(['ynTransactions as total_yn_transaction_partner_revenue' => function (Builder $query): void {
                $query->where('status', MembershipTransactionStatus::SUCCESS);
            }], 'partner_revenue_share')
            ->withSum(['membershipTransactions as total_membership_transactions_partner_revenue' => function (Builder $query): void {
                $query->where('status', MembershipTransactionStatus::SUCCESS);
            }], 'partner_revenue_share')
            ->where('partner_id', $partnerId)
            ->get();
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Subclient;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SubclientService
{
    public function fetchWithTermsAndCondition(User $user): Collection
    {
        return Subclient::query()
            ->where('company_id', $user->company_id)
            ->selectRaw('id, CONCAT(subclient_name, "/", unique_identification_number) as name_with_id')
            ->pluck('name_with_id', 'id')
            ->prepend('master terms & conditions (minimum requirement)', 'all');
    }

    public function fetchTermsNameAndId(int $companyId): Collection
    {
        return $this->companyBuilder($companyId)
            ->whereNull('pif_balance_discount_percent')
            ->whereNull('ppa_balance_discount_percent')
            ->whereNull('min_monthly_pay_percent')
            ->whereNull('max_days_first_pay')
            ->selectRaw('id, CONCAT("subclient - ",subclient_name, "/", unique_identification_number) as name_with_id')
            ->selectRaw('id, CONCAT("subclient_",id) as subclient_id')
            ->pluck('name_with_id', 'subclient_id');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function fetchPay(array $data): LengthAwarePaginator
    {
        return Subclient::query()
            ->select(
                'id',
                'subclient_name',
                'unique_identification_number',
                'pif_balance_discount_percent',
                'ppa_balance_discount_percent',
                'min_monthly_pay_percent',
                'max_days_first_pay'
            )
            ->where('company_id', $data['company_id'])
            ->whereNotNull('pif_balance_discount_percent')
            ->whereNotNull('ppa_balance_discount_percent')
            ->whereNotNull('min_monthly_pay_percent')
            ->whereNotNull('max_days_first_pay')
            ->paginate($data['per_page']);
    }

    public function updateTerms(int $subclientId, array $data): void
    {
        Subclient::query()
            ->where('id', $subclientId)
            ->update([
                'pif_balance_discount_percent' => filled($data['pif_balance_discount_percent']) ? $data['pif_balance_discount_percent'] : null,
                'ppa_balance_discount_percent' => filled($data['ppa_balance_discount_percent']) ? $data['ppa_balance_discount_percent'] : null,
                'min_monthly_pay_percent' => filled($data['min_monthly_pay_percent']) ? $data['min_monthly_pay_percent'] : null,
                'max_days_first_pay' => filled($data['max_days_first_pay']) ? $data['max_days_first_pay'] : null,
                'minimum_settlement_percentage' => filled($data['minimum_settlement_percentage']) ? $data['minimum_settlement_percentage'] : null,
                'minimum_payment_plan_percentage' => filled($data['minimum_payment_plan_percentage']) ? $data['minimum_payment_plan_percentage'] : null,
                'max_first_pay_days' => filled($data['max_first_pay_days']) ? $data['max_first_pay_days'] : null,
            ]);
    }

    public function fetchSubclientTerms(int $subclientId, int $companyId): ?Subclient
    {
        return Subclient::query()
            ->select(
                'id',
                'subclient_name',
                'unique_identification_number',
                'pif_balance_discount_percent',
                'ppa_balance_discount_percent',
                'min_monthly_pay_percent',
                'max_days_first_pay',
                'minimum_settlement_percentage',
                'minimum_payment_plan_percentage',
                'max_first_pay_days',
            )
            ->where('company_id', $companyId)
            ->where(function (Builder $query): void {
                $query->whereNotNull('pif_balance_discount_percent')
                    ->orWhereNotNull('ppa_balance_discount_percent')
                    ->orWhereNotNull('min_monthly_pay_percent')
                    ->orWhereNotNull('max_days_first_pay')
                    ->orWhereNotNull('minimum_settlement_percentage')
                    ->orWhereNotNull('minimum_payment_plan_percentage')
                    ->orWhereNotNull('max_first_pay_days');
            })
            ->findOrFail($subclientId);
    }

    public function isExists(int $companyId): bool
    {
        return Subclient::query()->where('company_id', $companyId)->exists();
    }

    /**
     * @return array<string, int>
     */
    public function fetchForSelectionBox(int $companyId): array
    {
        return $this->companyBuilder($companyId)
            ->pluck('subclient_name', 'id')
            ->all();
    }

    /**
     * @return array<string, int>
     */
    public function fetchForGenerateReportSelectionBox(int $companyId): array
    {
        return $this->companyBuilder($companyId)
            ->selectRaw('id, CONCAT(subclient_name, "/", unique_identification_number) as name_with_id')
            ->pluck('name_with_id', 'id')
            ->prepend('Master - All accounts', 'master')
            ->all();
    }

    /**
     * @param  array{
     *  company_id: ?int,
     *  search: string,
     *  column: string,
     *  direction: string,
     *  per_page: int
     * }  $data
     */
    public function fetchByCompany(array $data): LengthAwarePaginator
    {
        return Subclient::query()
            ->select(
                'id',
                'subclient_name',
                'company_id',
                'unique_identification_number',
                'email',
                'phone',
                'pif_balance_discount_percent',
                'ppa_balance_discount_percent',
                'min_monthly_pay_percent',
                'max_days_first_pay',
                'minimum_settlement_percentage',
                'minimum_payment_plan_percentage',
                'max_first_pay_days',
                'created_at',
                'approved_at'
            )
            ->with('user:id,subclient_id')
            ->when($data['company_id'], function (Builder $query) use ($data): void {
                $query->where('company_id', $data['company_id']);
            })
            ->withWhereHas('company:id,company_name')
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->where(function (Builder $query) use ($data): void {
                    $query->search('subclient_name', $data['search'])
                        ->orSearch('email', $data['search'])
                        ->orSearch('phone', $data['search']);
                });
            })
            ->when($data['column'] === 'company_name', function (Builder $query) use ($data): void {
                $query->orderBy(
                    Company::select($data['column'])->whereColumn('companies.id', 'subclients.company_id'),
                    $data['direction']
                )->orderBy('id');
            })
            ->when($data['column'] === 'pay_terms', function (Builder $query) use ($data): void {
                $query->orderByRaw(<<<SQL
                    CASE
                        WHEN pif_balance_discount_percent IS NOT NULL
                            OR ppa_balance_discount_percent IS NOT NULL
                            OR min_monthly_pay_percent IS NOT NULL
                            OR max_days_first_pay IS NOT NULL
                            OR minimum_settlement_percentage IS NOT NULL
                            OR minimum_payment_plan_percentage IS NOT NULL
                            OR max_first_pay_days IS NOT NULL
                        THEN 1
                        ELSE 0
                    END {$data['direction']}
                SQL)->orderBy('id');
            })
            ->when(
                in_array($data['column'], ['subclient_name', 'created_at', 'unique_identification_number']),
                function (Builder $query) use ($data): void {
                    $query->orderBy($data['column'], $data['direction'])->orderBy('id');
                }
            )
            ->paginate($data['per_page']);
    }

    public function restore(int $subclientId, ?int $companyId): int
    {
        return Subclient::query()
            ->withTrashed()
            ->where('id', $subclientId)
            ->when($companyId, fn (Builder $query) => $query->where('company_id', $companyId))
            ->update(['deleted_at' => null]);
    }

    public function forceDelete(int $subclientId): int
    {
        return Subclient::query()
            ->withTrashed()
            ->where('id', $subclientId)
            ->forceDelete();
    }

    public function fetchByTilledMerchantForWebhookCreation(string $tilledMerchantAccountId): ?Subclient
    {
        return Subclient::query()
            ->select('id', 'tilled_merchant_account_id')
            ->whereNull('tilled_webhook_secret')
            ->where('tilled_merchant_account_id', $tilledMerchantAccountId)
            ->first();
    }

    public function fetchWebhookSecretForValidateSignature(string $tilledMerchantAccountId): ?Subclient
    {
        return Subclient::query()
            ->select('id', 'tilled_webhook_secret')
            ->whereNotNull('tilled_webhook_secret')
            ->where('tilled_merchant_account_id', $tilledMerchantAccountId)
            ->first();
    }

    private function companyBuilder(int $companyId): Builder
    {
        return Subclient::query()->where('company_id', $companyId);
    }
}

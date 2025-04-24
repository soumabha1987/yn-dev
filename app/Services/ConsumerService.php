<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ConsumerStatus;
use App\Enums\GroupCustomRules;
use App\Enums\NegotiationType;
use App\Enums\State;
use App\Models\CampaignTracker;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\Group;
use App\Models\PaymentProfile;
use App\Models\Subclient;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ConsumerService
{
    public function findByInvitationLink(string $invitationLink): ?Consumer
    {
        return Consumer::query()
            ->select(['id', 'company_id', 'subclient_id', 'status'])
            ->where('invitation_link', $invitationLink)
            ->first();
    }

    public function exportConsumers(Company $company): Collection
    {
        return Consumer::query()
            ->select('member_account_number', 'reason_id', 'first_name', 'last_name', 'dob', 'last4ssn', 'email1', 'mobile1', 'current_balance', 'invitation_link', 'status')
            ->with('reason:id,label')
            ->where('company_id', $company->id)
            ->whereNot('status', ConsumerStatus::DEACTIVATED->value)
            ->get();
    }

    public function fetchConsumersByStatus(array $conditions = []): Collection
    {
        return Consumer::query()
            ->select(
                'id',
                'company_id',
                'subclient_id',
                'consumer_profile_id',
                'first_name',
                'last_name',
                'account_number',
                'current_balance',
                'pay_setup_discount_percent',
                'ppa_amount',
                'pif_discount_percent',
                'invitation_link',
                'pass_through1',
                'pass_through2',
                'pass_through3',
                'pass_through4',
                'pass_through5',
            )
            ->with([
                'consumerProfile:id,mobile,email,text_permission,email_permission',
                'company:id,company_name,ppa_balance_discount_percent,pif_balance_discount_percent',
                'company.personalizedLogo:id,company_id,customer_communication_link,primary_color,secondary_color',
                'subclient:id,subclient_name,bank_account_number,ppa_balance_discount_percent,pif_balance_discount_percent',
                'consumerPersonalizedLogo:consumer_id,primary_color,secondary_color',
            ])
            ->whereNotNull('consumer_profile_id')
            ->whereHas('company.activeCompanyMembership')
            ->doesntHave('unsubscribe')
            ->where($conditions)
            ->get();
    }

    public function getConsumerProfileExists(Consumer $consumer): Builder
    {
        return Consumer::query()
            ->where('dob', $consumer->dob)
            ->where('last_name', $consumer->last_name)
            ->where('last4ssn', $consumer->last4ssn)
            ->whereNotNull('consumer_profile_id');
    }

    /**
     * @param  array{
     *  is_super_admin: bool,
     *  search: string,
     *  company_id: int,
     *  subclient_id: int|string,
     *  column: string,
     *  direction: string,
     *  per_page: int,
     *  status: string,
     *  company: int|string
     * }  $data
     */
    public function fetchUsingFilters(array $data): LengthAwarePaginator
    {
        return $this->listPageBuilder($data)->paginate($data['per_page']);
    }

    /**
     * @param  array{
     *  is_super_admin: bool,
     *  search: string,
     *  company_id: int,
     *  subclient_id: int|string,
     *  column: string,
     *  direction: string,
     *  status: string,
     *  company: int|string
     * }  $data
     */
    public function getUsingFilters(array $data): Collection
    {
        return $this->listPageBuilder($data)->get();
    }

    public function removeRelatedEntries(int $consumerId): void
    {
        app(ConsumerNegotiationService::class)->deleteByConsumer($consumerId);
        app(PaymentProfileService::class)->deleteByConsumer($consumerId);
        app(ScheduleTransactionService::class)->deleteByConsumer($consumerId);
        app(TransactionService::class)->deleteByConsumer($consumerId);
        app(ConsumerUnsubscribeService::class)->deleteByConsumer($consumerId);
        app(StripePaymentDetailService::class)->deleteByConsumer($consumerId);
    }

    /**
     * @param  array{
     *  company_id: int,
     *  subclient_id: ?int,
     *  search: string,
     *  column: string,
     *  direction: string,
     *  is_recently_completed_negotiation: bool,
     *  per_page: int
     * }  $data
     */
    public function getOffers(array $data): Collection
    {
        return $this->fetchOffersBuilder($data)->get();
    }

    /**
     * @param  array{
     *  company_id: int,
     *  subclient_id: ?int,
     *  search: string,
     *  column: string,
     *  direction: string,
     *  is_recently_completed_negotiation: bool,
     *  per_page: int
     * }  $data
     */
    public function fetchOffers(array $data): LengthAwarePaginator
    {
        return $this->fetchOffersBuilder($data)->paginate($data['per_page']);
    }

    /**
     * @param  array{
     *  company_id: int,
     *  subclient_id: ?int,
     *  search: string,
     *  column: string,
     *  direction: string,
     *  is_recently_completed_negotiation: bool,
     * }  $data
     */
    private function fetchOffersBuilder(array $data): Builder
    {
        return Consumer::query()
            ->select([
                'id',
                'company_id',
                'first_name',
                'last_name',
                'member_account_number',
                'original_account_name',
                'subclient_name',
                'current_balance',
                'pif_discount_percent',
                'pay_setup_discount_percent',
                'min_monthly_pay_percent',
                'counter_offer',
                'custom_offer',
                'offer_accepted',
                'payment_setup',
                'status',
                'subclient_id',
                'created_at',
            ])
            ->withWhereHas('consumerNegotiation', function (HasOne|Builder $query) use ($data): void {
                $query->select([
                    'id',
                    'consumer_id',
                    'one_time_settlement',
                    'counter_one_time_amount',
                    'counter_negotiate_amount',
                    'negotiate_amount',
                    'monthly_amount',
                    'counter_monthly_amount',
                    'negotiation_type',
                    'created_at',
                ])
                    ->where('company_id', $data['company_id'])
                    ->where('active_negotiation', true);
            })
            ->when(
                $data['is_recently_completed_negotiation'],
                function (Builder $query) use ($data): void {
                    $query->where('company_id', $data['company_id'])->whereNot('status', ConsumerStatus::PAYMENT_SETUP->value);
                },
                function (Builder $query): void {
                    $query->where('custom_offer', true)->where('status', ConsumerStatus::PAYMENT_SETUP->value);
                }
            )
            ->when($data['subclient_id'], function (Builder $query) use ($data): void {
                $query->where('subclient_id', $data['subclient_id']);
            })
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->where(function ($query) use ($data): void {
                    $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $data['search'] . '%'])
                        ->orSearch('member_account_number', $data['search']);
                });
            })
            ->when($data['column'] === 'consumer_name', function (Builder $query) use ($data): void {
                $query->orderByRaw("TRIM(CONCAT_WS(' ', first_name, last_name)) {$data['direction']}")->orderBy('id');
            })
            ->when($data['column'] === 'consumer_last_offer', function (Builder $query) use ($data): void {
                $query->orderBy(
                    ConsumerNegotiation::query()
                        ->selectRaw(
                            <<<'SQL'
                                CASE
                                    WHEN negotiation_type = ? THEN one_time_settlement
                                    WHEN negotiation_type = ? THEN monthly_amount
                                    ELSE NULL
                                END
                            SQL,
                            [NegotiationType::PIF, NegotiationType::INSTALLMENT]
                        )
                        ->whereColumn('consumer_negotiations.consumer_id', 'consumers.id'),
                    $data['direction']
                )->orderBy('id');
            })
            ->when($data['column'] === 'negotiated_amount', function (Builder $query) use ($data): void {
                $query->orderBy(
                    ConsumerNegotiation::query()
                        ->selectRaw('
                            CASE
                                WHEN negotiation_type = ? THEN COALESCE(counter_one_time_amount, one_time_settlement, 0)
                                WHEN negotiation_type = ? THEN COALESCE(counter_negotiate_amount, negotiate_amount, 0)
                                ELSE 0
                            END AS negotiated_amount', [NegotiationType::PIF->value, NegotiationType::INSTALLMENT->value])
                        ->whereColumn('consumer_id', 'consumers.id'),
                    $data['direction']
                )->orderBy('id');
            })
            ->when(
                in_array($data['column'], ['member_account_number', 'original_account_name', 'subclient_name', 'placement_date', 'payment_setup']),
                function (Builder $query) use ($data): void {
                    $query->orderBy($data['column'], $data['direction'])->orderBy('id');
                }
            )
            ->when(in_array($data['column'], ['created_at', 'negotiation_type']), function (Builder $query) use ($data): void {
                $query->orderBy(
                    ConsumerNegotiation::query()
                        ->select($data['column'])
                        ->whereColumn('consumer_id', 'consumers.id'),
                    $data['direction']
                )->orderBy('id');
            })
            ->when($data['column'] === 'status', function (Builder $query) use ($data): void {
                $query->orderByRaw(<<<SQL
                    CASE
                        WHEN offer_accepted = 1 AND counter_offer = 1 THEN 4
                        WHEN offer_accepted = 1 AND custom_offer = 1 THEN 5
                        WHEN status = ? THEN 6
                        WHEN counter_offer = 1 THEN 1
                        WHEN custom_offer = 1 THEN 3
                        WHEN offer_accepted = 1 THEN 2
                        ELSE 7
                    END {$data['direction']}
                SQL, [ConsumerStatus::PAYMENT_DECLINED])
                    ->orderBy('id');
            });
    }

    public function getCountByCompany(int $companyId): int
    {
        return Consumer::query()
            ->where('company_id', $companyId)
            ->whereNot('status', ConsumerStatus::DEACTIVATED->value)
            ->count();
    }

    public function getCountOfNewOffer(int $companyId): int
    {
        return Consumer::query()
            ->where('company_id', $companyId)
            ->where('offer_accepted', false)
            ->where('counter_offer', false)
            ->where('custom_offer', true)
            ->where('status', ConsumerStatus::PAYMENT_SETUP)
            ->count();
    }

    /**
     * @param array{
     *    user: User,
     *    per_page: int,
     *    search: string,
     * } $data
     */
    public function fetchWithConsumerActivity(array $data): LengthAwarePaginator
    {
        return Consumer::query()
            ->with('subclient:id,subclient_name')
            ->has('consumerLogs')
            ->where('company_id', $data['user']->company_id)
            ->when($data['user']->subclient_id, function (Builder $query) use ($data): void {
                $query->where('subclient_id', $data['user']->subclient_id);
            })
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->where(function (Builder $query) use ($data): void {
                    $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $data['search'] . '%'])
                        ->orSearch('account_number', $data['search']);
                });
            })
            ->paginate($data['per_page']);
    }

    public function isPaymentAcceptedForCompany(int $companyId): bool
    {
        return Consumer::query()
            ->where('company_id', $companyId)
            ->where('status', ConsumerStatus::PAYMENT_ACCEPTED)
            ->exists();
    }

    public function isPaymentAcceptedAndPaymentSetupForCompany(int $companyId): bool
    {
        return Consumer::query()
            ->where('company_id', $companyId)
            ->whereIn('status', [ConsumerStatus::PAYMENT_ACCEPTED, ConsumerStatus::PAYMENT_SETUP])
            ->exists();
    }

    public function getCountsByCompany(int $companyId, ?int $subclientId)
    {
        return Consumer::query()
            ->where('company_id', $companyId)
            ->whereNotIn('status', [ConsumerStatus::DEACTIVATED, ConsumerStatus::NOT_PAYING, ConsumerStatus::DISPUTE])
            ->when($subclientId, function (Builder $query) use ($subclientId): void {
                $query->where('subclient_id', $subclientId);
            })
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(current_balance) as total_balance_count,
                SUM(CASE WHEN payment_setup = true AND offer_accepted = true AND status <> ? THEN 1 ELSE 0 END) as accepted_count
            ', [ConsumerStatus::SETTLED->value])
            ->first();
    }

    /**
     * @param  array{ company_id: int, subclient_id: ?int, search: string, column: string, direction: string }  $data
     */
    public function getOpenNegotiationOffers(array $data): Collection
    {
        return $this->openNegotiationOfferBuilder($data)->get();
    }

    /**
     * @param array{
     * company_id: int,
     * search: string,
     * column: string,
     * direction: string
     * }$data
     */
    public function exportCompletedNegotiations(array $data): Collection
    {
        return $this->completedNegotiationBuilder($data)
            ->where('payment_setup', false)
            ->get();
    }

    /**
     * @param array{
     * company_id: int,
     * search: string,
     * column: string,
     * direction: string
     * }$data
     */
    public function exportRecentlyCompletedNegotiations(array $data): Collection
    {
        return $this->completedNegotiationBuilder($data)
            ->whereDoesntHave('transactions')
            ->where('payment_setup', true)
            ->whereHas('consumerNegotiation', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('counter_offer_accepted', true)
                        ->where('counter_first_pay_date', '>', today());
                })->orWhere(function (Builder $query): void {
                    $query->where('counter_offer_accepted', false)
                        ->where('first_pay_date', '>', today());
                });
            })
            ->get();
    }

    /**
     * @param array{
     * company_id: int,
     * search: string,
     * column: string,
     * direction: string
     * }$data
     */
    public function completedNegotiationBuilder(array $data): Builder
    {
        return Consumer::query()
            ->select(
                'id',
                'company_id',
                'first_name',
                'last_name',
                'subclient_name',
                'member_account_number',
                'original_account_name',
                'placement_date',
                'payment_setup',
                'total_balance',
            )
            ->where('company_id', $data['company_id'])
            ->withWhereHas('consumerNegotiation', function (HasOne|Builder $query) use ($data): void {
                $query->select([
                    'id',
                    'consumer_id',
                    'negotiation_type',
                    'offer_accepted',
                    'counter_offer_accepted',
                    'reason',
                    'one_time_settlement',
                    'counter_monthly_amount',
                    'counter_one_time_amount',
                    'counter_negotiate_amount',
                    'first_pay_date',
                    'counter_first_pay_date',
                    'monthly_amount',
                    'negotiate_amount',
                    'created_at',
                ])
                    ->where('company_id', $data['company_id']);
            })
            ->where('offer_accepted', true)
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->where(function (Builder $query) use ($data): void {
                    $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $data['search'] . '%'])
                        ->orSearch('member_account_number', $data['search']);
                });
            })
            ->when(
                $data['column'] === 'consumer_name',
                function (Builder $query) use ($data): void {
                    $query->orderByRaw("TRIM(CONCAT_WS(' ', first_name, last_name)) {$data['direction']}")
                        ->orderBy('id');
                }
            )
            ->when(
                in_array($data['column'], ['created_at', 'negotiation_type', 'offer_accepted']),
                function (Builder $query) use ($data): void {
                    $query
                        ->orderBy(
                            ConsumerNegotiation::query()
                                ->select($data['column'])
                                ->whereColumn('consumer_negotiations.consumer_id', 'consumers.id'),
                            $data['direction']
                        )
                        ->orderBy('id');
                }
            )
            ->when(
                in_array($data['column'], ['negotiate_amount', 'monthly_amount']),
                function (Builder $query) use ($data): void {
                    $query->orderBy(
                        ConsumerNegotiation::query()
                            ->selectRaw(<<<SQL
                                CASE
                                    WHEN negotiation_type = ? AND offer_accepted = true THEN one_time_settlement
                                    WHEN negotiation_type = ? AND counter_offer_accepted = true THEN counter_one_time_amount
                                    WHEN negotiation_type = ? AND offer_accepted = true THEN {$data['column']}
                                    WHEN negotiation_type = ? AND counter_offer_accepted = true THEN counter_{$data['column']}
                                    ELSE NULL
                                END
                            SQL, [
                                NegotiationType::PIF,
                                NegotiationType::PIF,
                                NegotiationType::INSTALLMENT,
                                NegotiationType::INSTALLMENT,
                            ])
                            ->whereColumn('consumer_negotiations.consumer_id', 'consumers.id'),
                        $data['direction']
                    )->orderBy('id');
                }
            )
            ->when(
                $data['column'] === 'first_pay_date',
                function (Builder $query) use ($data): void {
                    $query->orderBy(
                        ConsumerNegotiation::query()
                            ->selectRaw(<<<'SQL'
                                CASE
                                    WHEN offer_accepted = 1 THEN first_pay_date
                                    WHEN counter_offer_accepted = 1 THEN counter_first_pay_date
                                    ELSE NULL
                                END
                            SQL)
                            ->whereColumn('consumer_negotiations.consumer_id', 'consumers.id'),
                        $data['direction']
                    )->orderBy('id');
                }
            )
            ->when(
                in_array($data['column'], ['subclient_name', 'total_balance', 'placement_date', 'member_account_number', 'original_account_name']),
                function (Builder $query) use ($data): void {
                    $query->orderBy($data['column'], $data['direction'])->orderBy('id');
                }
            );
    }

    /**
     * @param  array{ company_id: int, subclient_id: ?int, per_page: int, search: string, column: string, direction: string }  $data
     */
    public function fetchOpenNegotiationOffers(array $data): LengthAwarePaginator
    {
        return $this->openNegotiationOfferBuilder($data)->paginate($data['per_page']);
    }

    /**
     * @param array{
     *     company_id: int,
     *     search: string,
     *     per_page: int,
     *     column: string,
     *     direction: string
     * } $data
     */
    public function fetchRecentlyCompletedNegotiations(array $data): LengthAwarePaginator
    {
        return $this->completedNegotiationBuilder($data)
            ->whereDoesntHave('transactions')
            ->where('payment_setup', true)
            ->whereHas('consumerNegotiation', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('counter_offer_accepted', true)
                        ->where('counter_first_pay_date', '>', today());
                })->orWhere(function (Builder $query): void {
                    $query->where('counter_offer_accepted', false)
                        ->where('first_pay_date', '>', today());
                });
            })
            ->paginate($data['per_page']);
    }

    /**
     * @param array{
     *     company_id: int,
     *     search: string,
     *     per_page: int,
     *     column: string,
     *     direction: string
     * } $data
     */
    public function fetchCompletedNegotiations(array $data): LengthAwarePaginator
    {
        return $this->completedNegotiationBuilder($data)
            ->where('payment_setup', false)
            ->paginate($data['per_page']);
    }

    /**
     * @param  array{
     *  company_id: int,
     *  subclient_id: ?int,
     *  search: string,
     *  column: string,
     *  direction: string
     * }  $data
     */
    public function getDeactivatedAndDispute(array $data): Collection
    {
        return $this->disputeBuilder($data)->get();
    }

    /**
     * @param  array{
     *  company_id: int,
     *  subclient_id: ?int,
     *  search: string,
     *  per_page: int,
     *  column: string,
     *  direction: string
     * }  $data
     */
    public function fetchDispute(array $data): LengthAwarePaginator
    {
        return $this->disputeBuilder($data)->paginate($data['per_page']);
    }

    /**
     * @param  array{
     *  company_id: int,
     *  subclient_id: ?int,
     *  search: string,
     *  per_page: int,
     *  column: string,
     *  direction: string,
     * }  $data
     */
    public function fetchOnPaymentPlan(array $data): LengthAwarePaginator
    {
        return Consumer::query()
            ->with([
                'company:id,company_name',
                'subclient:id,company_id,subclient_name',
                'consumerNegotiation',
                'paymentProfile' => function (HasOne $relation): void {
                    $relation->whereNull('deleted_at');
                },
            ])
            ->where('company_id', $data['company_id'])
            ->when($data['subclient_id'], function (Builder $query) use ($data): void {
                $query->where('subclient_id', $data['subclient_id']);
            })
            ->whereNot('status', ConsumerStatus::SETTLED)
            ->where('offer_accepted', true)
            ->where('payment_setup', true)
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->where(function (Builder $query) use ($data): void {
                    $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $data['search'] . '%'])
                        ->orSearch('member_account_number', $data['search']);
                });
            })
            ->when(
                in_array($data['column'], ['member_account_number', 'current_balance', 'subclient_name']),
                function (Builder $query) use ($data): void {
                    $query->orderBy($data['column'], $data['direction'])->orderBy('id');
                }
            )
            ->when($data['column'] === 'consumer_name', function (Builder $query) use ($data): void {
                $query->orderByRaw("TRIM(CONCAT_WS(' ', first_name, last_name)) {$data['direction']}")->orderBy('id');
            })
            ->when($data['column'] === 'payment_profile_created_on', function (Builder $query) use ($data): void {
                $query->orderBy(
                    PaymentProfile::query()
                        ->select('created_at')
                        ->whereColumn('payment_profiles.consumer_id', 'consumers.id')
                        ->latest()
                        ->take(1),
                    $data['direction']
                )->orderBy('id');
            })
            ->paginate($data['per_page']);
    }

    /**
     * @param  array{ company_id: int, search: string, per_page: int }  $data
     */
    public function fetchWithProfile(array $data): LengthAwarePaginator
    {
        return $this->profilePermissionBuilder($data)->paginate($data['per_page']);
    }

    /**
     * @param  array{ company_id: int, search: string }  $data
     */
    public function getWithProfile(array $data): Collection
    {
        return $this->profilePermissionBuilder($data)->get();
    }

    public function discountedPifAmount(Consumer $consumer): mixed
    {
        $discountPIFPercent = (float) (
            $consumer->pif_discount_percent
            ?? $consumer->subclient->pif_balance_discount_percent
            ?? $consumer->company->pif_balance_discount_percent
        );

        return (float) $consumer->current_balance - ((float) $consumer->current_balance * $discountPIFPercent / 100);
    }

    public function discountedPaymentPlanBalance(Consumer $consumer, ConsumerNegotiation $consumerNegotiation): mixed
    {
        if ($consumerNegotiation->negotiation_type === NegotiationType::PIF) {
            $percentage = $consumer->pif_discount_percent
                ?? $consumer->subclient->pif_balance_discount_percent
                ?? $consumer->company->pif_balance_discount_percent;

            return (float) ($consumer->current_balance * $percentage / 100);
        }

        if ($consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT) {
            if ($discountedPaymentPlanBalance = $consumer->ppa_amount) {
                return (float) $discountedPaymentPlanBalance;
            }

            return (float) ($consumer->current_balance - $this->paymentPlanDiscountAmount($consumer));
        }

        return null;
    }

    public function minimumMonthlyPayment(Consumer $consumer): mixed
    {
        $percentage = (float) ($consumer->min_monthly_pay_percent
            ?? $consumer->subclient->min_monthly_pay_percent
            ?? $consumer->company->min_monthly_pay_percent);

        if ($consumer->ppa_amount) {
            return (float) ($consumer->ppa_amount * $percentage / 100);
        }

        $paymentPlanDiscountAmount = $this->paymentPlanDiscountAmount($consumer);

        return (float) (($consumer->current_balance - $paymentPlanDiscountAmount) * $percentage / 100);
    }

    public function paymentPlanDiscountAmount(Consumer $consumer): mixed
    {
        $percentage = (float) ($consumer->pay_setup_discount_percent
            ?? $consumer->subclient->ppa_balance_discount_percent
            ?? $consumer->company->ppa_balance_discount_percent);

        return (float) (($consumer->current_balance * $percentage) / 100);
    }

    /**
     * @param  array{
     *  company_id: int,
     *  subclient_id: ?int,
     *  search: string,
     *  column: string,
     *  direction: string,
     * }  $data
     */
    private function openNegotiationOfferBuilder(array $data): Builder
    {
        return Consumer::query()
            ->select([
                'id',
                'company_id',
                'first_name',
                'last_name',
                'member_account_number',
                'original_account_name',
                'subclient_name',
                'current_balance',
                'pif_discount_percent',
                'pay_setup_discount_percent',
                'min_monthly_pay_percent',
                'counter_offer',
                'custom_offer',
                'offer_accepted',
                'payment_setup',
                'status',
                'subclient_id',
                'created_at',
            ])
            ->withWhereHas('consumerNegotiation', function (HasOne|Builder $query) use ($data): void {
                $query->select([
                    'id',
                    'consumer_id',
                    'one_time_settlement',
                    'counter_one_time_amount',
                    'monthly_amount',
                    'counter_monthly_amount',
                    'counter_first_pay_date',
                    'first_pay_date',
                    'installment_type',
                    'negotiation_type',
                    'created_at',
                    // Ref: Below fields are required in the resources/views/livewire/creditor/consumer-offers/view-offer.blade.php
                    'negotiate_amount',
                    'reason',
                    'note',
                    'counter_note',
                    'counter_negotiate_amount',
                ])
                    ->where('company_id', $data['company_id'])
                    ->where('offer_accepted', false)
                    ->where('counter_offer_accepted', false);
            })
            ->where('company_id', $data['company_id'])
            ->where('status', ConsumerStatus::PAYMENT_SETUP)
            ->when($data['subclient_id'], function (Builder $query) use ($data): void {
                $query->where('subclient_id', $data['subclient_id']);
            })
            ->where('offer_accepted', false)
            ->whereNotIn('status', [ConsumerStatus::DEACTIVATED->value, ConsumerStatus::PAYMENT_DECLINED->value])
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->where(function (Builder $query) use ($data): void {
                    $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $data['search'] . '%'])
                        ->orSearch('member_account_number', $data['search']);
                });
            })
            ->when($data['column'] === 'consumer_name', function (Builder $query) use ($data): void {
                $query->orderByRaw("TRIM(CONCAT_WS(' ', first_name, last_name)) {$data['direction']}")
                    ->orderBy('id');
            })
            ->when(in_array($data['column'], ['created_at', 'negotiation_type']), function (Builder $query) use ($data): void {
                $query->orderBy(
                    ConsumerNegotiation::query()
                        ->select($data['column'])
                        ->whereColumn('consumer_id', 'consumers.id'),
                    $data['direction']
                )->orderBy('id');
            })
            ->when($data['column'] === 'consumer_last_offer', function (Builder $query) use ($data): void {
                $query->orderBy(
                    ConsumerNegotiation::query()
                        ->selectRaw(
                            <<<'SQL'
                                CASE
                                    WHEN negotiation_type = ? THEN one_time_settlement
                                    WHEN negotiation_type = ? THEN monthly_amount
                                    ELSE NULL
                                END
                            SQL,
                            [NegotiationType::PIF, NegotiationType::INSTALLMENT]
                        )
                        ->whereColumn('consumer_negotiations.consumer_id', 'consumers.id'),
                    $data['direction']
                )->orderBy('id');
            })
            ->when(
                in_array($data['column'], ['member_account_number', 'original_account_name', 'subclient_name', 'placement_date', 'payment_setup', 'counter_offer']),
                function (Builder $query) use ($data): void {
                    $query->orderBy($data['column'], $data['direction'])->orderBy('id');
                }
            );
    }

    /**
     * @param  array{
     *  company_id: int,
     *  subclient_id: ?int,
     *  search: string,
     *  column: string,
     *  direction: string
     * }  $data
     */
    private function disputeBuilder(array $data): Builder
    {
        return Consumer::query()
            ->where('company_id', $data['company_id'])
            ->when($data['subclient_id'], function (Builder $query) use ($data): void {
                $query->where('subclient_id', $data['subclient_id']);
            })
            ->whereNotNull('disputed_at')
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->where(function (Builder $query) use ($data): void {
                    $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $data['search'] . '%']);
                });
            })
            ->when($data['column'] === 'consumer_name', function (Builder $query) use ($data): void {
                $query->orderByRaw("TRIM(CONCAT_WS(' ', first_name, last_name)) {$data['direction']}")
                    ->orderBy('id');
            })
            ->when(
                in_array($data['column'], ['disputed_at', 'current_balance', 'original_account_name', 'member_account_number', 'subclient_name', 'placement_date']),
                function (Builder $query) use ($data): void {
                    $query->orderBy($data['column'], $data['direction'])->orderBy('id');
                }
            );
    }

    /**
     * @param array{
     *  is_super_admin: bool,
     *  company_id: int,
     *  search: string,
     *  subclient_id: ?int,
     *  status: string,
     *  company: int|string,
     *  column: string,
     *  direction: string
     * }  $data
     */
    private function listPageBuilder(array $data): Builder
    {
        return Consumer::query()
            ->select([
                'id',
                'company_id',
                'subclient_id',
                'reason_id',
                'first_name',
                'last_name',
                'dob',
                'last4ssn',
                'current_balance',
                'member_account_number',
                'original_account_name',
                'subclient_name',
                'subclient_account_number',
                'placement_date',
                'payment_setup',
                'email1',
                'last4ssn',
                'hold_reason',
                'invitation_link',
                'status',
            ])
            ->with('reason:id,label', 'consumerNegotiation:id,consumer_id,negotiation_type')
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->where(function (Builder $query) use ($data): void {
                    $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $data['search'] . '%'])
                        ->orSearch('member_account_number', $data['search'])
                        ->orSearch('account_number', $data['search'])
                        ->orSearch('mobile1', $data['search'])
                        ->orSearch('email1', $data['search'])
                        ->orSearch('last4ssn', $data['search']);
                });
            })
            ->when($data['column'], function (Builder $query) use ($data): void {
                $query->when(
                    in_array($data['column'], ['member_account_number', 'original_account_name', 'subclient_name', 'placement_date']),
                    function (Builder $query) use ($data): void {
                        $query->orderBy($data['column'], $data['direction'])->orderBy('id');
                    },
                    function (Builder $query) use ($data): void {
                        $query->when($data['column'] === 'company_name', function (Builder $query) use ($data): void {
                            $query->orderBy(Company::select($data['column'])->whereColumn('companies.id', 'consumers.company_id'), $data['direction'])
                                ->orderBy('id');
                        })->when($data['column'] === 'name', function (Builder $query) use ($data): void {
                            $query->orderByRaw("TRIM(CONCAT_WS(' ', first_name, last_name)) {$data['direction']}")
                                ->orderBy('id');
                        })->when($data['column'] === 'account_status', function (Builder $query) use ($data): void {
                            $query->orderByRaw(
                                "
                                CASE
                                    WHEN status IN (?, ?, ?) THEN 1
                                    ELSE 2
                                END {$data['direction']}
                            ",
                                [ConsumerStatus::DEACTIVATED, ConsumerStatus::DISPUTE, ConsumerStatus::NOT_PAYING]
                            )
                                ->orderBy('id', $data['direction']);
                        })->when($data['column'] === 'status', function (Builder $query) use ($data): void {
                            $query->when($data['column'] === 'status', function (Builder $query) use ($data): void {
                                $query->orderByRaw(<<<SQL
                                    CASE
                                        WHEN status = ? AND payment_setup = 1 THEN 1
                                        WHEN status = ? AND payment_setup = 0 THEN 2
                                        WHEN status = ? THEN 3
                                        WHEN status = ? THEN 4
                                        WHEN status = ? THEN 5
                                        WHEN status = ? THEN 6
                                        WHEN status = ? THEN 7
                                        WHEN status = ? THEN 8
                                        WHEN status = ? THEN 9
                                        WHEN status = ? THEN 10
                                        WHEN status = ? THEN 11
                                        ELSE 12
                                    END {$data['direction']}
                                SQL, [
                                    ConsumerStatus::PAYMENT_ACCEPTED,
                                    ConsumerStatus::PAYMENT_ACCEPTED,
                                    ConsumerStatus::HOLD,
                                    ConsumerStatus::DEACTIVATED,
                                    ConsumerStatus::DISPUTE,
                                    ConsumerStatus::PAYMENT_SETUP,
                                    ConsumerStatus::PAYMENT_DECLINED,
                                    ConsumerStatus::UPLOADED,
                                    ConsumerStatus::JOINED,
                                    ConsumerStatus::NOT_PAYING,
                                    ConsumerStatus::SETTLED,
                                ])->orderBy('id');
                            });
                        });
                    }
                );
            })
            ->when($data['company'] !== '' && $data['is_super_admin'], function (Builder $query) use ($data): void {
                $query->where(function (Builder $query) use ($data): void {
                    $query->whereRelation('company', 'id', $data['company']);
                });
            })
            ->when(! $data['is_super_admin'], function (Builder $query) use ($data): void {
                $query->where('company_id', $data['company_id']);
            }, function (Builder $query): void {
                $query->with('subclient:id,subclient_name')
                    ->withWhereHas('company:id,company_name');
            })
            ->when(
                in_array(
                    $data['status'],
                    [
                        ConsumerStatus::JOINED->value,
                        ConsumerStatus::UPLOADED->value,
                        ConsumerStatus::PAYMENT_SETUP->value,
                        ConsumerStatus::SETTLED->value,
                        ConsumerStatus::NOT_PAYING->value,
                        ConsumerStatus::PAYMENT_DECLINED->value,
                        ConsumerStatus::DISPUTE->value,
                        ConsumerStatus::DEACTIVATED->value,
                        ConsumerStatus::HOLD->value,
                    ]
                ),
                function (Builder $query) use ($data): void {
                    $query->where('status', $data['status']);
                }
            )
            ->when($data['status'] === 'active_payment_plan', function (Builder $query) {
                $query->where('status', ConsumerStatus::PAYMENT_ACCEPTED)
                    ->where('payment_setup', true)
                    ->where('offer_accepted', true);
            })
            ->when($data['status'] === 'agreed_settlement_pending_payment', function (Builder $query) {
                $query
                    ->whereNot('status', ConsumerStatus::SETTLED)
                    ->where('payment_setup', false)
                    ->where('offer_accepted', true)
                    ->whereRelation('consumerNegotiation', 'negotiation_type', NegotiationType::PIF);
            })
            ->when($data['status'] === 'agreed_payment_plan_pending_payment', function (Builder $query) {
                $query
                    ->whereNot('status', ConsumerStatus::SETTLED)
                    ->where('payment_setup', false)
                    ->where('offer_accepted', true)
                    ->whereRelation('consumerNegotiation', 'negotiation_type', NegotiationType::INSTALLMENT);
            })
            ->when($data['subclient_id'], function (Builder $query) use ($data): void {
                $query->where('subclient_id', $data['subclient_id']);
            });
    }

    /**
     * @param  array{ company_id: int, search: string }  $data
     */
    private function profilePermissionBuilder(array $data): Builder
    {
        return Consumer::query()
            ->withWhereHas('consumerProfile', function (BelongsTo|Builder $relation): void {
                $relation->withCasts(['state' => State::class]);
            })
            ->where('company_id', $data['company_id'])
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->where(function (Builder $query) use ($data): void {
                    $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $data['search'] . '%'])
                        ->orSearch('account_number', $data['search'])
                        ->orSearch('email1', $data['search'])
                        ->orSearch('mobile1', $data['search']);
                });
            })
            ->withCasts(['state' => State::class]);
    }

    /**
     * @param  array{ company_id: ?int, subclient_id: ?int, start_date: string, end_date: string }  $data
     */
    public function generateReports(array $data): Collection
    {
        return Consumer::query()
            ->select(['id', 'company_id', 'subclient_id', 'reason_id', 'member_account_number', 'first_name', 'last_name', 'last4ssn', 'dob', 'email1', 'mobile1', 'current_balance', 'status', 'invitation_link'])
            ->with(['company:id,company_name', 'reason:id,label'])
            ->when($data['company_id'], function (Builder $query) use ($data): void {
                $query->where('company_id', $data['company_id']);
            })
            ->when($data['subclient_id'], function (Builder $query) use ($data): void {
                $query->where('subclient_id', $data['subclient_id']);
            })
            ->whereBetween('created_at', [$data['start_date'], $data['end_date']])
            ->get();
    }

    /**
     * @param  array{ company_id: ?int, subclient_id: ?int, start_date: string, end_date: string }  $data
     */
    public function reportOfCounterOffers(array $data): Collection
    {
        return Consumer::query()
            ->with(['company', 'subclient'])
            ->withWhereHas('consumerNegotiation', function (HasOne|Builder $query) use ($data): void {
                $query->where('active_negotiation', true)
                    // TODO: Think about it when we said counter offer at that time we need to check the date!
                    ->whereBetween('created_at', [$data['start_date'], $data['end_date']]);
            })
            ->where('counter_offer', true)
            ->whereNot('status', ConsumerStatus::DEACTIVATED->value)
            ->when($data['company_id'], function (Builder $query) use ($data): void {
                $query->where('company_id', $data['company_id']);
            })
            ->when($data['subclient_id'], function (Builder $query) use ($data): void {
                $query->where('subclient_id', $data['subclient_id']);
            })
            ->get();
    }

    /**
     * @param  array{ company_id: ?int, subclient_id: ?int, start_date: string, end_date: string }  $data
     */
    public function reportOfDeactivatedAndDispute(array $data): Collection
    {
        return Consumer::query()
            ->with('company:id,company_name')
            ->select(['id', 'company_id', 'subclient_id', 'account_number', 'first_name', 'last_name', 'last4ssn', 'email1', 'mobile1', 'status'])
            ->whereIn('status', [ConsumerStatus::DEACTIVATED->value, ConsumerStatus::DISPUTE->value])
            ->when($data['company_id'], function (Builder $query) use ($data): void {
                $query->where('company_id', $data['company_id']);
            })
            ->when($data['subclient_id'], function (Builder $query) use ($data): void {
                $query->where('subclient_id', $data['subclient_id']);
            })
            ->whereBetween('updated_at', [$data['start_date'], $data['end_date']])
            ->get();
    }

    /**
     * @param  array{ company_id: ?int, subclient_id: ?int, start_date: string, end_date: string }  $data
     */
    public function reportProfilePermission(array $data): Collection
    {
        return Consumer::query()
            ->with('company:id,company_name')
            ->withWhereHas('consumerProfile', function (BelongsTo|Builder $relation) use ($data): void {
                $relation->whereBetween('created_at', [$data['start_date'], $data['end_date']])
                    ->withCasts(['state' => State::class]);
            })
            ->when($data['company_id'], function (Builder $query) use ($data): void {
                $query->where('company_id', $data['company_id']);
            })
            ->when($data['subclient_id'], function (Builder $query) use ($data): void {
                $query->where('subclient_id', $data['subclient_id']);
            })
            ->get();
    }

    /**
     * @param  array{ company_id: int, search: string, column: string, direction: string }  $data
     */
    public function exportNotNullPayTerms(array $data): Collection
    {
        return $this->notNullPayTermsBuilder($data)->get();
    }

    /**
     * @param  array{ company_id: int, per_page: int, search: string, column: string, direction: string }  $data
     */
    public function fetchNotNullPayTerms(array $data): LengthAwarePaginator
    {
        return $this->notNullPayTermsBuilder($data)->paginate($data['per_page']);
    }

    /**
     * @param  array{ company_id: int, search: string, column: string, direction: string }  $data
     */
    private function notNullPayTermsBuilder(array $data): Builder
    {
        return Consumer::query()
            ->select(
                'id',
                'member_account_number',
                'first_name',
                'last_name',
                'subclient_account_number',
                'subclient_name',
                'total_balance',
                'pif_discount_percent',
                'pay_setup_discount_percent',
                'min_monthly_pay_percent',
                'max_days_first_pay',
            )
            ->where('company_id', $data['company_id'])
            ->where(function (Builder $query): void {
                $query->whereNotNull('pif_discount_percent')
                    ->orWhereNotNull('pay_setup_discount_percent')
                    ->orWhereNotNull('min_monthly_pay_percent')
                    ->orWhereNotNull('max_days_first_pay');
            })
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->where(function ($query) use ($data): void {
                    $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $data['search'] . '%'])
                        ->orSearch('member_account_number', $data['search']);
                });
            })
            ->when($data['column'] === 'consumer_name', function (Builder $query) use ($data): void {
                $query->orderByRaw("TRIM(CONCAT_WS(' ', first_name, last_name)) {$data['direction']}")->orderBy('id');
            })
            ->when($data['column'] === 'amount', function (Builder $query) use ($data): void {
                $query->orderByRaw(<<<SQL
                    CASE
                        WHEN pay_setup_discount_percent IS NOT NULL
                            AND min_monthly_pay_percent IS NOT NULL
                        THEN
                            (total_balance - (total_balance * pay_setup_discount_percent / 100))
                            * min_monthly_pay_percent / 100
                        ELSE NULL
                    END {$data['direction']}
                SQL)->orderBy('id');
            })
            ->when(in_array($data['column'], ['member_account_number', 'subclient_name', 'total_balance', 'pif_discount_percent', 'pay_setup_discount_percent', 'max_days_first_pay']), function (Builder $query) use ($data): void {
                $query->orderBy($data['column'], $data['direction'])->orderBy('id');
            });
    }

    public function fetchByGroup(Group $group, ?int $companyId = null): Collection
    {
        return $this->fetchByGroupBuilder($group, $companyId)->get();
    }

    /**
     * @return Builder<Consumer>
     */
    public function fetchByGroupBuilder(Group $group, ?int $companyId = null): Builder
    {
        return Consumer::query()
            ->when($companyId, function (Builder $query) use ($companyId): void {
                $query->where('company_id', $companyId);
            })
            ->where(function (Builder $query) use ($group): void {
                $group->consumer_state->getBuilder($query);

                if (blank($group->custom_rules)) {
                    return;
                }

                foreach ($group->custom_rules as $rule => $data) {
                    GroupCustomRules::tryFrom($rule)->getBuilder($query, $data);
                }
            });
    }

    public function countByGroup(Group $group, ?int $companyId = null): Consumer
    {
        return $this->fetchByGroupBuilder($group, $companyId)
            ->selectRaw('id, COUNT(*) as total_count, SUM(current_balance) as total_balance')
            ->first();
    }

    public function updateCampaignTrackerClickCount(array $data): void
    {
        Consumer::query()
            ->where('last_name', $data['last_name'])
            ->where('last4ssn', $data['last_four_ssn'])
            ->where('dob', $data['dob'])
            ->each(function (Consumer $consumer): void {
                app(GroupService::class)
                    ->fetchByConsumer($consumer)
                    ->each(function (CampaignTracker $campaignTracker) use ($consumer): void {
                        $firstClickExists = $campaignTracker->campaignTrackerConsumers()
                            ->where('consumer_id', $consumer->id)
                            ->where('click', 0)
                            ->exists();

                        if ($firstClickExists) {
                            $campaignTracker->campaignTrackerConsumers()
                                ->where('consumer_id', $consumer->id)
                                ->where('click', 0)
                                ->update(['click' => 1]);

                            $campaignTracker->increment('clicks_count');
                        }
                    });
            });
    }

    /**
     * @param  array{ company_id: ?int, subclient_id: ?int, start_date: string, end_date: string }  $data
     */
    public function reportDisputeNoPay(array $data): Collection
    {
        return Consumer::query()
            ->select([
                'first_name',
                'last_name',
                'dob',
                'last4ssn',
                'current_balance',
                'original_account_name',
                'account_number',
                'member_account_number',
                'reference_number',
                'statement_id_number',
                'subclient_id',
                'subclient_name',
                'subclient_account_number',
                'placement_date',
                'expiry_date',
                'status',
                'reason_id',
                'disputed_at',
            ])
            ->with('reason:id,label')
            ->whereIn('status', [ConsumerStatus::DEACTIVATED, ConsumerStatus::DISPUTE, ConsumerStatus::NOT_PAYING])
            ->when($data['company_id'], function (Builder $query) use ($data): void {
                $query->where('company_id', $data['company_id']);
            })
            ->when($data['subclient_id'], function (Builder $query) use ($data): void {
                $query->where('subclient_id', $data['subclient_id']);
            })
            ->whereBetween('disputed_at', [$data['start_date'], $data['end_date']])
            ->get();
    }

    /**
     * @param  array{ company_id: ?int, subclient_id: ?int }  $data
     */
    public function reportConsumerOptOut(array $data): Collection
    {
        return Consumer::query()
            ->select([
                'consumer_profile_id',
                'first_name',
                'last_name',
                'dob',
                'last4ssn',
                'current_balance',
                'original_account_name',
                'account_number',
                'member_account_number',
                'reference_number',
                'statement_id_number',
                'subclient_id',
                'subclient_name',
                'subclient_account_number',
                'placement_date',
                'expiry_date',
                'email1',
                'mobile1',
            ])
            ->withWhereHas('consumerProfile', function (BelongsTo|Builder $query): void {
                $query->select(['id', 'text_permission', 'email_permission'])
                    ->where('text_permission', false)
                    ->orWhere('email_permission', false);
            })
            ->when($data['company_id'], function (Builder $query) use ($data): void {
                $query->where('company_id', $data['company_id']);
            })
            ->when($data['subclient_id'], function (Builder $query) use ($data): void {
                $query->where('subclient_id', $data['subclient_id']);
            })
            ->get();
    }

    /**
     * @param  array{ company_id: ?int, subclient_id: ?int }  $data
     */
    public function reportFinalPaymentsBalanceSummary(array $data): Collection
    {
        return Consumer::query()
            ->select([
                'first_name',
                'last_name',
                'dob',
                'last4ssn',
                'current_balance',
                'original_account_name',
                'account_number',
                'member_account_number',
                'reference_number',
                'statement_id_number',
                'subclient_id',
                'subclient_name',
                'subclient_account_number',
                'placement_date',
                'expiry_date',
                'payment_setup',
                'status',
                'total_balance',
            ])
            ->with('consumerNegotiation:id,consumer_id,negotiation_type')
            ->when($data['company_id'], function (Builder $query) use ($data): void {
                $query->where('company_id', $data['company_id']);
            })
            ->when($data['subclient_id'], function (Builder $query) use ($data): void {
                $query->where('subclient_id', $data['subclient_id']);
            })
            ->get();
    }

    /**
     * @param  array{ company_id: ?int, subclient_id: ?int }  $data
     */
    public function reportSummaryBalanceCompliance(array $data): Collection
    {
        return Consumer::query()
            ->select([
                'id',
                'company_id',
                'account_number',
                'member_account_number',
                'last_name',
                'dob',
                'last4ssn',
                'original_account_name',
                'total_balance',
                'current_balance',
                'payment_setup',
                'status',
            ])
            ->with(['company:id,company_name', 'consumerNegotiation:id,consumer_id,negotiation_type'])
            ->when($data['company_id'], function (Builder $query) use ($data): void {
                $query->where('company_id', $data['company_id']);
            })
            ->when($data['subclient_id'], function (Builder $query) use ($data): void {
                $query->where('subclient_id', $data['subclient_id']);
            })
            ->get();
    }

    /**
     * @param  array{ company_id: ?int, subclient_id: ?int, start_date: string, end_date: string }  $data
     */
    public function reportAllAccountStatusAndActivity(array $data): Collection
    {
        return Consumer::query()
            ->with([
                'consumerNegotiation',
                'consumerProfile:id,text_permission,email_permission',
                'company:id,pif_balance_discount_percent,ppa_balance_discount_percent,max_days_first_pay,min_monthly_pay_percent',
                'subclient:id,pif_balance_discount_percent,ppa_balance_discount_percent,max_days_first_pay,min_monthly_pay_percent',
                'scheduledTransactions:id,consumer_id,status,schedule_date,amount',
            ])
            ->when($data['company_id'], function (Builder $query) use ($data): void {
                $query->where('company_id', $data['company_id']);
            })
            ->when($data['subclient_id'], function (Builder $query) use ($data): void {
                $query->where('subclient_id', $data['subclient_id']);
            })
            ->whereBetween('created_at', [$data['start_date'], $data['end_date']])
            ->get();
    }
}

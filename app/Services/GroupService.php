<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ConsumerStatus;
use App\Enums\GroupConsumerState;
use App\Enums\GroupCustomRules;
use App\Enums\NegotiationType;
use App\Enums\Role;
use App\Enums\TransactionStatus;
use App\Models\Campaign;
use App\Models\Consumer;
use App\Models\Group;
use App\Models\ScheduleTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GroupService
{
    /**
     * @param array{
     *  per_page: int,
     *  company_id: int,
     *  search: string,
     *  column: string,
     *  direction: string,
     *  user: User,
     * } $data
     */
    public function fetch(array $data)
    {
        return Group::query()
            ->with('user:id,name')
            ->when(
                $data['user']->hasRole(Role::CREDITOR),
                function (Builder $query): void {
                    $query->select(
                        'id',
                        'name',
                        'user_id',
                        'company_id',
                        'consumer_state',
                        'custom_rules',
                        'ppa_balance_discount_percent',
                        'pif_balance_discount_percent',
                        'min_monthly_pay_percent',
                        'max_days_first_pay',
                        'minimum_settlement_percentage',
                        'minimum_payment_plan_percentage',
                        'max_first_pay_days',
                        'created_at'
                    );
                },
                function (Builder $query): void {
                    $query->select(
                        'id',
                        'name',
                        'user_id',
                        'company_id',
                        'consumer_state',
                        'custom_rules',
                        'created_at'
                    );
                }
            )
            ->where('company_id', $data['company_id'])
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->where(function (Builder $query) use ($data): void {
                    $consumerState = data_get(array_flip(GroupConsumerState::displaySelectionBox()), $data['search']);
                    $query->when(
                        $consumerState,
                        function (Builder $query) use ($consumerState): void {
                            $query->where('consumer_state', $consumerState);
                        },
                        function (Builder $query) use ($data): void {
                            $query->search('name', $data['search']);
                        }
                    );
                });
            })
            ->when(in_array($data['column'], ['name', 'created_at', 'consumer_state']), function (Builder $query) use ($data): void {
                $query->orderBy($data['column'], $data['direction'])->orderBy('id');
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
            ->paginate($data['per_page']);
    }

    public function fetchByConsumer(Consumer $consumer): Collection
    {
        $consumer->loadMissing(['consumerNegotiation', 'scheduledTransactions']);

        return Group::query()
            ->with('campaigns.campaignTracker')
            ->where(function (Builder $query) use ($consumer) {
                $query->where('company_id', $consumer->company_id)
                    ->orWhereNull('company_id')
                    ->orWhere('company_id', 1);
            })
            ->where(function (Builder $query) use ($consumer): void {
                $query
                    ->when(
                        $consumer->status !== ConsumerStatus::DEACTIVATED,
                        function (Builder $query): void {
                            $query->where('consumer_state', GroupConsumerState::ALL_ACTIVE);
                        }
                    )
                    ->when(
                        in_array($consumer->status->value, ConsumerStatus::notVerified()),
                        function (Builder $query): void {
                            $query->orWhere('consumer_state', GroupConsumerState::NOT_VIEWED_OFFER);
                        }
                    )
                    ->when(
                        $consumer->status === ConsumerStatus::JOINED,
                        function (Builder $query): void {
                            $query->orWhere('consumer_state', GroupConsumerState::VIEWED_OFFER_BUT_NO_RESPONSE);
                        }
                    )
                    ->when(
                        $consumer->status === ConsumerStatus::PAYMENT_SETUP,
                        function (Builder $query): void {
                            $query->orWhere('consumer_state', GroupConsumerState::OPEN_NEGOTIATIONS);
                        }
                    )
                    ->when(
                        $consumer->status === ConsumerStatus::PAYMENT_SETUP && $consumer->counter_offer,
                        function (Builder $query): void {
                            $query->orWhere('consumer_state', GroupConsumerState::NOT_RESPONDED_TO_COUNTER_OFFER);
                        }
                    )
                    ->when(
                        $consumer->status === ConsumerStatus::PAYMENT_ACCEPTED && $consumer->payment_setup,
                        function (Builder $query) use ($consumer): void {
                            $query
                                ->when(
                                    $consumer->consumerNegotiation->negotiation_type === NegotiationType::PIF,
                                    function (Builder $query): void {
                                        $query->where('consumer_state', GroupConsumerState::NEGOTIATED_PAYOFF_BUT_PENDING_PAYMENT);
                                    }
                                )
                                ->when(
                                    $consumer->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT,
                                    function (Builder $query): void {
                                        $query->where('consumer_state', GroupConsumerState::NEGOTIATED_PLAN_BUT_PENDING_PAYMENT);
                                    }
                                )
                                ->when(
                                    $consumer->scheduledTransactions
                                        ->where('updated_at', '>=', now()->subDays(30))
                                        ->filter(fn (ScheduleTransaction $scheduleTransaction) => $scheduleTransaction->status !== TransactionStatus::SUCCESSFUL && ($scheduleTransaction->status === TransactionStatus::FAILED || $scheduleTransaction->previous_schedule_date !== null))
                                        ->isNotEmpty(),
                                    function (Builder $query): void {
                                        $query->where('consumer_state', GroupConsumerState::FAILED_OR_SKIP_MORE_THAN_TWO_PAYMENTS_CONSECUTIVELY);
                                    }
                                );
                        }
                    )
                    ->when(
                        $consumer->reason_id,
                        function (Builder $query): void {
                            $query->where('consumer_state', GroupConsumerState::REPORTED_NOT_PAYING);
                        }
                    )
                    ->when(
                        $consumer->disputed_at,
                        function (Builder $query): void {
                            $query->where('consumer_state', GroupConsumerState::DISPUTED);
                        }
                    )
                    ->when(
                        $consumer->status === ConsumerStatus::DEACTIVATED,
                        function (Builder $query): void {
                            $query->where('consumer_state', GroupConsumerState::DEACTIVATED);
                        }
                    );
            })
            ->get()
            ->filter(function (Group $group) use ($consumer): bool {
                if (blank($group->custom_rules)) {
                    return true;
                }

                $isValidGroups = [];

                foreach ($group->custom_rules as $customRule => $data) {
                    $isValidGroups[] = GroupCustomRules::tryFrom($customRule)->isValidConsumer($consumer, $data);
                }

                return ! in_array(false, $isValidGroups);
            })
            ->flatMap(fn (Group $group): Collection => $group
                ->campaigns
                ->filter(fn (Campaign $campaign): bool => $campaign->campaignTracker !== null)
                ->pluck('campaignTracker'))
            ->flatten();
    }

    public function fetchForCampaignSelectionBox(int $companyId): Collection
    {
        return Group::query()
            ->where('company_id', $companyId)
            ->pluck('name', 'id');
    }

    public function fetchTermsNameAndId(int $companyId): Collection
    {
        return Group::query()
            ->where('company_id', $companyId)
            ->whereNull('pif_balance_discount_percent')
            ->whereNull('ppa_balance_discount_percent')
            ->whereNull('min_monthly_pay_percent')
            ->whereNull('max_days_first_pay')
            ->selectRaw('id, CONCAT("group - ", name) as name_with_id')
            ->selectRaw('id, CONCAT("group_",id) as group_id')
            ->pluck('name_with_id', 'group_id');
    }

    public function fetchGroupTerms(int $groupId, int $companyId): ?Group
    {
        return Group::query()
            ->select(
                'id',
                'name',
                'pif_balance_discount_percent',
                'ppa_balance_discount_percent',
                'min_monthly_pay_percent',
                'max_days_first_pay',
                'minimum_settlement_percentage',
                'minimum_payment_plan_percentage',
                'max_first_pay_days'
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
            ->findOrFail($groupId);
    }

    public function updateTerms(int $groupId, array $data): void
    {
        Group::query()
            ->where('id', $groupId)
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
}

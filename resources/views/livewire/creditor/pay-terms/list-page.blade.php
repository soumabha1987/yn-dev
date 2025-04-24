@use('Illuminate\Support\Str')
@use('Illuminate\Support\Number')
@use('Illuminate\Support\Facades\Session')

<div>
    <div class="card">
        <div
            @class([
                'flex p-4 items-center gap-4',
                'justify-between' => $terms->isNotEmpty(),
                'justify-end' => $terms->isEmpty()
            ])
        >
            <x-table.per-page-count :items="$terms" />
            <a
                wire:navigate
                href="{{ route('creditor.pay-terms.create') }}"
                class="btn text-sm+ bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 flex items-center space-x-1"
            >
                <x-lucide-circle-plus class="size-5" />
                <span>{{ __('Create') }}</span>
            </a>
        </div>

        <div class="min-w-full overflow-auto">
            <x-table>
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th column="name" :$sortCol :$sortAsc>{{ __('Sub Account Name') }}</x-table.th>
                        <x-table.th column="sub_id" :$sortCol :$sortAsc>{{ __('Sub ID#') }}</x-table.th>
                        <x-table.th column="type" :$sortCol :$sortAsc>{{ __('Term Type') }}</x-table.th>
                        <x-table.th column="pif-discount" :$sortCol :$sortAsc>{{ __('Settlement Discount') }}</x-table.th>
                        <x-table.th column="ppa-discount" :$sortCol :$sortAsc>{{ __('PayPlan Bal. Discount') }}</x-table.th>
                        <x-table.th column="min-monthly-amount" :$sortCol :$sortAsc>{{ __('Min. Monthly Payment %') }}</x-table.th>
                        <x-table.th column="max-day" :$sortCol :$sortAsc>{{ __('Max. Days 1st Payment') }}</x-table.th>
                        <x-table.th column="min-settlement-percentage" :$sortCol :$sortAsc>{{ __('Min. Settlement Percentage %') }}</x-table.th>
                        <x-table.th column="min-payment-plan-percentage" :$sortCol :$sortAsc>{{ __('Min. Payment Plan Percentage %') }}</x-table.th>
                        <x-table.th column="max-first-pay-days" :$sortCol :$sortAsc>{{ __('Max. First pay Days') }}</x-table.th>
                        <x-table.th>{{ __('Actions') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse ($terms as $term)
                        <x-table.tr>
                            <x-table.td class="text-center">
                                {{ $term->terms_name }}
                            </x-table.td>
                            <x-table.td>
                                {{ $term->unique_identification_number ?? 'N/A' }}
                            </x-table.td>
                            <x-table.td class="text-center whitespace-nowrap">
                                {{ str($term->type)->title() }}
                            </x-table.td>
                            <x-table.td class="text-center">
                                {{ $term->pif_balance_discount_percent !== null ? Number::percentage($term->pif_balance_discount_percent) : 'N/A' }}
                            </x-table.td>
                            <x-table.td class="text-center">
                                {{ $term->ppa_balance_discount_percent !== null ? Number::percentage($term->ppa_balance_discount_percent) : 'N/A' }}
                            </x-table.td>
                            <x-table.td class="text-center">
                                {{ $term->min_monthly_pay_percent ? Number::percentage($term->min_monthly_pay_percent) : 'N/A' }}
                            </x-table.td>
                            <x-table.td class="text-center">
                                {{ $term->max_days_first_pay ? $term->max_days_first_pay . ' Days' : 'N/A' }}
                            </x-table.td>
                            <x-table.td class="text-center">
                                {{ $term->minimum_settlement_percentage ? Number::percentage($term->minimum_settlement_percentage) : 'N/A' }}
                            </x-table.td>
                            <x-table.td class="text-center">
                                {{ $term->minimum_payment_plan_percentage ? Number::percentage($term->minimum_payment_plan_percentage) : 'N/A' }}
                            </x-table.td>
                            <x-table.td class="text-center">
                                {{ $term->max_first_pay_days . ' Days' ?? 'N/A' }}
                            </x-table.td>
                            <x-table.td>
                                <div class="flex space-x-2 whitespace-nowrap">
                                    @php
                                        $payTermsType = match ($term->type) {
                                            'master' => 'master-terms',
                                            'group' => 'group-terms',
                                            'sub account' => 'sub-account-terms',
                                            default => null,
                                        };
                                    @endphp
                                    <a
                                        wire:navigate
                                        href="{{ route('creditor.pay-terms.edit', ['id' => $term->id, 'payTerms' => $payTermsType]) }}"
                                        class="text-xs sm:text-sm+ px-3 py-1.5 btn text-white bg-info hover:bg-info-focus focus:bg-info-focus active:bg-info-focus/90"
                                    >
                                        <div class="flex space-x-1 items-center">
                                            <x-heroicon-o-pencil-square class="size-4.5 sm:size-5" />
                                            <span>{{ __('Edit') }}</span>
                                        </div>
                                    </a>
                                    @if ($term->type === 'sub account' || $term->type === 'group')
                                        <x-confirm-box
                                            :message="__('Are you sure you want to remove negotiation terms?')"
                                            :ok-button-label="__('Delete')"
                                            action="removeTerm({{ $term->id }}, '{{ $term->type }}')"
                                        >
                                            <x-form.button
                                                type="button"
                                                variant="error"
                                                wire:loading.class="opacity-50"
                                                class="text-xs sm:text-sm+ px-3 py-1.5"
                                            >
                                                <div class="flex space-x-1 items-center">
                                                    <x-lucide-x-circle class="size-4.5 sm:size-5" />
                                                    <span>{{ __('Remove Terms') }}</span>
                                                </div>
                                            </x-form.button>
                                        </x-confirm-box>
                                    @endif
                                </div>
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="8" />
                    @endforelse
                </x-slot>
            </x-table>
        </div>
        <x-table.per-page :items="$terms" />
    </div>
</div>

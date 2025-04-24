@use('Illuminate\Support\Number')
@use('App\Enums\ConsumerStatus')
@use('App\Enums\NegotiationType')
@use('App\Enums\Role')

<div>
    <div class="card">
        <div
            @class([
                'flex flex-col sm:flex-row p-4 sm:items-center gap-4',
                'justify-between' => $consumers->isNotEmpty(),
                'justify-end' => $consumers->isEmpty()
            ])
        >
            <x-table.per-page-count :items="$consumers" />
            <div>
                <x-search-box
                    name="search"
                    wire:model.live.debounce.400="search"
                    placeholder="{{ __('Search') }}"
                    :description="__('You can search by its name and account number.')"
                />
            </div>
        </div>

        <div class="n min-w-full overflow-auto">
            <x-table>
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th column="consumer_name" :$sortCol :$sortAsc>{{ __('Consumer Name') }}</x-table.th>
                        <x-table.th column="member_account_number" :$sortCol :$sortAsc>{{ __('Account Number') }}</x-table.th>
                        @role(Role::CREDITOR)
                            <x-table.th column="sub_account" :$sortCol :$sortAsc>{{ __('Sub Account(s)') }}</x-table.th>
                        @endrole
                        <x-table.th column="current_balance" :$sortCol :$sortAsc>{{ __('Current Balance') }}</x-table.th>
                        <x-table.th column="profile_created_on" :$sortCol :$sortAsc>{{ __('Profile Created On') }}</x-table.th>
                        <x-table.th>{{ __('Actions') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse ($consumers as $consumer)
                        <x-table.tr>
                            <x-table.td>
                                <a
                                    wire:navigate
                                    class="hover:underline hover:underline-offset-4 text-primary"
                                    href="{{ route('manage-consumers.view', $consumer->id) }}"
                                >
                                    {{ $consumer->first_name . ' ' . $consumer->last_name }}
                                </a>
                            </x-table.td>
                            <x-table.td>
                                <a
                                    wire:navigate
                                    class="hover:underline hover:underline-offset-4 text-primary"
                                    href="{{ route('manage-consumers.view', $consumer->id) }}"
                                >
                                    {{ $consumer->member_account_number }}
                                </a>
                            </x-table.td>
                            @role(Role::CREDITOR)
                                <x-table.td>{{ $consumer->subclient_name ?? 'N/A' }}</x-table.td>
                            @endrole
                            <x-table.td>{{ Number::currency((float) $consumer->current_balance) }}</x-table.td>
                            <x-table.td>{{ $consumer->paymentProfile->updated_at->formatWithTimezone() }}</x-table.td>
                            <x-table.td>
                                <x-dialog>
                                    <x-dialog.open>
                                        <x-form.button
                                            type="button"
                                            variant="primary"
                                            class="text-xs sm:text-sm+"
                                        >
                                            <x-lucide-eye class="size-4.5 sm:size-5 mr-1"/>
                                            {{ __('View') }}
                                        </x-form.button>
                                    </x-dialog.open>

                                    <x-dialog.panel size="2xl">
                                        <x-slot name="heading">{{ $consumer->first_name . ' ' . $consumer->last_name }}</x-slot>
                                        <div class="border mb-4">
                                            <div class="flex items-center justify-between gap-2 py-2 px-3 text-sm+ font-semibold border-b">
                                                <span class="text-black">{{ __("Account Number") }}</span>
                                                <span class="text-primary text-end sm:text-left">{{ '#' . $consumer->account_number }}</span>
                                            </div>
                                            <div class="flex items-center justify-between gap-2 py-2 px-3 text-sm+ font-semibold border-b">
                                                <span class="text-black">{{ __("Consumer Name") }}</span>
                                                <span class="text-primary text-end sm:text-left">{{ $consumer->first_name . ' ' . $consumer->last_name }}</span>
                                            </div>
                                            <div class="flex items-center justify-between gap-2 py-2 px-3 text-sm+ font-semibold">
                                                <span class="text-black">{{ __("Account Balance") }}</span>
                                                <span class="text-primary text-end sm:text-left"> {{ Number::currency((float) ($consumer->current_balance ?? 0)) }}</span>
                                            </div>

                                            @if ($consumer->status === ConsumerStatus::PAYMENT_ACCEPTED && $consumer->consumerNegotiation)
                                                @if ($consumer->consumerNegotiation->offer_accepted)
                                                    @if ($consumer->consumerNegotiation->negotiation_type === NegotiationType::PIF)
                                                        <div class="flex items-center justify-between gap-2 py-2 px-3 text-sm+ font-semibold border-y">
                                                            <span class="text-black">{{ __('Negotiation Term') }}</span>
                                                            <span class="text-primary text-end sm:text-left">{{ __('Discounted Pay In Full Plan') }}</span>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-2 py-2 px-3 text-sm+ font-semibold border-b">
                                                            <span class="text-black">{{ __('Approved By') }}</span>
                                                            <span class="text-primary text-end sm:text-left">{{ __('Auto Approve') }}</span>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-2 py-2 px-3 text-sm+ font-semibold border-b">
                                                            <span class="text-black">{{ __('Settlement For') }}</span>
                                                            <span class="text-primary text-end sm:text-left">
                                                                {{ Number::currency((float) ($consumer->consumerNegotiation->one_time_settlement ?? 0)) }}
                                                            </span>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-2 py-2 px-3 text-sm+ font-semibold border-b">
                                                            <span class="text-black">{{ __('Negotiation Completed On') }}</span>
                                                            <span class="text-primary text-end sm:text-left">
                                                                {{ $consumer->consumerNegotiation->updated_at->formatWithTimezone() }}
                                                            </span>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-2 py-2 px-3 text-sm+ font-semibold">
                                                            <span class="text-black">{{ __('First Payment Date') }}</span>
                                                            <span class="text-primary text-end sm:text-left">
                                                                {{ $consumer->consumerNegotiation->first_pay_date->format('M d, Y') }}
                                                            </span>
                                                        </div>
                                                    @elseif ($consumer->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT)
                                                        <div class="flex items-center justify-between gap-2 py-2 px-3 text-sm+ font-semibold border-y">
                                                            <span class="text-black">{{ __('Negotiation Term') }}</span>
                                                            <span class="text-primary text-end sm:text-left">{{ __('Discounted Payment Plan') }}</span>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-2 py-2 px-3 text-sm+ font-semibold border-b">
                                                            <span class="text-black">{{ __('Accepted By') }}</span>
                                                            <span class="text-primary text-end sm:text-left">{{ __('Auto Approve') }}</span>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-2 py-2 px-3 text-sm+ font-semibold border-b">
                                                            <span class="text-black">{{ __('Plan') }}</span>
                                                            @if ($consumer->consumerNegotiation->last_month_amount)
                                                                <span class="text-primary text-end sm:text-left">
                                                                    {{ __(':installments Payments Of :amount and 1 last payment of :last_installment_amount', [
                                                                    'installments' => $consumer->consumerNegotiation->no_of_installments,
                                                                    'amount' => Number::currency((float) ($consumer->consumerNegotiation->monthly_amount ?? 0)),
                                                                    'last_installment_amount' => Number::currency((float) ($consumer->consumerNegotiation->last_month_amount ?? 0)),
                                                                ]) }}
                                                                </span>
                                                            @else
                                                                <span class="text-primary text-end sm:text-left">
                                                                    {{ __(':installments Payments Of :amount', [
                                                                        'installments' => $consumer->consumerNegotiation->no_of_installments,
                                                                        'amount' => Number::currency((float) ($consumer->consumerNegotiation->monthly_amount ?? 0)),
                                                                    ]) }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <div class="flex items-center justify-between gap-2 py-2 px-3 text-sm+ font-semibold border-b">
                                                            <span class="text-black">{{ __('Negotiation Completed On') }}</span>
                                                            <span class="text-primary text-end sm:text-left">
                                                                {{ $consumer->consumerNegotiation->updated_at->formatWithTimezone() }}
                                                            </span>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-2 py-2 px-3 text-sm+ font-semibold">
                                                            <span class="text-black">{{ __('First Payment Date') }}</span>
                                                            <span class="text-primary text-end sm:text-left">
                                                                {{ $consumer->consumerNegotiation->first_pay_date->format('M d, Y') }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                @elseif ($consumer->consumerNegotiation->counter_offer_accepted)
                                                    @if ($consumer->consumerNegotiation->negotiation_type === NegotiationType::PIF)
                                                        <div class="flex items-center justify-between gap-2 py-2 px-3 text-sm+ font-semibold border-y">
                                                            <span class="text-black">{{ __('Negotiation Term') }}</span>
                                                            <span class="text-primary text-end sm:text-left">{{ __('Discounted Counter PayOff Plan') }}</span>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-2 py-2 px-3 text-sm+ font-semibold border-b">
                                                            <span class="text-black">{{ __('Approved By') }}</span>
                                                            <span class="text-primary text-end sm:text-left">
                                                                {{ $consumer->first_name . ' ' . $consumer->last_name }}
                                                            </span>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-2 py-2 px-3 text-sm+ font-semibold border-b">
                                                            <span class="text-black">{{ __('Settlement For') }}</span>
                                                            <span class="text-primary text-end sm:text-left">
                                                                {{ Number::currency((float) ($consumer->consumerNegotiation->counter_one_time_amount ?? 0)) }}
                                                            </span>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-2 py-2 px-3 text-sm+ font-semibold border-b">
                                                            <span class="text-black">{{ __('Negotiation Completed On') }}</span>
                                                            <span class="text-primary text-end sm:text-left">
                                                                {{ $consumer->consumerNegotiation->updated_at->formatWithTimezone() }}
                                                            </span>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-2 py-2 px-3 text-sm+ font-semibold">
                                                            <span class="text-black">{{ __('First Payment Date') }}</span>
                                                            <span class="text-primary text-end sm:text-left">
                                                                {{ $consumer->consumerNegotiation->counter_first_pay_date->format('M d, Y') }}
                                                            </span>
                                                        </div>
                                                    @elseif ($consumer->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT)
                                                        <div class="flex items-center justify-between gap-2 py-2 px-3 text-sm+ font-semibold border-y">
                                                            <span class="text-black">{{ __('Negotiation Term') }}</span>
                                                            <span class="text-primary text-end sm:text-left">{{ __('Discounted Counter Payment Plan') }}</span>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-2 py-2 px-3 text-sm+ font-semibold border-b">
                                                            <span class="text-black">{{ __('Approved By') }}</span>
                                                            <span class="text-primary text-end sm:text-left">
                                                                {{ $consumer->first_name . ' ' . $consumer->last_name }}
                                                            </span>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-2 py-2 px-3 text-sm+ font-semibold border-b">
                                                            <span class="text-black">{{ __('Plan') }}</span>
                                                            @if ($consumer->consumerNegotiation->last_month_amount)
                                                                <span class="text-primary text-end sm:text-left">
                                                                    {{ __(':installments Payments Of :amount and 1 last payment of :last_installment_amount', [
                                                                        'installments' => $consumer->consumerNegotiation->no_of_installments,
                                                                        'amount' => Number::currency((float) ($consumer->consumerNegotiation->monthly_amount ?? 0)),
                                                                        'last_installment_amount' => Number::currency((float) ($consumer->consumerNegotiation->last_month_amount ?? 0)),
                                                                    ]) }}
                                                                </span>
                                                            @else
                                                                <span class="text-primary text-end sm:text-left">
                                                                    {{ __(':installments Payments Of :amount', [
                                                                        'installments' => $consumer->consumerNegotiation->no_of_installments,
                                                                        'amount' => Number::currency((float) ($consumer->consumerNegotiation->monthly_amount ?? 0)),
                                                                    ]) }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <div class="flex items-center justify-between gap-2 py-2 px-3 text-sm+ font-semibold border-b">
                                                            <span class="text-black">{{ __('Negotiation Completed On') }}</span>
                                                            <span class="text-primary text-end sm:text-left">
                                                                {{ $consumer->consumerNegotiation->updated_at->formatWithTimezone() }}
                                                            </span>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-2 py-2 px-3 text-sm+ font-semibold">
                                                            <span class="text-black">{{ __('First Payment Date') }}</span>
                                                            <span class="text-primary text-end sm:text-left">
                                                                {{ $consumer->consumerNegotiation->counter_first_pay_date->format('M d, Y') }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                @endif
                                            @endif
                                        </div>
                                    </x-dialog.panel>
                                </x-dialog>
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="7" />
                    @endforelse
                </x-slot>
            </x-table>
        </div>
        <x-table.per-page :items="$consumers" />
    </div>
</div>

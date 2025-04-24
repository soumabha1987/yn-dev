@use('Illuminate\Support\Js')
@use('Illuminate\Support\Number')
@use('App\Enums\ConsumerStatus')
@use('App\Enums\NegotiationType')
@use('App\Enums\Role')

<div>
    <div class="card">
        <div
            x-on:refresh-global-search.window="$wire.set('search', '')"
            @class([
                'flex flex-col sm:flex-row p-4 sm:items-center gap-4',
                'justify-between' => $consumers->isNotEmpty(),
                'justify-end' => $consumers->isEmpty()
            ])
        >
            <x-table.per-page-count :items="$consumers" />
            <div class="flex flex-col sm:flex-row sm:items-center justify-end gap-3">
                <div>
                    <x-form.select
                        wire:model.live="status"
                        :placeholder="__('Consumer Status')"
                        :options="$statuses"
                        name="status"
                        class="!mt-0"
                    />
                </div>
                @if ($isSuperAdmin)
                    <div>
                        <x-form.select
                            wire:model.live="company"
                            :placeholder="__('Companies')"
                            :options="$companies"
                            name="company"
                            class="!mt-0"
                        />
                    </div>
                @endif

                @if (($isSuperAdmin && $company) || auth()->user()->hasRole(Role::CREDITOR))
                    @if (count($subclients) > 0)
                        <div>
                            <x-form.select
                                wire:model.live="subclient"
                                :placeholder="__('subclient')"
                                :options="$subclients"
                                name="subclient"
                                class="!mt-0"
                            />
                        </div>
                    @endif
                @endif
                @if ($consumers->isNotEmpty())
                    <div>
                        <x-form.button
                            wire:click="export"
                            wire:target="export"
                            wire:loading.attr="disabled"
                            type="button"
                            variant="primary"
                            class="flex items-center space-x-2 mt-0 disabled:opactiy-50"
                        >
                            <span>{{ __('Export') }}</span>
                            <x-lucide-download class="size-5" wire:loading.remove wire:target="export" />
                            <x-lucide-loader-2 class="animate-spin size-5" wire:loading wire:target="export" />
                        </x-form.button>
                    </div>
                @endif
            </div>
        </div>

        <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
            <x-table>
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th column="master_account_number" :$sortCol :$sortAsc>{{ __('Master Account Number') }}</x-table.th>
                        <x-table.th column="name" :$sortCol :$sortAsc>{{ __('Consumer Name') }}</x-table.th>
                        <x-table.th column="account_name" :$sortCol :$sortAsc>{{ __('Account Name') }}</x-table.th>
                        @if ($isSuperAdmin)
                            <x-table.th column="company_name" :$sortCol :$sortAsc>{{ __('Company Name') }}</x-table.th>
                        @endif
                        <x-table.th column="sub_name" :$sortCol :$sortAsc>{{ __('sub Name/ID') }}</x-table.th>
                        <x-table.th column="placement_date" :$sortCol :$sortAsc>{{ __('Placement Date') }}</x-table.th>
                        <x-table.th column="account_status" :$sortCol :$sortAsc>{{ __('Account Status') }}</x-table.th>
                        @if ($isSuperAdmin)
                            <x-table.th colspan="2" column="status" :$sortCol :$sortAsc>{{ __('Status') }}</x-table.th>
                        @else
                            <x-table.th column="status" :$sortCol :$sortAsc>{{ __('Status') }}</x-table.th>
                        @endif
                        <x-table.th>{{ __('Actions') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse ($consumers as $consumer)
                        <x-table.tr>
                            <x-table.td>
                                <a
                                    wire:navigate
                                    href="{{ route('manage-consumers.view', $consumer->id) }}"
                                    class="cursor-pointer hover:underline text-primary hover:text-primary-focus whitespace-nowrap"
                                >
                                    {{ $consumer->member_account_number ?? 'N/A' }}
                                </a>
                            </x-table.td>
                            <x-table.td x-data="{ showTooltip: false }">
                                <a
                                    wire:navigate
                                    href="{{ route('manage-consumers.view', $consumer->id) }}"
                                    class="cursor-pointer hover:underline text-primary hover:text-primary-focus whitespace-nowrap"
                                    x-on:mouseover="showTooltip = true"
                                    x-on:mouseleave="showTooltip = false"
                                >
                                    {{ $consumer->first_name . ' ' . $consumer->last_name }}
                                    <div
                                        x-show="showTooltip"
                                        x-transition:enter="transition-transform duration-200 ease-out absolute origin-top"
                                        x-transition:enter-start="scale-75"
                                        x-transition:enter-end="scale-100 static"
                                        class="z-10 flex rounded space-x-3 absolute bg-slate-200 p-2 mt-2 transition-opacity duration-300 ease-in-out"
                                    >
                                        <ul class="list-none uppercase">
                                            <li>{{ __('Last Name') }}</li>
                                            <li>{{ __('Date of Birth') }}</li>
                                            <li>{{ __('SSN') }}</li>
                                            <li>{{ __('Current Balance') }}</li>
                                        </ul>
                                        <ul class="list-none font-bold">
                                            <li>{{ $consumer->last_name }}</li>
                                            <li>{{ $consumer->dob->format('M d, Y') }}</li>
                                            <li>{{ $consumer->last4ssn }}</li>
                                            <li>{{ Number::currency((float) $consumer->current_balance) }}</li>
                                        </ul>
                                    </div>
                                </a>
                            </x-table.td>
                            <x-table.td>{{ $consumer->original_account_name ? str($consumer->original_account_name)->title() : 'N/A' }}</x-table.td>
                            @if ($isSuperAdmin)
                                <x-table.td>
                                    <span
                                        class="hover:underline hover:underline-offset-2"
                                        x-tooltip.placement.right="@js($consumer->company?->company_name)"
                                    >
                                        {{ str($consumer->company?->company_name)->words(2)->toString() }}
                                    </span>
                                </x-table.td>
                            @endif
                            <x-table.td>{{ $consumer->subclient_name ? str($consumer->subclient_name. '/' . $consumer->subclient_account_number)->title()  : 'N/A' }}</x-table.td>
                            <x-table.td>{{ $consumer->placement_date ? $consumer->placement_date->formatWithTimezone() : 'N/A' }}</x-table.td>
                            <x-table.td>
                                <div class="flex items-center gap-x-1">
                                    <span
                                        @class([
                                            'badge',
                                            'bg-error/10 text-error hover:bg-error/20 focus:bg-error/20' => in_array($consumer->status, [ConsumerStatus::DEACTIVATED, ConsumerStatus::DISPUTE, ConsumerStatus::NOT_PAYING]),
                                            'bg-primary/10 text-primary hover:bg-primary/20 focus:bg-primary/20' => ! in_array($consumer->status, [ConsumerStatus::DEACTIVATED, ConsumerStatus::DISPUTE, ConsumerStatus::NOT_PAYING])
                                        ])
                                    >
                                        {{ in_array($consumer->status, [ConsumerStatus::DEACTIVATED, ConsumerStatus::DISPUTE, ConsumerStatus::NOT_PAYING]) ? __('Removed') : __('Active') }}
                                    </span>
                                </div>
                            </x-table.td>
                            <x-table.td>
                                <div class="flex items-center gap-x-1">
                                    <span
                                        @class([
                                            'badge whitespace-nowrap',
                                            'bg-secondary/10 text-secondary hover:bg-secondary/20 focus:bg-secondary/20' => $consumer->status === ConsumerStatus::JOINED,
                                            'bg-error/10 text-error hover:bg-error/20 focus:bg-error/20' => $consumer->status === ConsumerStatus::DEACTIVATED,
                                            'bg-info/10 text-info hover:bg-info/20 focus:bg-info/20' => $consumer->status === ConsumerStatus::PAYMENT_ACCEPTED && ! $consumer->payment_setup,
                                            'bg-warning/10 text-warning hover:bg-warning/20 focus:bg-warning/20' => $consumer->status === ConsumerStatus::PAYMENT_SETUP,
                                            'bg-primary/10 text-primary hover:bg-primary/20 focus:bg-primary/20' => $consumer->status === ConsumerStatus::RENEGOTIATE,
                                            'bg-indigo-600/10 text-indigo-600' => $consumer->status === ConsumerStatus::PAYMENT_DECLINED,
                                            'bg-rose-500/10 text-rose-500 hover:bg-rose-500/20 focus:bg-rose-500/20' => in_array($consumer->status, [ConsumerStatus::NOT_PAYING, ConsumerStatus::DISPUTE, ConsumerStatus::HOLD]),
                                            'bg-accent/10 text-accent hover:bg-accent/20 focus:bg-accent/20' => $consumer->status === ConsumerStatus::SETTLED,
                                            'bg-success/10 text-success hover:bg-success/20 focus:bg-success/20' => $consumer->status === ConsumerStatus::UPLOADED,
                                            'bg-accent-light/10 text-accent-light hover:bg-accent-light/20 focus:bg-accent-light/20' => $consumer->status === ConsumerStatus::PAYMENT_ACCEPTED && $consumer->payment_setup,
                                            'bg-slate-200 text-slate-300' => $consumer->status === ConsumerStatus::NOT_VERIFIED,
                                        ])
                                    >
                                        @php
                                            $status = match(true) {
                                                $consumer->status !== ConsumerStatus::PAYMENT_ACCEPTED => $statuses[$consumer->status->value] ?? 'N/A',
                                                $consumer->payment_setup => __('Active Payment Plan'),
                                                $consumer->consumerNegotiation->negotiation_type === NegotiationType::PIF => __('Agreed Settlement/Pending Payment'),
                                                $consumer->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT => __('Agreed Payment Plan/Pending Payment'),
                                            }
                                        @endphp
                                        {{ $status }}
                                    </span>
                                    @if ($consumer->status === ConsumerStatus::HOLD)
                                        <x-lucide-info
                                            class="size-5 lg:ml-2"
                                            x-tooltip.placement.bottom.error="{{ Js::from($consumer->hold_reason) }}"
                                        />
                                    @endif
                                </div>
                                @if ($consumer->reason_id)
                                    <div class="text-xs font-semibold text-error">
                                        {{ $consumer->reason->label }}
                                    </div>
                                @endif
                            </x-table.td>
                            @role(Role::SUPERADMIN)
                                <x-table.td>
                                    <a
                                        href="{{ $consumer->invitation_link }}"
                                        class="text-purple-600"
                                        target="_blank"
                                    >
                                        <x-heroicon-s-arrow-top-right-on-square class="size-5" />
                                    </a>
                                </x-table.td>
                            @endrole
                            <x-table.td>
                                <a
                                    wire:navigate
                                    href="{{ route('manage-consumers.view', $consumer->id) }}"
                                    class="text-xs sm:text-sm+ whitespace-nowrap px-2 sm:px-3 py-1.5 btn text-white bg-success hover:bg-success-focus focus:bg-success-focus active:bg-success-focus/90"
                                >
                                    <div class="flex space-x-1 items-center">
                                        <x-lucide-eye class="size-4.5 sm:size-5"/>
                                        <span>{{ __('View Profile') }}</span>
                                    </div>
                                </a>
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="$isSuperAdmin ? 9 : 8" />
                    @endforelse
                </x-slot>
            </x-table>
        </div>
        <x-table.per-page :items="$consumers" />
    </div>
</div>

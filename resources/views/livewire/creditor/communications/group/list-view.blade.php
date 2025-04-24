@use('App\Enums\GroupCustomRules')
@use('Illuminate\Support\Number')
@use('App\Enums\Role')

<div>
    <div
        class="card mt-8"
        x-on:refresh-parent.window="$wire.$refresh"
    >
        <div
            @class([
                'flex flex-col sm:flex-row p-4 sm:items-center gap-4',
                'justify-between' => $groups->isNotEmpty(),
                'justify-end' => $groups->isEmpty(),
            ])
        >
            <x-table.per-page-count :items="$groups" />
            <div class="sm:flex items-center space-x-3">
                <x-search-box
                    wire:model.live="search"
                    :placeholder="__('Search')"
                    :description="__('You can search by name and created by')"
                />
            </div>
        </div>
        <div class="min-w-full overflow-auto">
            <x-table>
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th column="created-on" :$sortCol :$sortAsc>{{ __('Created On') }}</x-table.th>
                        <x-table.th column="name" :$sortCol :$sortAsc>{{ __('Group Name') }}</x-table.th>
                        <x-table.th column="consumer-status" :$sortCol :$sortAsc>{{ __('Consumer Status') }}</x-table.th>
                        <x-table.th>{{ __('Custom Rules') }}</x-table.th>
                        @role(Role::CREDITOR)
                            <x-table.th column="pay-terms" :$sortCol :$sortAsc>{{ __('Pay Terms') }}</x-table.th>
                        @endrole
                        <x-table.th>{{ __('Actions') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse ($groups as $group)
                        <x-table.tr>
                            <x-table.td>{{ $group->created_at->formatWithTimezone() }}</x-table.td>
                            <x-table.td>{{ $group->name }}</x-table.td>
                            <x-table.td>{{ $group->consumer_state->displayName() }}</x-table.td>
                            <x-table.td>
                                @if ($group->custom_rules)
                                    @foreach ($group->custom_rules as $customRuleKey => $customRuleValues)
                                        <span
                                            class="hover:cursor-pointer hover:underline"
                                            x-tooltip.placement.right="@js(GroupCustomRules::tryFrom($customRuleKey)->displayValuesString($customRuleValues))"
                                        >
                                            {{ GroupCustomRules::tryFrom($customRuleKey)->displayName() }}
                                        </span> <br>
                                    @endforeach
                                @else
                                    {{ __('N/A') }}
                                @endif
                            </x-table.td>
                            @role(Role::CREDITOR)
                                <x-table.td x-data="{ showTooltip: false }">
                                    @if (
                                        $group->pif_balance_discount_percent !== null ||
                                        $group->ppa_balance_discount_percent !== null ||
                                        $group->min_monthly_pay_percent !== null ||
                                        $group->max_days_first_pay !== null ||
                                        $group->minimum_settlement_percentage !== null ||
                                        $group->minimum_payment_plan_percentage !== null ||
                                        $group->max_first_pay_days
                                    )
                                        <span
                                            x-on:mouseover="showTooltip = true"
                                            x-on:mouseleave="showTooltip = false"
                                        >
                                            <x-lucide-check class="text-success size-5"/>
                                            <div
                                                x-show="showTooltip"
                                                x-clock
                                                x-transition:enter="transition-transform duration-200 ease-out absolute origin-top"
                                                x-transition:enter-start="scale-75"
                                                x-transition:enter-end="scale-100 static"
                                                class="text-primary z-10 flex rounded space-x-3 absolute bg-slate-200 p-2 mt-2 transition-opacity duration-300 ease-in-out"
                                            >
                                                <ul class="list-none">
                                                    <li>{{ __('Settlement Balance Discount:') }}</li>
                                                    <li>{{ __('Payment Plan Balance Discount:') }}</li>
                                                    <li>{{ __('Min. Monthly Payment:') }}</li>
                                                    <li>{{ __('Max Days First Pay:') }}</li>
                                                    <li>{{ __('Minimum Settlement Percentage:') }}</li>
                                                    <li>{{ __('Minimum Payment Plan Percentage:') }}</li>
                                                    <li>{{ __('Max First Payment Date:') }}</li>
                                                </ul>
                                                <ul class="list-none font-bold">
                                                    <li>
                                                        {{ $group->pif_balance_discount_percent !== null ? Number::percentage($group->pif_balance_discount_percent) : 'N/A' }}
                                                    </li>
                                                    <li>
                                                        {{ $group->ppa_balance_discount_percent !== null ? Number::percentage($group->ppa_balance_discount_percent) : 'N/A' }}
                                                    </li>
                                                    <li>
                                                        {{ $group->min_monthly_pay_percent !== null ? Number::percentage($group->min_monthly_pay_percent) : 'N/A' }}
                                                    </li>
                                                    <li>
                                                        {{ $group->max_days_first_pay ?? 'N/A' }}
                                                    </li>
                                                    <li>
                                                        {{ $group->minimum_settlement_percentage ?? 'N/A' }}
                                                    </li>
                                                    <li>
                                                        {{ $group->minimum_payment_plan_percentage ?? 'N/A' }}
                                                    </li>
                                                    <li>
                                                        {{ $group->max_first_pay_days ?? 'N/A' }}
                                                    </li>
                                                </ul>
                                            </div>
                                        </span>
                                    @else
                                        <span>
                                            <x-lucide-x class="text-error size-5"/>
                                        </span>
                                    @endif
                                </x-table.td>
                            @endrole
                            <x-table.td class="flex items-center">
                                <x-menu>
                                    <x-menu.button
                                        class="hover:bg-slate-100 p-1 rounded-full"
                                        x-on:close-menu.window="menuOpen = false"
                                    >
                                        <x-heroicon-m-ellipsis-horizontal class="size-7" />
                                    </x-menu.button>
                                    <x-menu.items>
                                        <x-menu.item
                                            wire:click="$parent.edit({{ $group->id }})"
                                            wire:target="$parent.edit({{ $group->id }})"
                                            wire:loading.attr="disabled"
                                            class="flex items-center gap-3"
                                        >
                                            <x-lucide-loader-2
                                                wire:loading
                                                wire:target="$parent.edit({{ $group->id }})"
                                                class="size-5 animate-spin"
                                            />
                                            <x-heroicon-o-pencil-square
                                                wire:loading.remove
                                                wire:target="$parent.edit({{ $group->id }})"
                                                class="size-5"
                                            />
                                            <span>{{ __('Edit') }}</span>
                                        </x-menu.item>

                                        <x-menu.item
                                            wire:click="export({{ $group->id }})"
                                            wire:target="export({{ $group->id }})"
                                            wire:loading.attr="disabled"
                                            class="flex items-center gap-3"
                                        >
                                            <x-lucide-loader-2
                                                wire:loading
                                                wire:target="export({{ $group->id }})"
                                                class="size-5 animate-spin"
                                            />
                                            <x-heroicon-m-arrow-down-tray
                                                wire:loading.remove
                                                wire:target="export({{ $group->id }})"
                                                class="size-5"
                                            />
                                            <span>{{ __('Export') }}</span>
                                        </x-menu.item>

                                        <x-confirm-box
                                            :message="__('Are you sure you want to delete this group?')"
                                            :ok-button-label="__('Delete')"
                                            action="delete({{ $group->id }})"
                                        >
                                            <x-menu.close>
                                                <x-menu.item>
                                                    <x-heroicon-o-trash class="size-5" />
                                                    <span>{{ __('Delete') }}</span>
                                                </x-menu.item>
                                            </x-menu.close>
                                        </x-confirm-box>
                                        @role(Role::CREDITOR)
                                            <livewire:creditor.consumer-pay-terms.update-page
                                                :record="$group"
                                                :isMenuItem="true"
                                                :key="str()->random(10)"
                                            />
                                        @endrole

                                        <x-menu.item
                                            wire:click="calculateGroupSize({{ $group->id }})"
                                            wire:target="calculateGroupSize({{ $group->id }})"
                                            wire:loading.attr="disabled"
                                        >
                                            <x-heroicon-o-eye class="size-5" />
                                            <span>{{ __("Preview Group Size") }}</span>
                                        </x-menu.item>
                                    </x-menu.items>
                                </x-menu>
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="5"/>
                    @endforelse
                </x-slot>
            </x-table>
        </div>
        <x-table.per-page :items="$groups"/>

        <x-communications.group-size
            wire:model="openModal"
            :$groupSize
            :$totalBalance
        />
    </div>
</div>

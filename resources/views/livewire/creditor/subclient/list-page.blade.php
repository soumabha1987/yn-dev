@use('App\Enums\Role')
@use('Illuminate\Support\Number')

<div>
    <div class="card">
        <div
            x-on:refresh-parent.window="$wire.$refresh"
            @class([
                'flex flex-col sm:flex-row p-4 sm:items-center gap-4',
                'justify-between' => $subclients->isNotEmpty(),
                'justify-end' => $subclients->isEmpty()
            ])
        >
            <x-table.per-page-count :items="$subclients" />
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center w-full sm:w-auto gap-3">
                <template x-if="! visible">
                    <x-dialog wire:model="dialogOpen">
                        <x-form.button
                            type="button"
                            variant="primary"
                            class="text-sm+ disabled:opacity-50"
                            wire:click="formReset"
                            wire:target="formReset"
                            wire:loading.attr="disabled"
                        >
                            <div class="flex space-x-1 items-center">
                                <x-lucide-circle-plus
                                    wire:loading.remove
                                    wire:target="formReset"
                                    class="size-5"
                                />
                                <x-lucide-loader-2
                                    wire:loading
                                    wire:target="formReset"
                                    class="size-5 animate-spin"
                                />
                                <span>{{ __('Create') }}</span>
                            </div>
                        </x-form.button>
                        <x-dialog.panel
                            :heading="$form->subclient_name ? __('Update Sub Account') : __('Create Sub Account')"
                            size="2xl"
                        >
                            <form wire:submit="createOrUpdate" autocomplete="off">
                                <div>
                                    <x-form.input-field
                                        wire:model="form.subclient_name"
                                        :label="__('Name')"
                                        type="text"
                                        name="form.subclient_name"
                                        class="w-full"
                                        :placeholder="__('Enter up to 160 characters')"
                                        required
                                    />
                                </div>
                                <div class="mt-2">
                                    <x-form.input-field
                                        wire:model="form.unique_identification_number"
                                        :label="__('Unique ID#')"
                                        type="text"
                                        name="form.unique_identification_number"
                                        class="w-full"
                                        :placeholder="__('Enter up to 160 characters')"
                                        required
                                    />
                                </div>
                                @role(Role::SUPERADMIN)
                                    <div class="mt-2">
                                        <x-form.select
                                            wire:model="form.company_id"
                                            :label="__('Company')"
                                            name="form.company_id"
                                            :options="$companies"
                                            class="w-full"
                                            :placeholder="__('Company')"
                                            required
                                        />
                                    </div>
                                @endrole

                                <div class="space-x-2 text-right mt-5">
                                    <x-dialog.close>
                                        <x-form.default-button type="button">
                                            {{ __('Close') }}
                                        </x-form.default-button>
                                    </x-dialog.close>
                                    <x-form.button
                                        variant="primary"
                                        type="submit"
                                        wire:loading.attr="disabled"
                                        wire:target="createOrUpdate"
                                        class="border focus:border-primary-focus disabled:opacity-50"
                                    >
                                        <x-lucide-loader-2
                                            wire:loading
                                            wire:target="createOrUpdate"
                                            class="size-5 animate-spin mr-2"
                                        />
                                        {{ $form->subclient_name ? __('Update') : __('Save') }}
                                    </x-form.button>
                                </div>
                            </form>
                        </x-dialog.panel>
                    </x-dialog>
                </template>
                <div>
                    <x-search-box
                        wire:model.live.debounce.400="search"
                        placeholder="{{ __('Search') }}"
                        :description="__('You can search by name')"
                    />
                </div>
            </div>
        </div>

        <div class="min-w-full overflow-auto">
            <x-table>
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th column="created_on" :$sortCol :$sortAsc class="lg:w-36">{{ __('Created On') }}</x-table.th>
                        <x-table.th column="name" :$sortCol :$sortAsc>{{ __('Name') }}</x-table.th>
                        <x-table.th column="unique_identification_number" :$sortCol :$sortAsc>{{ __('Unique ID') }}</x-table.th>
                        @role(Role::SUPERADMIN)
                            <x-table.th column="company_name" :$sortCol :$sortAsc>{{ __('Company') }}</x-table.th>
                        @endrole
                        <x-table.th column="pay_terms" :$sortCol :$sortAsc>{{ __('Pay Terms') }}</x-table.th>
                        <x-table.th class="lg:w-1/12">{{ __('Actions') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse ($subclients as $subclient)
                        <x-table.tr wire:key="{{ str()->random(10) }}">
                            <x-table.td>{{ $subclient->created_at->formatWithTimezone() }}</x-table.td>
                            <x-table.td class="uppercase">
                                {{ $subclient->subclient_name }}
                            </x-table.td>
                            <x-table.td>{{ $subclient->unique_identification_number }}</x-table.td>
                            @role(Role::SUPERADMIN)
                                <x-table.td>{{ $subclient->company->company_name }}</x-table.td>
                            @endrole
                            <x-table.td x-data="{ showTooltip: false }">
                                @if (
                                    $subclient->pif_balance_discount_percent !== null ||
                                    $subclient->ppa_balance_discount_percent !== null ||
                                    $subclient->min_monthly_pay_percent !== null ||
                                    $subclient->max_days_first_pay !== null ||
                                    $subclient->minimum_settlement_percentage !== null ||
                                    $subclient->minimum_payment_plan_percentage !== null ||
                                    $subclient->max_first_pay_days
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
                                                <li>{{ __('Min. Settlement Percentage') }}</li>
                                                <li>{{ __('Min. Payment Plan Percentage') }}</li>
                                                <li>{{ __('Max First Pay Days') }}</li>
                                            </ul>
                                            <ul class="list-none font-bold">
                                                <li>
                                                    {{ $subclient->pif_balance_discount_percent !== null ? Number::percentage($subclient->pif_balance_discount_percent) : 'N/A' }}
                                                </li>
                                                <li>
                                                    {{ $subclient->ppa_balance_discount_percent !== null ? Number::percentage($subclient->ppa_balance_discount_percent) : 'N/A' }}
                                                </li>
                                                <li>
                                                    {{ $subclient->min_monthly_pay_percent !== null ? Number::percentage($subclient->min_monthly_pay_percent) : 'N/A' }}
                                                </li>
                                                <li>
                                                    {{ $subclient->max_days_first_pay ?? 'N/A' }}
                                                </li>
                                                <li>
                                                    {{ $subclient->minimum_settlement_percentage ?? 'N/A' }}
                                                </li>
                                                <li>
                                                    {{ $subclient->minimum_payment_plan_percentage ?? 'N/A' }}
                                                </li>
                                                <li>
                                                    {{ $subclient->max_first_pay_days ?? 'N/A' }}
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
                            <x-table.td>
                                <div class="flex items-center">
                                    @role(Role::CREDITOR)
                                        <div class="mr-2">
                                            <livewire:creditor.consumer-pay-terms.update-page
                                                :record="$subclient"
                                                :key="str()->random(10)"
                                            />
                                        </div>
                                    @endrole
                                    <div class="mr-2">
                                        <x-form.button
                                            x-on:click="$wire.dialogOpen = true, isCreateHeading = false"
                                            type="button"
                                            class="text-xs sm:text-sm+ px-2 sm:px-3 py-1.5"
                                            variant="info"
                                            wire:click="edit({{ $subclient->id }})"
                                        >
                                            <div class="flex space-x-1 items-center">
                                                <x-heroicon-o-pencil-square class="size-4.5 sm:size-5"/>
                                                <span>{{ __('Edit') }}</span>
                                            </div>
                                        </x-form.button>
                                    </div>
                                    <x-confirm-box
                                        :ok-button-label="__('Delete')"
                                        action="delete({{ $subclient->id }})"
                                        wire:target="delete({{ $subclient->id }})"
                                    >
                                        <x-slot name="message">
                                            {{ __('Are you sure you want to delete this subclient?') }}
                                        </x-slot>
                                        <x-form.button
                                            type="button"
                                            variant="error"
                                            class="text-xs sm:text-sm+ px-2 sm:px-3 py-1.5 disabled:opacity-50"
                                        >
                                            <x-heroicon-o-trash class="size-5 mr-1"/>
                                            <span>{{ __('Delete') }}</span>
                                        </x-form.button>
                                    </x-confirm-box>
                                </div>
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="7" />
                    @endforelse
                </x-slot>
            </x-table>
        </div>
        <x-table.per-page :items="$subclients"/>
    </div>
</div>

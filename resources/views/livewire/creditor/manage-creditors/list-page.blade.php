@use('App\Enums\CompanyStatus')
@use('App\Enums\CreditorCurrentStep')
@use('App\Enums\CompanyBusinessCategory')
@use('Illuminate\Support\Number')

<div>
    <div class="card">
        <div
            @class([
                'flex flex-col sm:flex-row p-4 sm:items-center gap-4',
                'justify-between' => $companies->isNotEmpty(),
                'justify-end' => $companies->isEmpty()
            ])
        >
            <x-table.per-page-count :items="$companies" />
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center w-full sm:w-auto gap-2">
                <label class="inline-flex space-x-2 items-center">
                    <input
                        type="checkbox"
                        class="form-checkbox is-basic size-4 sm:size-4.5 rounded border-slate-400/70 checked:bg-primary hover:border-primary focus:border-primary"
                        wire:model.live="onlyTrashed"
                    />
                    <span>{{ __('Only Trashed') }}</span>
                </label>

                <div>
                    <x-search-box
                        name="search"
                        wire:model.live.debounce.400="search"
                        placeholder="{{ __('Search') }}"
                        :description="__('You can search by its companies name and owner full name')"
                    />
                </div>
            </div>
        </div>

        <div class="min-w-full overflow-auto">
            <x-table>
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th column="name" :$sortCol :$sortAsc>{{ __('Company Name') }}</x-table.th>
                        <x-table.th column="owner_full_name" :$sortCol :$sortAsc>{{ __('Owner Name') }}</x-table.th>
                        <x-table.th column="status" :$sortCol :$sortAsc class="lg:w-28">{{ __('Status') }}</x-table.th>
                        <x-table.th class="lg:w-40">{{ __('Setup Completed') }}</x-table.th>
                        <x-table.th column="merchant_status" :$sortCol :$sortAsc class="lg:w-44">{{ __('Merchant Status') }}</x-table.th>
                        <x-table.th column="category" :$sortCol :$sortAsc class="lg:w-60">{{ __('Company Type') }}</x-table.th>
                        <x-table.th column="created_on" :$sortCol :$sortAsc class="lg:w-36">{{ __('Created On') }}</x-table.th>
                        <x-table.th column="consumers_count" :$sortCol :$sortAsc class="lg:w-44">{{ __('Consumers Count') }}</x-table.th>
                        <x-table.th column="total_balance" :$sortCol :$sortAsc class="lg:w-36">{{ __('Total Balance') }}</x-table.th>
                        <x-table.th colspan="2" class="lg:w-1/12">{{ __('Actions') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse ($companies as $company)
                        <x-table.tr>
                            <x-table.td>{{ str($company->company_name ?? 'N/A')->title() }}</x-table.td>
                            <x-table.td>{{ str($company->owner_full_name ?? 'N/A')->title() }}</x-table.td>
                            @if ($company->is_deactivate)
                                <x-table.td>
                                    <span class="badge text-xs+ bg-error/20 text-error">{{ __('Blocked') }}</span>
                                </x-table.td>
                                <x-table.td>{{ ('N/A') }}</x-table.td>
                                <x-table.td>{{ ('N/A') }}</x-table.td>
                            @else
                                <x-table.td class="text-nowrap">
                                    <span @class([
                                        'badge text-xs+ text-nowrap',
                                        'bg-warning/20 text-warning' => $company->trashed(),
                                        'bg-success/20 text-success' => ! $company->trashed() && $company->current_step === CreditorCurrentStep::COMPLETED->value,
                                        'bg-primary/20 text-primary' => ! $company->trashed() && $company->current_step !== CreditorCurrentStep::COMPLETED->value,
                                    ])>
                                        {{ $company->trashed() ? __('Deleted') : ($company->current_step === CreditorCurrentStep::COMPLETED->value ? __('Active') : __('Started')) }}
                                    </span>
                                </x-table.td>
                                <x-table.td class="text-nowrap">
                                    <span @class([
                                        'badge text-xs+ text-nowrap',
                                        'bg-success/20 text-success' => $company->isSetupWizardCompleted,
                                        'bg-error/20 text-error' => ! $company->isSetupWizardCompleted,
                                    ])>
                                        {{ $company->isSetupWizardCompleted ? __('Yes') : __('No') }}
                                    </span>
                                </x-table.td>
                                <x-table.td class="text-nowrap">
                                    <span @class([
                                        'badge text-xs+ text-nowrap',
                                        'bg-success/20 text-success' => $company->status === CompanyStatus::ACTIVE,
                                        'bg-warning/20 text-warning' => $company->status === CompanyStatus::SUBMITTED,
                                        'bg-info/20 text-info' => $company->status !== CompanyStatus::ACTIVE,
                                    ])>
                                        {{ $company->status === CompanyStatus::SUBMITTED ? __('Pending') : ($company->status === CompanyStatus::ACTIVE ? __('Approved') : __('Not Approved')) }}
                                    </span>
                                </x-table.td>
                            @endif
                            <x-table.td>{{ $company->business_category ? CompanyBusinessCategory::displaySelectionBox()[$company->business_category->value] : 'N/A' }}</x-table.td>
                            <x-table.td>{{ $company->created_at->formatWithTimezone() }}</x-table.td>
                            <x-table.td>{{ Number::format($company->consumers_count) }}</x-table.td>
                            <x-table.td>{{ Number::currency((float) $company->consumers_sum_current_balance ?? 0) }}</x-table.td>
                            @if ($company->deleted_at === null)
                                <x-table.td>
                                    <x-menu>
                                        <x-menu.button class="hover:bg-slate-100 p-1 rounded-full">
                                            <x-heroicon-m-ellipsis-horizontal class="w-7" />
                                        </x-menu.button>

                                        <x-menu.items>
                                            <x-menu.item wire:click="switchBlockStatus({{ $company->id }})">
                                                <span
                                                    class="flex items-center"
                                                    @close-menu-item.window="menuOpen = false"
                                                >
                                                    <x-lucide-loader-2
                                                        wire:loading
                                                        wire:target="switchBlockStatus({{ $company->id }})"
                                                        class="size-5 mr-3 animate-spin"
                                                    />
                                                    @if (! $company->is_deactivate)
                                                        <x-lucide-shield-off
                                                            wire:loading.remove
                                                            wire:target="switchBlockStatus({{ $company->id }})"
                                                            class="size-5 mr-3"
                                                        />
                                                        <span>{{ __('Block') }}</span>
                                                    @else
                                                        <x-lucide-shield
                                                            wire:loading.remove
                                                            wire:target="switchBlockStatus({{ $company->id }})"
                                                            class="size-5 mr-3"
                                                        />
                                                        <span>{{ __('Unblock') }}</span>
                                                    @endif
                                                </span>
                                            </x-menu.item>

                                            @if (! $company->is_deactivate)
                                                <div @close-menu-item.window="menuOpen = false">
                                                    <x-menu.item
                                                        wire:click="exportConsumers({{ $company->id }})"
                                                        wire:target="exportConsumers({{ $company->id }})"
                                                        wire:loading.attr="disabled"
                                                        class="flex items-center gap-3"
                                                    >
                                                        <x-lucide-loader-2
                                                            wire:loading
                                                            wire:target="exportConsumers({{ $company->id }})"
                                                            class="size-5 animate-spin"
                                                        />
                                                        <x-heroicon-m-arrow-down-tray
                                                            wire:loading.remove
                                                            wire:target="exportConsumers({{ $company->id }})"
                                                            class="size-5"
                                                        />
                                                        <span>{{ __('Export Consumers') }}</span>
                                                    </x-menu.item>
                                                </div>
                                                @if ($company->creditorUser->email && $company->creditorUser->blocker_user_id === null && $company->creditorUser->blocked_at === null)
                                                    <div @close-menu-item.window="menuOpen = false">
                                                        <x-menu.item
                                                            wire:click="login({{ $company->id }})"
                                                            wire:loading.attr="disabled"
                                                            wire:target="login({{ $company->id }})"
                                                            class="flex items-center"
                                                        >
                                                            <x-heroicon-m-arrow-right-on-rectangle
                                                                wire:target="login({{ $company->id }})"
                                                                wire:loading.remove
                                                                class="size-5 mr-1"
                                                            />
                                                            <x-lucide-loader-2
                                                                wire:loading
                                                                wire:target="login({{ $company->id }})"
                                                                class="size-5 animate-spin mr-1"
                                                            />
                                                            <span>{{ __('Click Here to Login') }}</span>
                                                        </x-menu.item>
                                                    </div>
                                                @endif
                                                @if ($company->deleted_at === null)
                                                    <x-confirm-box
                                                        :message="__('Are you sure you want to delete this company?')"
                                                        :ok-button-label="__('Delete')"
                                                        action="delete({{ $company->id }})"
                                                    >
                                                        <x-menu.close>
                                                            <x-menu.item>
                                                                <span class="flex items-center">
                                                                    <x-heroicon-o-trash class="w-5 mr-3" />
                                                                    <span>{{ __('Delete') }}</span>
                                                                </span>
                                                            </x-menu.item>
                                                        </x-menu.close>
                                                    </x-confirm-box>
                                                    <a
                                                        wire:navigate
                                                        href="{{ route('super-admin.manage-creditors.view', $company->id) }}"
                                                    >
                                                        <x-menu.item class="space-x-1">
                                                            <x-heroicon-o-eye class="w-5" />
                                                            <span>{{ __('View') }}</span>
                                                        </x-menu.item>
                                                    </a>
                                                @endif
                                            @endif
                                        </x-menu.items>
                                    </x-menu>
                                </x-table.td>
                            @else
                                <x-table.td class="text-center">
                                    <span class="badge bg-slate-300 text-nowrap p-2 rounded">{{ __('No Action') }}</span>
                                </x-table.td>
                            @endif
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="10" />
                    @endforelse
                </x-slot>
            </x-table>
        </div>
        <x-table.per-page :items="$companies" />
    </div>
</div>

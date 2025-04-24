@use('Illuminate\Support\Number')

<div>
    <div class="card">
        <div class="flex flex-col sm:flex-row justify-end sm:items-center p-4 gap-2">
            <div>
                <a
                    wire:navigate
                    href="{{ route('super-admin.memberships.create') }}"
                    class="btn text-white bg-primary hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
                >
                    <div class="flex gap-x-1 items-center">
                        <x-lucide-circle-plus class="size-5" />
                        <span>{{ __('Create') }}</span>
                    </div>
                </a>
            </div>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center w-full sm:w-auto">
                <x-search-box
                    name="search"
                    wire:model.live.debounce.400="search"
                    placeholder="{{ __('Search') }}"
                    :description="__('You can search by its name and frequency')"
                />
            </div>
        </div>

        <div class="min-w-full overflow-auto">
            <x-table>
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th class="lg:w-0"></x-table.th>
                        <x-table.th class="lg:w-40">{{ __('Membership name') }}</x-table.th>
                        <x-table.th class="lg:w-32">{{ __('Licensing Fee') }}</x-table.th>
                        <x-table.th class="lg:w-32">{{ __('E-Letter Fee') }}</x-table.th>
                        <x-table.th class="lg:w-4">{{ __('Frequency') }}</x-table.th>
                        <x-table.th class="lg:w-48">{{ __('Percentage of Payments') }}</x-table.th>
                        <x-table.th class="lg:w-32">{{ __('Accounts Limit') }}</x-table.th>
                        <x-table.th class="lg:w-24">{{ __('Visible To') }}</x-table.th>
                        <x-table.th class="lg:w-24">{{ __('Status') }}</x-table.th>
                        <x-table.th class="lg:w-1/6">{{ __('Actions') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot
                    name="tableBody"
                    x-sort="$wire.sort($item, $position)"
                >
                    @forelse ($memberships as $membership)
                        <x-table.tr
                            x-sort:item="{{ $membership->id }}"
                            @class(['border-b-0' => $loop->last])
                        >
                            <x-table.td>
                                <x-lucide-menu
                                    x-sort:handle
                                    class="text-slate-300 cursor-pointer size-5"
                                />
                            </x-table.td>
                            <x-table.td>{{ $membership->name }}</x-table.td>
                            <x-table.td>{{ Number::currency((float) $membership->price) }}</x-table.td>
                            <x-table.td>{{ Number::currency((float) $membership->e_letter_fee) }}</x-table.td>
                            <x-table.td>{{ $membership->frequency->name }}</x-table.td>
                            <x-table.td>{{ Number::percentage($membership->fee, 2) }}</x-table.td>
                            <x-table.td>{{ Number::format($membership->upload_accounts_limit) }}</x-table.td>
                            <x-table.td>{{ $membership->company_id ? str($membership->company->company_name)->title() : 'Everyone' }}</x-table.td>
                            <x-table.td>
                                @php $membershipStatus = $membership->status ? __('Shown') : __('Hidden'); @endphp
                                <x-form.button
                                    type="button"
                                    wire:click="toggleActiveInactive({{ $membership->id }})"
                                    wire:target="toggleActiveInactive({{ $membership->id }})"
                                    wire:loading.attr="disabled"
                                    wire:key="{{ str()->random(10) }}"
                                    :variant="$membership->status ? 'success' : 'warning'"
                                    @class([
                                        'text-xs sm:text-sm px-2 sm:px-3 py-1.5 disabled:opacity-50',
                                        'hover:bg-success-focus' => $membership->status,
                                        'hover:bg-warning-focus' => ! $membership->status
                                    ])
                                >
                                    <div class="flex space-x-1 items-center">
                                        <x-lucide-loader-2
                                            wire:target="toggleActiveInactive({{ $membership->id }})"
                                            wire:loading
                                            class="animate-spin size-5 mr-2"
                                        />
                                        <span>{{ $membershipStatus }}</span>
                                    </div>
                                </x-form.button>
                            </x-table.td>
                            <x-table.td>
                                <div class="flex space-x-2">
                                    <a
                                        wire:navigate
                                        href="{{ route('super-admin.memberships.show', $membership->id) }}"
                                        class="text-xs sm:text-sm px-2 sm:px-3 py-1.5 btn text-white bg-success hover:bg-success-focus focus:bg-success-focus active:bg-success-focus/90"
                                    >
                                        <div class="flex space-x-1 items-center">
                                            <x-heroicon-o-eye class="size-4.5 sm:size-5 text-white" />
                                            <span>{{ __('View') }}</span>
                                        </div>
                                    </a>
                                    <a
                                        wire:navigate
                                        href="{{ route('super-admin.memberships.edit', $membership->id) }}"
                                        class="text-xs sm:text-sm px-2 sm:px-3 py-1.5 btn text-white bg-info hover:bg-info-focus focus:bg-info-focus active:bg-info-focus/90"
                                    >
                                        <div class="flex space-x-1 items-center">
                                            <x-heroicon-m-pencil-square class="size-4.5 sm:size-5 text-white" />
                                            <span>{{ __('Edit') }}</span>
                                        </div>
                                    </a>

                                    @if ($membership->company_memberships_exists)
                                        <x-form.button
                                            class="text-xs sm:text-sm px-2 sm:px-3 py-1.5 hover:bg-error-focus"
                                            type="button"
                                            variant="error"
                                            x-on:click="$notification({ text: '{{ __('We cannot delete a membership plan that has one or more active members.') }}', variant: 'error' })"
                                        >
                                            <div class="flex space-x-1 items-center">
                                                <x-heroicon-o-trash class="size-4.5 sm:size-5" />
                                                <span>{{ __('Delete') }}</span>
                                            </div>
                                        </x-form.button>
                                    @else
                                        <x-confirm-box
                                            :message="__('Are you sure you want to delete this membership plan?')"
                                            :ok-button-label="__('Delete')"
                                            action="delete('{{ $membership->company_memberships_exists }}', {{ $membership->id }})"
                                        >
                                            <x-form.button
                                                class="text-xs sm:text-sm px-2 sm:px-3 py-1.5 hover:bg-error-focus"
                                                type="button"
                                                variant="error"
                                            >
                                                <div class="flex space-x-1 items-center">
                                                    <x-heroicon-o-trash class="size-4.5 sm:size-5" />
                                                    <span>{{ __('Delete') }}</span>
                                                </div>
                                            </x-form.button>
                                        </x-confirm-box>
                                    @endif
                                </div>
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="9" />
                    @endforelse
                </x-slot>
            </x-table>
        </div>
    </div>
</div>

@use('Illuminate\Support\Number')
@use('App\Enums\ConsumerStatus')

<div>
    <div
        x-data="inspectlet"
        class="card my-8"
    >
        <div class="flex items-center space-x-4 py-3 px-4">
            <h2 class="text-md text-black font-semibold lg:text-lg">
                {{ __('My Consumer\'s YouNegotiate Experience') }}
            </h2>
        </div>

        <div class="flex justify-end px-4">
            <x-search-box
                name="search"
                wire:model.live.debounce.400="search"
                placeholder="{{ __('Search') }}"
                :description="__('You can search by its name and account number.')"
            />
        </div>

        <div class="is-scrollbar-hidden min-w-full overflow-x-auto mt-2">
            <x-table>
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th>{{ __('Account#') }}</x-table.th>
                        <x-table.th>{{ __('Consumer Name') }}</x-table.th>
                        @if (auth()->user()->subclient_id === null)
                            <x-table.th>{{ __('Subclient Name') }}</x-table.th>
                        @endif
                        <x-table.th>{{ __('Current Balance') }}</x-table.th>
                        <x-table.th>{{ __('Status') }}</x-table.th>
                        <x-table.th>{{ __('Action') }}</x-table.th>
                        <x-table.th>{{ __('Activities') }}</x-table.th>
                    </x-table.tr>
                </x-slot>

                <x-slot name="tableBody">
                    @forelse ($consumers as $consumer)
                        <x-table.tr @class(['border-none' => $loop->last])>
                            <x-table.td>
                                <a
                                    wire:navigate
                                    href="{{ route('manage-consumers.view', ['consumer' => $consumer->id]) }}"
                                    class="text-primary hover:cursor-pointer hover:underline underline-offset-2"
                                >
                                    {{ '#' . $consumer->account_number }}
                                </a>
                            </x-table.td>
                            <x-table.td>
                                <a
                                    wire:navigate
                                    class="text-primary hover:cursor-pointer hover:underline underline-offset-2"
                                    href="{{ route('manage-consumers.view', ['consumer' => $consumer->id]) }}"
                                >
                                    {{ $consumer->first_name . ' ' . $consumer->last_name }}
                                </a>
                            </x-table.td>
                            @if (auth()->user()->subclient_id === null)
                                <x-table.td>{{ $consumer->subclient?->subclient_name ?? 'N/A' }}</x-table.td>
                            @endif
                            <x-table.td>{{ Number::currency((float) $consumer->current_balance) }}</x-table.td>
                            <x-table.td>
                                <span
                                    @class([
                                        'badge',
                                        'bg-secondary/10 text-secondary hover:bg-secondary/20 focus:bg-secondary/20' => $consumer->status === ConsumerStatus::JOINED,
                                        'bg-error/10 text-error hover:bg-error/20 focus:bg-error/20' => $consumer->status === ConsumerStatus::DEACTIVATED,
                                        'bg-info/10 text-info hover:bg-info/20 focus:bg-info/20' => $consumer->status === ConsumerStatus::PAYMENT_ACCEPTED,
                                        'bg-warning/10 text-warning hover:bg-warning/20 focus:bg-warning/20' => $consumer->status === ConsumerStatus::PAYMENT_SETUP,
                                        'bg-primary/10 text-primary hover:bg-primary/20 focus:bg-primary/20' => $consumer->status === ConsumerStatus::RENEGOTIATE,
                                        'bg-indigo-600/10 text-indigo-600' => $consumer->status === ConsumerStatus::PAYMENT_DECLINED,
                                        'bg-rose-500/10 text-rose-500 hover:bg-rose-500/20 focus:bg-rose-500/20' => $consumer->status === ConsumerStatus::NOT_PAYING,
                                        'bg-accent/10 text-accent hover:bg-accent/20 focus:bg-accent/20' => $consumer->status === ConsumerStatus::SETTLED,
                                        'bg-navy-100 text-navy-300' => $consumer->status === ConsumerStatus::DISPUTE,
                                        'bg-success/10 text-success hover:bg-success/20 focus:bg-success/20' => $consumer->status === ConsumerStatus::UPLOADED,
                                        'bg-accent-light/10 text-accent-light hover:bg-accent-light/20 focus:bg-accent-light/20' => $consumer->status === ConsumerStatus::VISITED,
                                        'bg-slate-200 text-slate-300' => $consumer->status === ConsumerStatus::NOT_VERIFIED,
                                    ])
                                >
                                    {{ $consumer->status->displayLabel() }}
                                </span>
                            </x-table.td>

                            <x-table.td class="text-center">
                                <livewire:creditor.consumer-logs.row :$consumer :key="str()->random(10)" />
                            </x-table.td>
                            <x-table.td>
                                <x-form.button
                                    wire:click="inspectlet({{ $consumer->id }})"
                                    wire:target="inspectlet({{ $consumer->id }})"
                                    wire:loading.attr="disabled"
                                    type="button"
                                    variant="secondary"
                                    class="flex py-1 px-3 text-xs+ disabled:opacity-50"
                                >
                                    <x-heroicon-o-play class="size-4.5 mr-1" />
                                    <span>{{ __('Show Video') }}</span>
                                </x-form.button>
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="8" />
                    @endforelse
                </x-slot>
            </x-table>
        </div>
        <x-table.per-page :items="$consumers" />
    </div>

    @script
        <script>
            Alpine.data('inspectlet', () => ({
                init() {
                    this.$wire.$watch('sessionLink', () => {
                        if (this.$wire.sessionLink !== '') {
                            window.open(this.$wire.sessionLink, '_blank')
                        }
                    })
                },
            }))
        </script>
    @endscript
</div>

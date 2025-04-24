@use('Illuminate\Support\Number')
@use('App\Enums\MembershipTransactionStatus')

<div>
    <div class="card">
        <div
            @class([
                'flex flex-col sm:flex-row p-4 sm:items-center gap-4',
                'justify-between' => $billingHistories->isNotEmpty(),
                'justify-end' => $billingHistories->isEmpty()
            ])
        >
            <x-table.per-page-count :items="$billingHistories" />
            <div class="flex flex-col sm:flex-row sm:items-center justify-end gap-3">
                @if ($billingHistories->isNotEmpty())
                    <x-form.button
                        wire:click="export"
                        wire:loading.attr="disabled"
                        type="button"
                        variant="primary"
                        class="space-x-2 disabled:opacity-50"
                    >
                        <span>{{ __('Export') }}</span>
                        <x-lucide-download class="size-5" wire:loading.remove wire:target="export" />
                        <x-lucide-loader-2 class="animate-spin size-5" wire:loading wire:target="export" />
                    </x-form.button>
                @endif
                <a
                    wire:navigate
                    href="{{ route('creditor.membership-settings', ['accountDetailsDialogOpen' => true]) }}"
                    class="btn text-white bg-success hover:bg-success-focus focus:bg-success-focus active:bg-success-focus/90"
                >
                    <div class="flex gap-x-1 items-center">
                        <span>{{ __('Update Payment Method') }}</span>
                    </div>
                </a>
                <a
                    wire:navigate
                    href="{{ route('creditor.membership-settings') }}"
                    class="btn text-white bg-primary hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
                >
                    <div class="flex gap-x-1 items-center">
                        <span>{{ __('Change Plan') }}</span>
                    </div>
                </a>
            </div>
        </div>

        <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
            <x-table>
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th>{{ __('Payment Date') }}</x-table.th>
                        <x-table.th>{{ __('Invoice #') }}</x-table.th>
                        <x-table.th>{{ __('Total Amount') }}</x-table.th>
                        <x-table.th>{{ __('Pay Method') }}</x-table.th>
                        <x-table.th>{{ __('Payment Status') }}</x-table.th>
                        <x-table.th>{{ __('Actions') }}</x-table.th>
                    </x-table.tr>
                </x-slot>

                <x-slot name="tableBody">
                    @forelse($billingHistories as $billingHistory)
                        <x-table.tr>
                            <x-table.td>{{ $billingHistory->created_at->formatWithTimezone() }}</x-table.td>
                            <x-table.td>{{  $billingHistory->invoice_id }}</x-table.td>
                            <x-table.td>{{ Number::currency((float) $billingHistory->amount) }}</x-table.td>
                            <x-table.td>
                                {{
                                    data_get($billingHistory, 'response.payment_method.card.last4')
                                    ? '*** *** *** '.data_get($billingHistory, 'response.payment_method.card.last4')
                                    : 'N/A'
                                }}
                            </x-table.td>
                            <x-table.td>
                                @if ($billingHistory->status === MembershipTransactionStatus::FAILED)
                                    <span
                                        x-tooltip.placement.bottom="@js(data_get($billingHistory, 'response.last_payment_error.message', 'N/A'))"
                                        class="hover:underline cursor-pointer"
                                    >
                                    <p class="badge rounded-full bg-error/10 text-error">
                                        <x-lucide-info class="size-4 mr-1" />
                                        {{ __('Failed') }}
                                    </p>
                                    </span>
                                @else
                                    <p class="badge rounded-full bg-success/10 text-success">
                                        <x-lucide-circle-dot class="size-4 pr-1 fill-success" />
                                        {{ __('Paid') }}
                                    </p>
                                @endif
                            </x-table.td>
                            <x-table.td class="text-center">
                                <div class="flex space-x-2 items-center">
                                    <x-menu>
                                        <x-menu.button
                                            class="hover:bg-slate-100 rounded-full p-2"
                                            x-tooltip.placement.bottom.duration.1000="'{{ __('Action') }}'"
                                        >
                                            <x-heroicon-m-ellipsis-horizontal class="size-5"/>
                                        </x-menu.button>

                                        <x-menu.items>
                                            <div x-on:close-menu.window="menuOpen = false">
                                                <x-menu.item
                                                    wire:click="downloadInvoice({{ $billingHistory->id }}, '{{ $billingHistory->type }}')"
                                                    wire:loading.attr="disabled"
                                                >
                                                    <div
                                                        wire:loading.flex
                                                        wire:target="downloadInvoice({{ $billingHistory->id }}, '{{ $billingHistory->type }}')"
                                                        class="flex space-x-2"
                                                    >
                                                        <img src="https://api.iconify.design/svg-spinners:wind-toy.svg" class="size-5">
                                                        <span class="whitespace-nowrap">{{ __('Opening') }}</span>
                                                    </div>
                                                    <div
                                                        wire:loading.remove
                                                        wire:target="downloadInvoice({{ $billingHistory->id }}, '{{ $billingHistory->type }}')"
                                                        class="flex space-x-2"
                                                    >
                                                        <x-heroicon-o-arrow-down-tray class="size-5"/>
                                                        <span class="whitespace-nowrap">{{ __('Open') }}</span>
                                                    </div>
                                                </x-menu.item>
                                                @if ($billingHistory->status === MembershipTransactionStatus::FAILED)
                                                    <x-menu.item
                                                        wire:click="reprocess({{ $billingHistory->id }})"
                                                        wire:loading.attr="disabled"
                                                    >
                                                        <div
                                                            wire:loading.flex
                                                            wire:target="reprocess({{ $billingHistory->id }})"
                                                            class="flex space-x-2"
                                                        >
                                                            <img src="https://api.iconify.design/svg-spinners:wind-toy.svg" class="size-5">
                                                            <span class="whitespace-nowrap">{{ __('Reprocessing') }}</span>
                                                        </div>
                                                        <div
                                                            wire:loading.remove
                                                            wire:target="reprocess({{ $billingHistory->id }})"
                                                            class="flex space-x-2"
                                                        >
                                                            <x-lucide-badge-dollar-sign class="size-5"/>
                                                            <span class="whitespace-nowrap">{{ __('Reprocess Payment') }}</span>
                                                        </div>
                                                    </x-menu.item>
                                                @endif

                                                <a
                                                    href="mailto:help@younegotiate.com?subject=Billing inquiry for invoice id: {{ $billingHistory->invoice_id }}"
                                                    target="_blank"
                                                >
                                                    <x-menu.item>
                                                        <x-heroicon-o-eye class="w-5" />
                                                        <span>{{ __('Contact Billing') }}</span>
                                                    </x-menu.item>
                                                </a>
                                            </div>
                                        </x-menu.items>
                                    </x-menu>
                                </div>
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="6" />
                    @endforelse
                </x-slot>
            </x-table>
        </div>
        <x-table.per-page :items="$billingHistories" />
        <p class="text-primary text-right text-lg p-4 font-bold">
            <a class="hover:text-primary-400" href="mailto:help@younegotiate.com"> {{ __('Contact Us') }} </a>
        </p>
    </div>
</div>

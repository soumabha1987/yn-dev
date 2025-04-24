<div>
    <x-dialog>
        <x-dialog.open>
            <x-form.button
                type="button"
                variant="primary"
                class="flex py-1 px-3 text-xs+"
            >
                <x-heroicon-o-eye class="size-4.5 mr-1" />
                <span>{{ __('View Logs') }}</span>
            </x-form.button>
        </x-dialog.open>

        <x-dialog.panel size="3xl" class="h-96">
            <x-slot name="heading">{{ __('Activity Details') }}</x-slot>

            <div class="my-2">
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-slate-150 p-4">
                        <p class="mt-1 text-xs+">{{ __('Consumer Name') }}</p>
                        <div class="flex justify-between space-x-1">
                            <p class="text-xl font-semibold text-slate-700">
                                {{ $consumer->first_name . ' ' . $consumer->last_name}}
                            </p>
                        </div>
                    </div>

                    <div class="rounded-lg bg-slate-150 p-4">
                        <p class="mt-1 text-xs+">{{ __('Account Number') }}</p>
                        <div class="flex justify-between space-x-1">
                            <p class="text-xl font-semibold text-slate-700">
                                {{ '#' . $consumer->account_number }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <h2 class="text-lg font-medium text-slate-800">
                {{ __('Consumer Logs') }}
            </h2>

            <div class="card mt-3 mb-4">
                <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                    <x-table class="text-base">
                        <x-slot name="tableHead">
                            <x-table.tr>
                                <x-table.th class="rounded-tl-lg">{{ __('Time') }}</x-table.th>
                                <x-table.th class="rounded-tr-lg">{{ __('Event') }}</x-table.th>
                            </x-table.tr>
                        </x-slot>
                        <x-slot name="tableBody">
                            @forelse($consumerLogs as $consumerLog)
                                <x-table.tr @class(['border-none' => $loop->last])>
                                    <x-table.td>{{ $consumerLog->created_at->formatWithTimezone(format: 'M, d Y H:i:s') }}</x-table.td>
                                    <x-table.td>{{ $consumerLog->log_message }}</x-table.td>
                                </x-table.tr>
                            @empty
                                <x-table.no-items-found :colspan="2" />
                            @endforelse
                        </x-slot>
                    </x-table>
                </div>
                <x-table.per-page :items="$consumerLogs" />
            </div>

            <x-slot name="footer" class="mt-3">
                <x-dialog.close>
                    <x-form.button
                        variant="error"
                        type="button"
                        class="mt-3"
                    >
                        {{ __('Close') }}
                    </x-form.button>
                </x-dialog.close>
            </x-slot>
        </x-dialog.panel>
    </x-dialog>
</div>

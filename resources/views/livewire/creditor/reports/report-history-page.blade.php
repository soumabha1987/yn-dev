@use('App\Enums\ReportHistoryStatus')

<div>
    <div class="card">
        <div class="p-4">
            <x-table.per-page-count :items="$reportHistories" />
        </div>
        <div class="min-w-full overflow-auto">
            <x-table>
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th column="created-on" :$sortCol :$sortAsc>{{ __('Created On') }}</x-table.th>
                        <x-table.th column="name" :$sortCol :$sortAsc>{{ __('Report Name') }}</x-table.th>
                        <x-table.th column="account-in-scope" :$sortCol :$sortAsc>{{ __('Account in Scope') }}</x-table.th>
                        <x-table.th column="records" :$sortCol :$sortAsc>{{ __('# Records') }}</x-table.th>
                        <x-table.th column="start-date" :$sortCol :$sortAsc>{{ __('Start Date') }}</x-table.th>
                        <x-table.th column="end-date" :$sortCol :$sortAsc>{{ __('End Date') }}</x-table.th>
                        <x-table.th>{{ __('Actions') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse($reportHistories as $reportHistory)
                        <x-table.tr>
                            <x-table.td>{{ $reportHistory->created_at->formatWithTimezone() }}</x-table.td>
                            <x-table.td>{{ $reportHistory->report_type->displayName() }}</x-table.td>
                            <x-table.td>
                                {{ $reportHistory->subclient_id ? str($reportHistory->subclient->subclient_name)->title(). '/' . $reportHistory->subclient->unique_identification_number : __('Master-  All Accounts') }}
                            </x-table.td>
                            <x-table.td>{{ $reportHistory->records }}</x-table.td>
                            <x-table.td>{{ $reportHistory->start_date->format('M d, Y') }}</x-table.td>
                            <x-table.td>{{ $reportHistory->end_date->format('M d, Y') }}</x-table.td>
                            <x-table.td class="whitespace-nowrap">
                                @if ($reportHistory->status === ReportHistoryStatus::SUCCESS)
                                    <x-form.button
                                        wire:click="downloadReport({{ $reportHistory->id }})"
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-50"
                                        wire:target="downloadReport({{ $reportHistory->id }})"
                                        type="button"
                                        variant="success"
                                        class="text-xs sm:text-sm+ px-3 py-1.5"
                                    >
                                        <div
                                            wire:loading.flex
                                            class="flex items-center gap-x-2"
                                            wire:target="downloadReport({{ $reportHistory->id }})"
                                        >
                                            <x-lucide-loader-2 class="size-4.5 sm:size-5 animate-spin" />
                                            {{ __('Downloading') }}
                                        </div>
                                        <div
                                            class="flex items-center gap-x-2"
                                            wire:loading.remove
                                            wire:target="downloadReport({{ $reportHistory->id }})"
                                        >
                                            <x-lucide-download class="size-4.5 sm:size-5 text-white" />
                                            <span>{{ __('Download') }}</span>
                                        </div>
                                    </x-form.button>
                                @else
                                    <span class="mx-auto badge bg-error/10 text-error">{{ $reportHistory->status->name }}</span>
                                @endif
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="7" />
                    @endforelse
                </x-slot>
            </x-table>
        </div>
        <x-table.per-page :items="$reportHistories" />
    </div>
</div>

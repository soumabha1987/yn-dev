@use('Illuminate\Support\Number')
@use('App\Enums\CampaignFrequency')

<div class="card">
    <div
        @class([
            'flex flex-col sm:flex-row p-4 sm:items-center gap-4',
            'justify-between' => $campaignTrackers->isNotEmpty(),
            'justify-end' => $campaignTrackers->isEmpty()
        ])
    >
        <x-table.per-page-count :items="$campaignTrackers" />
    </div>
    <div
        wire:poll.30s="$refresh"
        class="min-w-full overflow-x-auto mt-2"
    >
        <x-table>
            <x-slot name="tableHead">
                <x-table.tr>
                    <x-table.th column="sent-on" :$sortCol :$sortAsc>{{ __('Sent On') }}</x-table.th>
                    <x-table.th column="template-name" :$sortCol :$sortAsc>{{ __('eLetter') }}</x-table.th>
                    <x-table.th column="group-name" :$sortCol :$sortAsc>{{ __('Group') }}</x-table.th>
                    <x-table.th column="total-balance" :$sortCol :$sortAsc>{{ __('Total Balances') }}</x-table.th>
                    <x-table.th column="sent" :$sortCol :$sortAsc>{{ __('# Sent') }}</x-table.th>
                    <x-table.th column="delivered" :$sortCol :$sortAsc>{{ __('# Delivered') }}</x-table.th>
                    <x-table.th column="delivered-percentage" :$sortCol :$sortAsc>{{ __('% Delivered') }}</x-table.th>
                    <x-table.th column="opened" :$sortCol :$sortAsc>{{ __('% Opened') }}</x-table.th>
                    <x-table.th column="pif-offer" :$sortCol :$sortAsc>{{ __('% PIFOffer') }}</x-table.th>
                    <x-table.th column="ppl-offer" :$sortCol :$sortAsc>{{ __('% PPLOffer') }}</x-table.th>
                    <x-table.th column="sent-offer" :$sortCol :$sortAsc>{{ __('% Sent Offer') }}</x-table.th>
                    <x-table.th>{{ __('Actions') }}</x-table.th>
                </x-table.tr>
            </x-slot>
            <x-slot name="tableBody">
                @forelse($campaignTrackers as $campaignTracker)
                    <x-table.tr>
                        <x-table.td>{{ $campaignTracker->created_at->formatWithTimezone() }}</x-table.td>
                        <x-table.td @class([
                            'text-error font-semibold' => ! $campaignTracker->campaign->template?->name,
                        ])>
                            {{ $campaignTracker->campaign->template->name ?? 'N/A' }}
                        </x-table.td>
                        <x-table.td @class([
                            'text-error font-semibold' => ! $campaignTracker->campaign->group?->name,
                        ])>
                            {{ $campaignTracker->campaign->group->name ?? 'N/A' }}
                        </x-table.td>
                        <x-table.td>{{ Number::currency((float) $campaignTracker->total_balance_of_consumers ?? 0) }}</x-table.td>
                        <x-table.td>{{ Number::format($campaignTracker->consumer_count) }}</x-table.td>
                        <x-table.td>{{ Number::format($campaignTracker->delivered_count) }}</x-table.td>
                        <x-table.td>
                            {{ Number::percentage($campaignTracker->consumer_count > 0 ? ($campaignTracker->delivered_count * 100 / $campaignTracker->consumer_count) : 0, 2) }}
                        </x-table.td>
                        <x-table.td>
                            {{ Number::percentage($campaignTracker->consumer_count > 0 ? ($campaignTracker->clicks_count * 100 / $campaignTracker->consumer_count) : 0, 2) }}
                        </x-table.td>
                        <x-table.td>
                            {{ Number::percentage($campaignTracker->consumer_count > 0 ? ($campaignTracker->pif_completed_count * 100 / $campaignTracker->consumer_count) : 0, 2) }}
                        </x-table.td>
                        <x-table.td>
                            {{ Number::percentage($campaignTracker->consumer_count > 0 ? ($campaignTracker->ppl_completed_count * 100 / $campaignTracker->consumer_count) : 0, 2) }}
                        </x-table.td>
                        <x-table.td>
                            {{ Number::percentage($campaignTracker->consumer_count > 0 ? ($campaignTracker->custom_offer_count * 100 / $campaignTracker->consumer_count) : 0, 2) }}
                        </x-table.td>
                        <x-table.td class="flex items-center">
                            <div class="mr-2">
                                @if(blank($campaignTracker->campaign->template) || blank($campaignTracker->campaign->group) || $campaignTracker->campaign->frequency !== CampaignFrequency::ONCE )
                                    <div
                                        x-tooltip.placement.bottom="@js(
                                            $campaignTracker->campaign->frequency !== CampaignFrequency::ONCE
                                            ? __('Only one time campaign frequency allow to re run')
                                            : __('This campaign template/group deleted.')
                                        )"
                                        class="text-xs sm:text-sm btn px-3 py-1.5 select-none text-white bg-info/50 flex space-x-1 items-center cursor-not-allowed"
                                    >
                                        <x-lucide-refresh-ccw class="size-4.5 sm:size-5"/>
                                        <span>{{ __('ReRun') }}</span>
                                    </div>
                                @else
                                    <x-form.button
                                        type="button"
                                        variant="info"
                                        class="text-xs sm:text-sm px-3 py-1.5 disabled:opacity-50 hover:bg-info-focus"
                                        wire:click="reRun({{ $campaignTracker->id }})"
                                        wire:target="reRun({{ $campaignTracker->id }})"
                                        wire:loading.attr="disabled"
                                    >
                                        <div class="flex space-x-2 items-center">
                                            <x-lucide-refresh-ccw class="size-4.5 sm:size-5"/>
                                            <span>{{ __('ReRun') }}</span>
                                        </div>
                                    </x-form.button>
                                @endif
                            </div>
                            <div class="mr-2">
                                <x-form.button
                                    type="button"
                                    variant="primary"
                                    class="text-xs sm:text-sm px-3 py-1.5 whitespace-nowrap disabled:opacity-50 hover:bg-info-focus"
                                    wire:click="exportConsumers({{ $campaignTracker->id }})"
                                    wire:target="exportConsumers({{ $campaignTracker->id }})"
                                    wire:loading.attr="disabled"
                                >
                                    <div class="flex space-x-reverse gap-2 items-center">
                                        <x-lucide-download
                                            wire:loading.remove
                                            wire:target="exportConsumers({{ $campaignTracker->id }})"
                                            class="size-4.5 sm:size-5"
                                        />
                                        <x-lucide-loader-2
                                            wire:loading
                                            wire:target="exportConsumers({{ $campaignTracker->id }})"
                                            class="size-4.5 sm:size-5 animate-spin"
                                        />
                                        <span class="w-32">{{ __('Export Consumers') }}</span>
                                    </div>
                                </x-form.button>
                            </div>
                        </x-table.td>
                    </x-table.tr>
                @empty
                    <x-table.no-items-found :colspan="13" />
                @endforelse
            </x-slot>
        </x-table>
    </div>
    <x-table.per-page :items="$campaignTrackers" />
</div>

@use('App\Enums\CampaignFrequency')
@use('Carbon\Carbon')
@use('App\Enums\Role')

<div
    x-on:refresh-list-view.window="$wire.$refresh"
    class="card mt-8"
>
    <div
        @class([
            'flex flex-col sm:flex-row p-4 sm:items-center gap-4',
            'justify-between' => $campaigns->isNotEmpty(),
            'justify-end' => $campaigns->isEmpty()
        ])
    >
        <x-table.per-page-count :items="$campaigns" />
        <div class="flex flex-col sm:flex-row space-x-2 justify-end items-stretch sm:items-center w-full sm:w-auto">
            <x-search-box
                name="search"
                wire:model.live.debounce.400="search"
                placeholder="{{ __('Search') }}"
                :description="__('You can search by frequency, template name and group name.')"
            />
        </div>
    </div>
    <div class="min-w-full overflow-auto">
        <x-table>
            <x-slot name="tableHead">
                <x-table.tr>
                    <x-table.th column="start-date" :$sortCol :$sortAsc>{{ __('Start Date') }}</x-table.th>
                    <x-table.th column="end-date" :$sortCol :$sortAsc>{{ __('End Date') }}</x-table.th>
                    @if($isCreditor)
                        <x-table.th column="template-name" :$sortCol :$sortAsc>{{ __('eLetter Name') }}</x-table.th>
                    @else
                        <x-table.th column="template-name" :$sortCol :$sortAsc>{{ __('Template Name') }}</x-table.th>
                        <x-table.th column="template-type" :$sortCol :$sortAsc>{{ __('Type') }}</x-table.th>
                    @endif
                    <x-table.th column="group-name" :$sortCol :$sortAsc>{{ __('Group Name') }}</x-table.th>
                    <x-table.th column="frequency" :$sortCol :$sortAsc>{{ __('Frequency') }}</x-table.th>
                    <x-table.th column="time" :$sortCol :$sortAsc>{{ __('Terms') }}</x-table.th>
                    <x-table.th class="lg:w-1/12">{{ __('Actions') }}</x-table.th>
                </x-table.tr>
            </x-slot>
            <x-slot name="tableBody">
                @forelse($campaigns as $campaign)
                    @php
                        $frequency = (string) str($campaign->frequency->name)->title();

                        $executionTime = match ($campaign->frequency) {
                            CampaignFrequency::MONTHLY => __("{$frequency} on the :monthDate", ['monthDate' => now()->day($campaign->day_of_month)->format('jS')]),
                            CampaignFrequency::WEEKLY => __("{$frequency} on :weekDay", ['weekDay' => now()->startOfWeek()->addDays($campaign->day_of_week - 1)->format('l')]),
                            CampaignFrequency::DAILY => __("{$frequency}"),
                            CampaignFrequency::ONCE => __("One time on :date", ['date' => $campaign->start_date->format('M d, Y')]),
                        };
                    @endphp
                    <x-table.tr>
                        <x-table.td>{{ $campaign->start_date->format('M d, Y') }}</x-table.td>
                        <x-table.td>{{ $campaign->end_date?->format('M d, Y') ?? '-' }}</x-table.td>
                        <x-table.td @class([
                            'text-error font-semibold' => ! $campaign->template?->name,
                        ])>
                            {{ $campaign->template->name ?? __('Deleted') }}
                        </x-table.td>
                        @if (! $isCreditor)
                            <x-table.td @class([
                                'text-error font-semibold' => ! $campaign->template?->type,
                            ])>
                                {{ $campaign->template->type->name ?? __('Deleted') }}
                            </x-table.td>
                        @endif
                        <x-table.td @class([
                            'text-error font-semibold' => ! $campaign->group?->name,
                        ])>
                            {{ $campaign->group->name ?? __('Deleted') }}
                        </x-table.td>
                        <x-table.td>{{ $campaign->frequency->displayName() }}</x-table.td>
                        <x-table.td class="text-nowrap">
                            {{ $executionTime }}
                        </x-table.td>
                        <x-table.td class="flex items-center">
                            @if (! ($campaign->frequency === CampaignFrequency::ONCE && $campaign->campaign_trackers_exists))
                                <div class="mr-2">
                                    <x-form.button
                                        type="button"
                                        wire:click="$parent.edit({{ $campaign->id }})"
                                        wire:target="$parent.edit({{ $campaign->id }})"
                                        wire:loading.attr="disabled"
                                        variant="bg-info"
                                        class="text-xs sm:text-sm+ px-3 py-1.5 btn select-none text-white bg-info hover:bg-info-focus"
                                    >
                                        <div class="flex space-x-1 items-center">
                                            <x-heroicon-o-pencil-square class="size-4.5 sm:size-5"/>
                                            <span>{{ __('Edit') }}</span>
                                        </div>
                                    </x-form.button>
                                </div>
                            @endif
                            <div class="mr-2">
                                <x-confirm-box
                                    :ok-button-label="__('Delete')"
                                    action="delete({{ $campaign->id }})"
                                >
                                    <x-slot name="message">
                                        <span> {{ __('Are you sure you want to delete this campaign?') }} </span>
                                        <p> {{ __('All associated campaign trackers will also be permanently deleted.') }} </p>
                                    </x-slot>
                                    <x-form.button class="text-xs sm:text-sm+ px-3 py-1.5" type="button" variant="error">
                                        <div class="flex space-x-1 items-center">
                                            <x-heroicon-o-trash class="size-4.5 sm:size-5"/>
                                            <span>{{ __('Delete') }}</span>
                                        </div>
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
    <x-table.per-page :items="$campaigns"/>
</div>

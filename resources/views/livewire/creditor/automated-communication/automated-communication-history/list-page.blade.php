@use('App\Enums\AutomatedCommunicationHistoryStatus')
@use('App\Enums\AutomatedTemplateType')
@use('App\Enums\CommunicationCode')
@use('Illuminate\Support\Number')

<div>
    <div class="card">
        <div
            @class([
                "flex items-center justify-between px-4 py-3",
                'hidden' => $status === '' && $communicationCode === '' && $templateType === '' && $searchTerm === '' && $company === ''
            ])
        >
            <x-form.button
                wire:click="resetFilters"
                class="hover:bg-error-focus"
                type="button"
                variant="error"
            >
                {{ __('Reset Filters') }}
            </x-form.button>
        </div>

        <div
            @class([
                'flex flex-col sm:flex-row p-4 sm:items-center gap-4',
                'justify-between' => $automatedCommunicationHistories->isNotEmpty(),
                'justify-end' => $automatedCommunicationHistories->isEmpty()
            ])
        >
            <div class="sm:w-40">
                <x-table.per-page-count :items="$automatedCommunicationHistories" />
            </div>
            <div @class([
                'grid gap-2 px-4 sm:grid-cols-2 md:grid-cols-3 items-center',
                'lg:grid-cols-6' => $hasSubclient = ($company && count($subclients) > 0),
                'lg:grid-cols-5' => ! $hasSubclient,
            ])>
                <div class="flex flex-col lg:flex-row justify-end mt-1.5">
                    <x-search-box
                        name="searchTerm"
                        wire:model.live.debounce.400="searchTerm"
                        placeholder="{{ __('Search') }}"
                        :description="__('You can search by company, template and consumer names')"
                    />
                </div>
                <div>
                    <x-form.select
                        wire:model.live="communicationCode"
                        :options="array_combine(CommunicationCode::values(), CommunicationCode::values())"
                        name="code"
                        placeholder="Code"
                    />
                </div>
                <div>
                    <x-form.select
                        wire:model.live="company"
                        :options="$companies"
                        name="company"
                        :placeholder="__('Company')"
                    />
                </div>
                @if ($company && count($subclients) > 0)
                    <div>
                        <x-form.select
                            wire:model.live="subclient"
                            :options="$subclients"
                            name="subclient"
                            :placeholder="__('Subclient')"
                        />
                    </div>
                @endif
                <div>
                    <x-form.select
                        wire:model.live="status"
                        :options="AutomatedCommunicationHistoryStatus::displaySelectionBox()"
                        name="code"
                        placeholder="Status"
                    />
                </div>
                <div>
                    <x-form.select
                        wire:model.live="templateType"
                        :options="AutomatedTemplateType::displaySelectionBox()"
                        name="code"
                        placeholder="Template Type"
                    />
                </div>
            </div>
        </div>

        <div class="px-4 mt-3">
            <b class="text-black">{{ __('Total cost: :cost', ['cost' => Number::currency((float) $totalCost ?: 0)]) }}</b>
        </div>

        <div class="min-w-full mt-3 overflow-auto">
            <x-table>
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th column="code" :$sortCol :$sortAsc class="lg:w-4">{{ __('Code') }}</x-table.th>
                        <x-table.th column="company_name" :$sortCol :$sortAsc>{{ __('Company Name') }}</x-table.th>
                        <x-table.th column="consumer_name" :$sortCol :$sortAsc>{{ __('Consumer Name') }}</x-table.th>
                        <x-table.th column="template_type" :$sortCol :$sortAsc>{{ __('Template Type') }}</x-table.th>
                        <x-table.th column="template_name" :$sortCol :$sortAsc>{{ __('Template Name') }}</x-table.th>
                        <x-table.th column="cost" :$sortCol :$sortAsc class="w-1/12">{{ __('Cost') }}</x-table.th>
                        <x-table.th column="status" :$sortCol :$sortAsc class="w-1/12">{{ __('Status') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse($automatedCommunicationHistories as $automatedCommunicationHistory)
                        <x-table.tr>
                            <x-table.td @class([
                                'text-error font-semibold' => ! $automatedCommunicationHistory->communicationStatus,
                            ])>
                                {{ $automatedCommunicationHistory->communicationStatus->code ?? 'N/A' }}
                            </x-table.td>
                            <x-table.td>
                                {{ $automatedCommunicationHistory->company->company_name }}
                            </x-table.td>
                            <x-table.td>
                                {{ filled($name = $automatedCommunicationHistory->consumer?->first_name . ' ' . $automatedCommunicationHistory->consumer?->last_name) ? $name : 'N/A' }}
                            </x-table.td>
                            <x-table.td>
                                <span
                                    x-tooltip.placement.bottom="@js($automatedCommunicationHistory->automated_template_type === AutomatedTemplateType::EMAIL ? $automatedCommunicationHistory->email ?? 'N/A' : $automatedCommunicationHistory->phone ?? 'N/A')"
                                    @class([
                                        'text-error' => ! $automatedCommunicationHistory->automated_template_type,
                                    ])
                                >
                                    {{ $automatedCommunicationHistory->automated_template_type?->name ?? 'N/A' }}
                                </span>
                            </x-table.td>
                            <x-table.td>
                                <span
                                    @class([
                                        'text-error' => ! $automatedCommunicationHistory->automatedTemplate,
                                    ])
                                >
                                    {{ $automatedCommunicationHistory->automatedTemplate?->name ?? 'N/A' }}
                                </span>
                            </x-table.td>
                            <x-table.td>
                                <span
                                    @class([
                                        'text-error' => ! $automatedCommunicationHistory->cost,
                                    ])
                                >
                                    {{ $automatedCommunicationHistory->cost ?? 'N/A' }}
                                </span>
                            </x-table.td>
                            <x-table.td>
                                <span @class([
                                    'badge',
                                    'bg-success/10 text-success' => $automatedCommunicationHistory->status == AutomatedCommunicationHistoryStatus::SUCCESS,
                                    'bg-warning/10 text-warning' => $automatedCommunicationHistory->status == AutomatedCommunicationHistoryStatus::IN_PROGRESS,
                                    'bg-error/10 text-error' => $automatedCommunicationHistory->status == AutomatedCommunicationHistoryStatus::FAILED,
                                ])>
                                    {{ $automatedCommunicationHistory->status->displayName() }}
                                </span>
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="13" />
                    @endforelse
                </x-slot>
            </x-table>
        </div>
        <x-table.per-page :items="$automatedCommunicationHistories" />
    </div>
</div>

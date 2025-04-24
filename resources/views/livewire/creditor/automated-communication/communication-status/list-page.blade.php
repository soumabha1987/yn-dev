<div>
    <div class="card">
        <div class="flex flex-col sm:flex-row space-x-2 justify-end p-4 items-stretch sm:items-center w-full sm:w-auto">
            <x-search-box
                name="search"
                wire:model.live.debounce.400="search"
                placeholder="{{ __('Search') }}"
                :description="__('You can search by code and template name.')"
            />
        </div>

        <div class="min-w-full overflow-auto">
            <x-table>
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th column="status" :$sortCol :$sortAsc class="lg:w-4">{{ __('Status') }}</x-table.th>
                        <x-table.th class="lg:w-1/6">{{ __('Description') }}</x-table.th>
                        <x-table.th column="email_template_name" :$sortCol :$sortAsc>{{ __('Email Template') }}</x-table.th>
                        <x-table.th column="sms_template_name" :$sortCol :$sortAsc>{{ __('SMS Template') }}</x-table.th>
                        <x-table.th column="trigger_type" :$sortCol :$sortAsc class="lg:w-36">{{ __('Trigger Type') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse ($communicationStatuses as $communicationStatus)
                        <livewire:creditor.automated-communication.communication-status.row
                            :key="$communicationStatus->id"
                            :loop="$loop"
                            :$communicationStatus
                        />
                    @empty
                        <x-table.no-items-found colspan="5" />
                    @endforelse
                </x-slot>
            </x-table>
        </div>
    </div>
</div>

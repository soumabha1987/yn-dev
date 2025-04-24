@use('App\Enums\Role')
@use('App\Enums\ScheduleExportFrequency')
@use('Illuminate\Support\Str')

<div class="card">
    <div
        @class([
            'sm:flex space-y-2 space-x-0 sm:space-x-2 sm:space-y-0 items-center p-4',
            'justify-between' => $scheduleExports->isNotEmpty(),
            'justify-end' => $scheduleExports->isEmpty()
        ])
    >
        <x-table.per-page-count :items="$scheduleExports" />
        <div class="sm:flex justify-between items-center space-y-2 space-x-0 sm:space-x-2 sm:space-y-0">
            <a
                wire:navigate
                href="{{ auth()->user()->hasRole(Role::SUPERADMIN) ? route('schedule-export.create') : route('creditor.schedule-export.create') }}"
                class="btn flex gap-1 space-x-reverse items-center text-white bg-primary hover:bg-primary-focus focus:bg-primary-focus w-max"
            >
                <x-lucide-circle-plus class="size-5" />
                <span>{{ __('Create a Report') }}</span>
            </a>
        </div>
    </div>

    @if ($thisFeatureIsDisabled)
        <div class="alert flex overflow-hidden bg-primary/10 text-primary mb-3">
            <div class="flex flex-1 items-center space-x-3 p-4">
                <x-heroicon-o-exclamation-triangle class="size-8" />
                <div class="flex-1">
                    <strong>{{ __('This feature is disabled by YouNegotiate') }}</strong>
                </div>
            </div>
        </div>
    @endif

    <div class="min-w-full overflow-auto">
        <x-table class="w-full">
            <x-slot name="tableHead">
                <x-table.tr>
                    <x-table.th column="created_on" :$sortCol :$sortAsc>{{ __('Created On') }}</x-table.th>
                    <x-table.th column="client_name" :$sortCol :$sortAsc>{{ __('Applied To') }}</x-table.th>
                    <x-table.th column="type" :$sortCol :$sortAsc>{{ __('Report Name') }}</x-table.th>
                    <x-table.th column="frequency" :$sortCol :$sortAsc>{{ __('Frequency') }}</x-table.th>
                    <x-table.th column="delivery_type" :$sortCol :$sortAsc>{{ __('Delivery Type') }}</x-table.th>
                    <x-table.th>{{ __('SFTP Profile/Email(s)') }}</x-table.th>
                    <x-table.th colspan="2" class="lg:w-1/12">{{ __('Actions') }}</x-table.th>
                </x-table.tr>
            </x-slot>
            <x-slot name="tableBody">
                @forelse ($scheduleExports as $scheduleExport)
                    <x-table.tr>
                        <x-table.td>{{ $scheduleExport->created_at->formatWithTimezone() }}</x-table.td>
                        <x-table.td class="capitalize">
                            @hasrole(Role::SUPERADMIN)
                                <span
                                    @if ($scheduleExport->company?->company_name)
                                        x-tooltip.placement.bottom="@js($scheduleExport->company->company_name)"
                                    @endif
                                >
                                    {{  Str::limit($scheduleExport->company?->company_name, 12) ?? __('All') }}
                                </span>
                            @endhasrole
                            @hasrole(Role::CREDITOR)
                                <span
                                    @if ($scheduleExport->subclient?->subclient_name)
                                        x-tooltip.placement.bottom="@js($scheduleExport->subclient->subclient_name)"
                                    @endif
                                >
                                    {{  Str::limit($scheduleExport->subclient?->subclient_name, 12) ?? __('All') }}
                                </span>
                            @endhasrole
                        </x-table.td>
                        <x-table.td>{{ $scheduleExport->report_type->displayName() }}</x-table.td>
                        <x-table.td>{{ $scheduleExport->frequency->displayName() }}</x-table.td>
                        <x-table.td>
                            {{ $scheduleExport->sftp_connection_id ? __('SFTP') : ('Email') }}
                        </x-table.td>
                        <x-table.td>
                            @if($scheduleExport->sftp_connection_id)
                                {{ $scheduleExport->sftpConnection->name }}
                            @else
                                @foreach ($scheduleExport->emails as $email)
                                    {{ $email }}<br>
                                @endforeach
                            @endif
                        </x-table.td>
                        <x-table.td>
                            <div class="flex items-center space-x-2">
                                <a
                                    wire:navigate
                                    href="{{ auth()->user()->hasRole(Role::SUPERADMIN) ? route('schedule-export.edit', $scheduleExport->id) : route('creditor.schedule-export.edit', $scheduleExport->id) }}"
                                    class="btn flex space-x-1 py-1.5 px-2 text-xs sm:text-sm+ items-center text-white bg-info hover:bg-info-focus focus:bg-info-focus"
                                >
                                    <x-lucide-edit class="size-4.5 sm:size-5" />
                                    <span>{{ __('Edit') }}</span>
                                </a>
                                <x-form.button
                                    wire:click="togglePause({{ $scheduleExport->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="togglePause({{ $scheduleExport->id }})"
                                    type="button"
                                    class="disabled:opacity-50 space-x-1 py-1.5 px-2 text-xs sm:text-sm+"
                                    :variant="$scheduleExport->pause ? 'success' : 'warning'"
                                >
                                    <x-lucide-loader-2
                                        class="size-4.5 sm:size-5 animate-spin"
                                        wire:target="togglePause({{ $scheduleExport->id }})"
                                        wire:loading
                                    />
                                    @if ($scheduleExport->pause)
                                        <x-lucide-play wire:loading.remove wire:target="togglePause({{ $scheduleExport->id }})" class="size-4.5 sm:size-5" />
                                        <span>{{ __('Resume') }}</span>
                                    @else
                                        <x-lucide-pause wire:loading.remove wire:target="togglePause({{ $scheduleExport->id }})" class="size-4.5 sm:size-5" />
                                        <span>{{ __('Pause') }}</span>
                                    @endif
                                </x-form.button>
                                <x-confirm-box
                                    :message="__('Are you sure you want to delete this schedule export?')"
                                    action="delete({{ $scheduleExport->id }})"
                                    :ok-button-label="__('Delete')"
                                >
                                    <x-form.button
                                        type="button"
                                        variant="error"
                                        class="py-1.5 px-2 text-xs sm:text-sm+"
                                    >
                                        <div class="flex space-x-1 items-center">
                                            <x-heroicon-o-trash class="size-4.5 sm:size-5" />
                                            <span>{{ __('Delete') }}</span>
                                        </div>
                                    </x-form.button>
                                </x-confirm-box>
                            </div>
                        </x-table.td>
                    </x-table.tr>
                @empty
                    <x-table.no-items-found :colspan="8" />
                @endforelse
            </x-slot>
        </x-table>
    </div>
    <x-table.per-page :items="$scheduleExports" />
</div>

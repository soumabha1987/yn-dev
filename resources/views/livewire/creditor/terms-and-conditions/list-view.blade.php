<div
    x-on:refresh-list-view.window="$wire.$refresh"
    class="card mt-8"
>
    <div
        @class([
            'flex p-4 sm:items-center gap-4',
            'justify-between' => $termsAndConditions->isNotEmpty(),
            'justify-end' => $termsAndConditions->isEmpty()
        ])
    >
        <x-table.per-page-count :items="$termsAndConditions" />
    </div>

    <div class="min-w-full overflow-auto">
        <x-table>
            <x-slot name="tableHead">
                <x-table.tr>
                    <x-table.th column="created_at" :$sortCol :$sortAsc>{{ __('Date Created') }}</x-table.th>
                    <x-table.th column="name" :$sortCol :$sortAsc>{{ __('Applied To') }}</x-table.th>
                    <x-table.th column="type" :$sortCol :$sortAsc>{{ __('Type') }}</x-table.th>
                    <x-table.th class="w-1/12">{{ __('Actions') }}</x-table.th>
                </x-table.tr>
            </x-slot>
            <x-slot name="tableBody">
                @forelse ($termsAndConditions as $termsAndCondition)
                    <x-table.tr>
                        <x-table.td>
                            {{ $termsAndCondition->created_at->formatWithTimezone() }}
                        </x-table.td>
                        <x-table.td>
                            {{ str($termsAndCondition->subclient->subclient_name ?? __('master T&C required'))->title() }}
                        </x-table.td>
                        <x-table.td>
                            {{ $termsAndCondition->subclient ? __('Sub Account') : __('Master') }}
                        </x-table.td>
                        <x-table.td>
                            <div class="flex space-x-2">
                                <x-form.button
                                    type="button"
                                    wire:click="$parent.edit({{ $termsAndCondition->id }})"
                                    wire:target="$parent.edit({{ $termsAndCondition->id }})"
                                    wire:loading.attr="disabled"
                                    variant="bg-info"
                                    class="text-xs sm:text-sm+ px-3 py-1.5 w-24 btn select-none text-white bg-info hover:bg-info-focus"
                                >
                                    <div class="flex space-x-1 items-center">
                                        <x-heroicon-o-pencil-square class="size-4.5 sm:size-5"/>
                                        <span>{{ __('Edit') }}</span>
                                    </div>
                                </x-form.button>
                                <x-dialog>
                                    <x-dialog.open>
                                        <x-form.button
                                            type="button"
                                            variant="success"
                                            class="text-xs sm:text-sm+ px-3 py-1.5 w-24"
                                        >
                                            <div class="flex space-x-1 items-center">
                                                <x-heroicon-o-eye class="size-4.5 sm:size-5 text-white"/>
                                                <span>{{ __('View') }}</span>
                                            </div>
                                        </x-form.button>
                                    </x-dialog.open>
                                    <x-dialog.panel size="2xl" class="h-96">
                                        <x-slot name="heading">
                                            {{ __('Terms & Conditions') }}
                                        </x-slot>
                                        <div class="prose text-gray-700">
                                            {!! $termsAndCondition->content !!}
                                        </div>
                                        <x-slot name="footer" class="mt-3">
                                            <x-dialog.close>
                                                <x-form.default-button
                                                    type="button"
                                                    class="mt-3"
                                                >
                                                    {{ __('Close') }}
                                                </x-form.default-button>
                                            </x-dialog.close>
                                        </x-slot>
                                    </x-dialog.panel>
                                </x-dialog>
                                @if ($termsAndCondition->subclient_id !== null)
                                    <x-confirm-box
                                        :message="__('Are you sure you want to delete this terms & conditions?')"
                                        :ok-button-label="__('Delete')"
                                        action="delete({{ $termsAndCondition->id }})"
                                    >
                                        <x-form.button
                                            class="text-xs sm:text-sm+ px-3 py-1.5 w-24"
                                            type="button"
                                            variant="error"
                                        >
                                            <div class="flex space-x-1 items-center">
                                                <x-heroicon-o-trash class="size-4.5 sm:size-5"/>
                                                <span>{{ __('Delete') }}</span>
                                            </div>
                                        </x-form.button>
                                    </x-confirm-box>
                                @endif
                            </div>
                        </x-table.td>
                    </x-table.tr>
                @empty
                    <x-table.no-items-found :colspan="4"/>
                @endforelse
            </x-slot>
        </x-table>
    </div>
    <x-table.per-page :items="$termsAndConditions"/>
</div>

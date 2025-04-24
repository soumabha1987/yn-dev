<div>
    @if ($adminConfigurations->isNotEmpty())
        <div class="card md:w-1/2">
            <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                <x-table>
                    <x-slot name="tableHead">
                        <x-table.tr>
                            <x-table.th class="rounded-tl-lg border-t-0">{{ __('Name') }}</x-table.th>
                            <x-table.th class="text-center border-t-0 rounded-tr-lg">{{ __('Value') }}</x-table.th>
                        </x-table.tr>
                    </x-slot>
                    <x-slot name="tableBody">
                        @foreach ($adminConfigurations as $adminConfiguration)
                            <x-table.tr @class(['border-none' => $loop->last])>
                                <x-table.td>{{ $adminConfiguration->name }}</x-table.td>
                                <x-table.td class="text-center">
                                    <x-form.input-field
                                        type="text"
                                        wire:model="adminConfigurationValues.{{ $adminConfiguration->id }}"
                                        wire:blur="updateConfiguration({{ $adminConfiguration->id }})"
                                        :name="$adminConfiguration->slug->displayName()"
                                        :placeholder="__('Enter Value')"
                                        class="mb-1.5"
                                    />
                                </x-table.td>
                            </x-table.tr>
                        @endforeach
                    </x-slot>
                </x-table>
            </div>
        </div>
    @endif

    @if ($featureFlags->isNotEmpty())
        <div class="card my-8 md:w-1/2">
            <div class="flex items-baseline space-x-4 py-3 px-4">
                <h2 class="text-md text-black font-semibold lg:text-lg">
                    {{ __('Feature Flags') }}
                </h2>
            </div>

            <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                <x-table>
                    <x-slot name="tableHead">
                        <x-table.tr>
                            <x-table.th>{{ __('Feature field name') }}</x-table.th>
                            <x-table.th colspan="2">{{ __('Status') }}</x-table.th>
                        </x-table.tr>
                    </x-slot>
                    <x-slot name="tableBody">
                        @forelse ($featureFlags as $key => $featureFlag)
                            <x-table.tr @class(['border-none' => $loop->last])>
                                <x-table.td>{{ $featureFlag->feature_name->displayName() }}</x-table.td>
                                <x-table.td class="border-r-0">
                                    <x-form.switch
                                        wire:model="ids"
                                        name="ids"
                                        wire:click="updateStatus({{ $featureFlag->id }})"
                                        value="{{ $featureFlag->id }}"
                                    />
                                </x-table.td>
                                <x-table.td class="w-1/5">
                                    <div
                                        x-data="{ displayMessage: false, message: null }"
                                        x-on:updated-status-{{ $featureFlag->id }}.window="() => {
                                            displayMessage = true
                                            message = $event.detail[0]
                                            setTimeout(function () {
                                                displayMessage = false
                                            }, 3000)
                                        }"
                                    >
                                    <span
                                        x-show="displayMessage"
                                        x-transition:enter="transition ease-out duration-300"
                                        x-transition:enter-start="opacity-0 scale-90"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-300"
                                        x-transition:leave-start="opacity-100 scale-100"
                                        x-transition:leave-end="opacity-0 scale-90"
                                        class="text-success"
                                        x-text="message + '!'"
                                    ></span>
                                    </div>
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.no-items-found :colspan="2" />
                        @endforelse
                    </x-slot>
                </x-table>
            </div>
        </div>
    @endif
</div>

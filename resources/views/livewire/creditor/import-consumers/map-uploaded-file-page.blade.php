@use('App\Enums\ConsumerFields')
@use('App\Enums\State')
@use('App\Enums\FileUploadHistoryDateFormat')

<div>
    <div
        x-data="mapUploadedFile"
        class="card py-3 px-5 lg:w-3/4"
    >
        <div class="flex-col items-baseline py-3">
            <h2 class="text-md text-black font-semibold lg:text-lg">
                {{ __('Map Your Consumer Import File Header') }}
            </h2>
            <span class="text-xs text-slate-700">{{ __('CSV Any to Any Header Mapping') }}</span>
            <h3 class="text-black mt-2 text-lg">
                <span class="font-bold">{{__('Header Name :')}}</span>
                <span>{{ str($csvHeader->name)->title() }}</span>
            </h3>
        </div>

        <div class="my-3">
            <div class="flex flex-col items-center justify-between w-full sm:flex-row gap-2 mb-2">
                <div class="my-4 w-full sm:w-1/3">
                    <x-form.select
                        wire:model="date_format"
                        :options="FileUploadHistoryDateFormat::displaySelectionBox()"
                        name="date_format"
                        :label="__('Select date format of uploaded file')"
                        required
                        :placeholder="__('Date Format')"
                    />
                    <small class="text-xs+">{{ __('[ Date format should be same in uploaded file ]') }}</small>
                </div>
                <div class="flex flex-col sm:flex-row sm:flex-wrap md:flex-nowrap sm:justify-end items-stretch sm:items-center gap-2 mt-1.5 w-full sm:w-auto">
                    <x-form.button
                        variant="primary"
                        type="button"
                        x-on:click="storeMappedHeaders"
                        class="text-nowrap"
                    >
                        {{ __('Save My Header Profile') }}
                    </x-form.button>

                    <x-form.button
                        variant="warning"
                        type="button"
                        wire:click="finishLater"
                        class="text-nowrap"
                    >
                        {{ __('Finish Later') }}
                    </x-form.button>

                    <x-form.button
                        variant="error"
                        type="button"
                        wire:click="deleteHeader"
                        class="text-nowrap"
                    >
                        {{ __('Exit/ Don\'t Save') }}
                    </x-form.button>
                </div>
            </div>

            <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                <x-table class="text-sm+">
                    <x-slot name="tableHead" class="border-x">
                        <x-table.tr>
                            <x-table.th>
                                {{ __('YouNegotiate Header Fields') }}
                            </x-table.th>
                            <x-table.th class="w-60">
                                {{ __('Your Data Field') }}
                            </x-table.th>
                        </x-table.tr>
                    </x-slot>
                    @error('mappedHeaders')
                        {{ $this->error($message) }}
                    @enderror
                    <x-slot name="tableBody" class="border-x">
                        @foreach (ConsumerFields::displaySelectionBox() as $name => $consumerField)
                            <x-table.tr>
                                <x-table.td class="!py-1">
                                    @if (collect($consumerField->validate())->contains('required'))
                                        <span class="font-semibold">
                                            {{ $name }}<span class="text-error text-base font-semibold">*</span>
                                        </span>
                                    @else
                                        {{ $name }}
                                    @endif
                                </x-table.td>
                                <x-table.td class="!py-1">
                                    <select
                                        class="my-1.5 w-full sm:w-80"
                                        x-init="new Tom($el, { sortField: { direction: 'asc' } })"
                                        wire:model="mappedHeaders.{{ $consumerField->value }}"
                                        x-on:change="updateMappedHeaders"
                                    >
                                        <option value="">{{ __('Select Your Header') }}</option>
                                        @foreach ($csvHeader->import_headers as $header)
                                            <option value="{{ $header }}">{{ $header }}</option>
                                        @endforeach
                                    </select>
                                    @error('mappedHeaders.'.$consumerField->value)
                                        <p class="text-error">{{ $message }}</p>
                                    @enderror
                                </x-table.td>
                            </x-table.tr>
                        @endforeach
                    </x-slot>
                </x-table>
            </div>
        </div>
    </div>

    @script
        <script>
            Alpine.data('mapUploadedFile', () => ({
                mappedHeaders: {},
                init () {
                    this.mappedHeaders = this.$wire.mappedHeaders
                    window.addEventListener('popstate', () => this.handlePopState())
                },
                updateMappedHeaders () {
                    this.mappedHeaders = {}
                    this.mappedHeaders = this.$wire.mappedHeaders
                },
                storeMappedHeaders () {
                    if (Object.values(this.mappedHeaders).length === 0) {
                        this.$notification({ text: '{{ __("Oops. you\'re missing some required fields. Please finish mapping:)") }}', variant: 'error' })
                        return
                    }

                    if(this.$wire.requiredFields.every(field =>
                        Object.entries(this.mappedHeaders).some(mappedHeader =>
                            mappedHeader[0] === field && mappedHeader[1] !== ''
                        )
                    )) {
                        this.$wire.storeMappedHeaders()
                    } else {
                        this.$notification({ text: '{{ __("Oops. you\'re missing some required fields. Please finish mapping:)") }}', variant: 'error' })
                    }
                },
                handlePopState() {
                    let $wire = Livewire.getByName('creditor.import-consumers.upload-file-page')
                    $wire[0].$refresh
                },
                destroy() {
                    window.removeEventListener('popstate', () => this.handlePopState())
                }
            }))
        </script>
    @endscript
</div>

@use('App\Enums\State')

<x-consumer.profile-layout route-name="profile">
    <div>
        <style>
            .ts-control {
                border-radius: 0.25rem
            }
        </style>
        <form method="POST" wire:submit="updateProfile" autocomplete="off">
            <div class="card">
                <div class="flex flex-col items-center space-y-4 border-b border-slate-200 p-4 sm:flex-row sm:justify-between sm:space-y-0 sm:px-5">
                    <h2 class="text-lg font-semibold tracking-wide text-black">
                        {{ __('My Information') }}
                    </h2>

                    {{-- Desktop view --}}
                    <div class="hidden sm:flex justify-center space-x-2">
                        <a
                            wire:navigate
                            href="{{ route('consumer.account') }}"
                            class="btn min-w-[7rem] rounded-full border border-slate-300 font-medium text-slate-700 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80"
                        >
                           {{ __('Cancel') }}
                        </a>
                        <x-form.button
                            type="submit"
                            variant="primary"
                            class="min-w-[7rem] rounded-full font-medium disabled:opacity-50"
                            wire:target="updateProfile"
                            wire:loading.attr="disabled"
                        >
                            <x-lucide-loader-2
                                wire:loading
                                wire:target="updateProfile"
                                class="size-5 animate-spin mr-2"
                            />
                            {{ __('Save') }}
                        </x-form.button>
                    </div>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="font-semibold text-black">{{ __('First name') }}</span>
                            <span class="relative mt-1.5 flex">
                                <input
                                    wire:model="form.first_name"
                                    placeholder="{{ __('First Name') }}"
                                    @class([
                                        'form-input peer w-full rounded border bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary',
                                        'border-error' => $errors->has('form.first_name'),
                                        'border-slate-300' => $errors->missing('form.first_name'),
                                    ])
                                    autocomplete="off"
                                >
                                <span @class([
                                    'pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary',
                                    'text-error' => $errors->has('form.first_name'),
                                    'text-slate-400' => $errors->missing('form.last_name'),
                                ])>
                                    <x-lucide-folder-pen class="size-5" />
                                </span>
                            </span>
                            @error('form.first_name')
                                <div class="mt-1">
                                    <span class="text-error">
                                        {{ $message }}
                                    </span>
                                </div>
                            @enderror
                        </label>

                        <label class="block">
                            <span class="font-semibold text-black">{{ __('Last name') }}</span>
                            <span
                                class="relative mt-1.5 flex"
                                x-tooltip.placement.bottom="@js(__('This field is not editable'))"
                            >
                                <input
                                    wire:ignore
                                    x-bind:value="$wire.form.last_name"
                                    class="form-input peer w-full rounded border border-slate-300 bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary opacity-50"
                                    disabled
                                    readonly
                                    autocomplete="off"
                                >
                                <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary">
                                    <x-lucide-user-round class="size-5" />
                                </span>
                            </span>
                        </label>

                        <label class="block">
                            <span class="font-semibold text-black">{{ __('Birth date') }}</span>
                            <span
                                class="relative mt-1.5 flex"
                                x-tooltip.placement.bottom="@js(__('This field is not editable'))"
                            >
                                <input
                                    wire:ignore
                                    x-bind:value="$wire.form.dob"
                                    class="form-input peer w-full rounded border border-slate-300 bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary opacity-50"
                                    disabled
                                    readonly
                                    autocomplete="off"
                                >
                                <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary">
                                    <x-lucide-cake class="size-5" />
                                </span>
                            </span>
                        </label>

                        <label class="block">
                            <span class="font-semibold text-black">{{ __('Last four digits of SSN') }}</span>
                            <span
                                x-data="{ textType: false }"
                                class="relative mt-1.5 flex"
                                x-tooltip.placement.bottom="@js(__('This field is not editable'))"
                            >
                                <input
                                    :type="textType ? 'text' : 'password'"
                                    wire:ignore
                                    x-bind:value="$wire.form.last_four_ssn"
                                    class="form-input peer w-full rounded border border-slate-300 bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary opacity-50"
                                    disabled
                                    readonly
                                    autocomplete="off"
                                >
                                <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary">
                                    <x-lucide-square-asterisk class="size-5" />
                                </span>
                                <span
                                    @click="textType = !textType"
                                    class="cursor-pointer absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary"
                                >
                                    <x-heroicon-o-eye x-show="textType" class="size-4.5" />
                                    <x-heroicon-o-eye-slash x-show="!textType" class="size-4.5" />
                                </span>
                            </span>
                        </label>
                    </div>

                    <div class="flex flex-col items-center space-y-4 border-b border-slate-200 p-4 sm:p-0 my-5 sm:flex-row sm:justify-between sm:space-y-0">
                        <h2 class="text-lg font-medium text-black tracking-wide">
                            {{ __('Current Address') }}
                        </h2>
                    </div>
                    <div
                        x-data="smartyAddress"
                        x-ref="outsideAddress"
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2 text-black"
                    >
                        <div class="relative">
                            <label
                                class="block"
                                x-on:click.outside="displaySuggestionsBox = false"
                            >
                                <span class="font-semibold">{{ __('Street') }}</span>
                                <span class="relative mt-1.5 flex">
                                    <input
                                        type="text"
                                        wire:model="form.address"
                                        class="form-input peer w-full rounded border bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary"
                                        x-ref="address"
                                        placeholder="{{ __('Enter street') }}"
                                        x-on:input="fetchAddresses('')"
                                        x-on:focusin="displaySuggestionsBox = true"
                                        x-on:keydown.down="focusAddress('')"
                                        autocomplete="off"
                                    >
                                    <span @class([
                                        'pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary',
                                        'text-error' => $errors->has('form.address'),
                                        'text-slate-400' => $errors->missing('form.address'),
                                    ])>
                                        <x-lucide-book-user class="size-5" />
                                    </span>
                                </span>
                                <div
                                    x-show="displaySuggestionsBox"
                                    class="absolute rounded-md min-w-full bg-white z-20 max-h-44 overflow-y-auto scrollbar-sm"
                                    :class="displaySuggestionsBox && addresses?.length > 0 && isClickableAddress && 'border border-slate-150 py-1.5'"
                                >
                                    <template x-if="!isClickableAddress">
                                        <div class="flex h-40 justify-between rounded-md gap-x-2 border border-slate-150 py-1.5 items-center cursor-pointer px-3 font-medium tracking-wide outline-none transition-all">
                                            <x-lucide-loader-2 class="animate-spin size-10 mx-auto" />
                                        </div>
                                    </template>
                                    <template x-if="isClickableAddress && displaySuggestionsBox">
                                        <template x-for="(suggestion, index) in addresses">
                                            <div
                                                class="flex h-8 justify-between gap-x-2 items-center cursor-pointer px-3 py-1 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800"
                                                x-bind:class="!isClickableAddress && 'opacity-50'"
                                                x-on:click="selectAddress(suggestion)"
                                                x-bind:id="'address-' + index"
                                                tabindex="-1"
                                                x-on:keydown.down="focusAddress(index + 1)"
                                                x-on:keydown.up="focusAddress(index - 1)"
                                                x-on:keydown.enter="selectAddress(suggestion)"
                                            >
                                                <div
                                                    x-show="isClickableAddress"
                                                    class="flex gap-x-4 w-full justify-between items-center focus:bg-slate-100 focus:text-slate-800 outline-none"
                                                >
                                                    <span
                                                        class="line-clamp-1"
                                                        x-text="suggestion.street_line + (suggestion.secondary ? ', ' + suggestion.secondary : '') + ', ' + suggestion.city + ', ' + suggestion.state + ', ' + suggestion.zipcode"
                                                        x-bind:class="suggestion.entries > 1 && 'w-3/5'"
                                                    ></span>
                                                    <template x-if="suggestion.entries > 1">
                                                        <div class="flex gap-x-1 font-extrabold text-nowrap">
                                                            <span class="text-primary" x-text="'+' + suggestion.entries + ' addresses'"></span>
                                                            <x-lucide-chevron-right class="size-5 text-primary" />
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </template>
                                </div>
                                @error('form.address')
                                    <div class="mt-1">
                                        <span class="text-error">
                                            {{ $message }}
                                        </span>
                                    </div>
                                @enderror
                            </label>
                        </div>

                        <label class="block">
                            <span class="font-semibold">{{ __('City') }}</span>
                            <span class="relative mt-1.5 flex">
                                <input
                                    type="text"
                                    wire:model="form.city"
                                    @class([
                                        'form-input peer w-full rounded border bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary',
                                        'border-error' => $errors->has('form.city'),
                                        'border-slate-300' => $errors->missing('form.city'),
                                    ])
                                    placeholder="{{ __('Enter city') }}"
                                    autocomplete="off"
                                >
                                <span @class([
                                    'pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary',
                                    'text-error' => $errors->has('form.city'),
                                    'text-slate-400' => $errors->missing('form.city'),
                                ])>
                                    <x-lucide-land-plot class="size-5" />
                                </span>
                            </span>
                            @error('form.city')
                                <div class="mt-1">
                                    <span class="text-error">
                                        {{ $message }}
                                    </span>
                                </div>
                            @enderror
                        </label>

                        <label class="block">
                            <span class="font-semibold">{{ __('State') }}</span>
                            <span class="relative mt-1.5 flex">
                                <select
                                    wire:ignore
                                    x-ref="state"
                                    x-bind="stateEffect"
                                    @class([
                                        'w-full rounded bg-transparent placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary',
                                        'border-error' => $errors->has('form.state'),
                                        'border-slate-300' => $errors->missing('form.state'),
                                    ])
                                    placeholder="{{ __('Select state') }}"
                                >
                                    <option value="">{{ __('Select state') }}</option>
                                    @foreach (State::displaySelectionBox() as $key => $state)
                                        <option value="{{ $key }}">{{ $state }}</option>
                                    @endforeach
                                </select>
                            </span>
                            @error('form.state')
                                <div class="mt-1">
                                    <span class="text-error">
                                        {{ $message }}
                                    </span>
                                </div>
                            @enderror
                        </label>

                        <label class="block">
                            <span class="font-semibold">{{ __('Zip / Postal Code') }}</span>
                            <span class="relative mt-1.5 flex">
                                <input
                                    type="text"
                                    wire:model="form.zip"
                                    @class([
                                        'form-input peer w-full rounded border bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary',
                                        'border-error' => $errors->has('form.zip'),
                                        'border-slate-300' => $errors->missing('form.zip'),
                                    ])
                                    placeholder="{{ __('Zip / Postal code') }}"
                                    autocomplete="off"
                                >
                                <span @class([
                                    'pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary',
                                    'text-error' => $errors->has('form.zip'),
                                    'text-slate-400' => $errors->missing('form.zip'),
                                ])>
                                    <x-lucide-map-pin class="size-5" />
                                </span>
                            </span>
                            @error('form.zip')
                                <div class="mt-1">
                                    <span class="text-error">
                                        {{ $message }}
                                    </span>
                                </div>
                            @enderror
                        </label>
                    </div>
                </div>

                {{-- mobile view --}}
                <div class="flex sm:hidden justify-center space-x-2 mb-4">
                    <a
                        wire:navigate
                        href="{{ route('consumer.account') }}"
                        class="btn min-w-[7rem] rounded-full border border-slate-300 font-medium text-slate-700 hover:bg-slate-150 focus:bg-slate-150 active:bg-slate-150/80"
                    >
                       {{ __('Cancel') }}
                    </a>
                    <x-form.button
                        type="submit"
                        variant="primary"
                        class="min-w-[7rem] rounded-full font-medium disabled:opacity-50"
                        wire:loading.attr="disabled"
                        wire:target="updateProfile"
                    >
                        <x-lucide-loader-2
                            wire:loading
                            wire:target="updateProfile"
                            class="size-5 animate-spin mr-2"
                        />
                        {{ __('Save') }}
                    </x-form.button>
                </div>
            </div>
        </form>

        @script
            <script>
                Alpine.data('smartyAddress', () => ({
                    displaySuggestionsBox: false,
                    isClickableAddress: true,
                    addresses: [],
                    suggestionEntries: 5,
                    init () {
                        new Tom(this.$refs.state, { sortField: { field: 'text', direction: 'asc' } })
                        this.$refs.state.tomselect.setValue(this.$wire.form.state)
                        this.$refs.state.tomselect.on('change', value => this.$wire.form.state = value)
                    },
                    stateEffect: {
                        ['x-effect']() {
                            if (this.$refs.state.tomselect === undefined) {
                                this.init()
                            }
                        },
                    },
                    async fetchAddresses (selected = '') {
                        this.$event.preventDefault()

                        this.isClickableAddress = false
                        if (this.$el.value === '' || this.$el.value === null) {
                            this.displaySuggestionsBox = false
                            this.isClickableAddress = true
                            return
                        }

                        const baseUrl = 'https://us-autocomplete-pro.api.smartystreets.com/lookup'

                        const queryParams = {
                            key: @js(config('services.smarty.key')),
                            max_results: 5,
                            'search': this.$el.value,
                            'selected': selected,
                        }

                        const queryString = new URLSearchParams(queryParams).toString()

                        const url = `${baseUrl}?${queryString}`

                        var response = fetch(url, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                        })
                        .then(response => response.json())
                        .then(response => response)

                        response = await response

                        this.addresses = response.suggestions
                        this.suggestionEntries = this.addresses?.length
                        this.isClickableAddress = true
                        this.displaySuggestionsBox = true
                    },
                    async selectAddress(suggestion) {
                        this.suggestionEntries = 5

                        this.$event.preventDefault()

                        if (! this.isClickableAddress) return

                        if (suggestion.entries > 1) {
                            let selected = suggestion.street_line + ' ' + suggestion.secondary +  ' ' + '(' + suggestion.entries + ')' + ' ' + suggestion.city + ' ' + suggestion.state + ' ' + suggestion.zipcode
                            this.isClickableAddress = false
                            this.suggestionEntries = suggestion.entries
                            this.fetchAddresses(selected)
                            this.$refs.address.focus()
                            return
                        }

                        this.$wire.form.address = suggestion.street_line + ' ' + suggestion.secondary
                        this.$wire.form.state = suggestion.state
                        this.$refs.state.tomselect.setValue(suggestion.state)
                        this.$wire.form.city = suggestion.city
                        this.$wire.form.zip = suggestion.zipcode
                        this.displaySuggestionsBox = false
                        this.$refs.outsideAddress.click()
                    },
                    focusAddress(index = '') {
                        this.$event.preventDefault()
                        const numOfAddresses = this.suggestionEntries

                        if (index === '') {
                            document.getElementById('address-0')?.focus()
                            return
                        }

                        index = parseInt(index)

                        let nextIndex = index % numOfAddresses

                        if (nextIndex === -1) nextIndex = numOfAddresses - 1

                        document.getElementById(`address-${nextIndex}`)?.focus()
                    }
                }))
            </script>
        @endscript
    </div>
</x-consumer.profile-layout>

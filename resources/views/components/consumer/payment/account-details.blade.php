@use('App\Enums\State')
@props(['isDisplayName' => false])

<div>
    <h2 class="text-lg font-medium tracking-wide text-slate-700">
        {{ __('Billing Details') }}
    </h2>
    <div class="my-2 h-px bg-slate-200"></div>
</div>

<div
    x-data="accountDetails"
    class="grid grid-cols-1 mt-3 gap-4 lg:grid-cols-2"
>
    <style>
        .ts-control {
            border-color: #94a3b8;
        }
    </style>
    @if ($isDisplayName)
        <label class="block">
            <span class="font-semibold tracking-wide text-black text-sm lg:text-base">
                {{ __('First Name') }}<span class="text-xs font-light">{{ __(' (As on Card/Bank Account)') }}</span><span class="text-error">*</span>
            </span>
            <span class="relative mt-1.5 flex">
                <input
                    type="text"
                    wire:model="form.first_name"
                    class="form-input peer w-full rounded-lg border border-slate-400 bg-transparent px-3 py-2 placeholder:text-slate-400 focus:border-accent"
                    placeholder="{{ __('First Name') }}"
                    required
                    autocomplete="off"
                >
            </span>
            @error('form.first_name')
                <div class="mt-1">
                    <span class="text-error text-sm+">{{ $message }}</span>
                </div>
            @enderror
        </label>
        <label class="block">
            <span class="font-semibold tracking-wide text-black text-sm lg:text-base">
                {{ __('Last Name') }}<span class="text-error">*</span>
            </span>
            <span class="relative mt-1.5 flex">
                <input
                    type="text"
                    wire:model="form.last_name"
                    class="form-input peer w-full rounded-lg border border-slate-400 bg-transparent px-3 py-2 placeholder:text-slate-400 focus:border-accent"
                    placeholder="{{ __('Last Name') }}"
                    required
                >
            </span>
            @error('form.last_name')
                <div class="mt-1">
                    <span class="text-error text-sm+">{{ $message }}</span>
                </div>
            @enderror
        </label>
    @endif
    <label
        class="block relative"
        x-ref="outsideAddress"
        x-on:click.outside="displaySuggestionsBox = false"
    >
        <span class="font-semibold tracking-wide text-black text-sm lg:text-base">
            {{ __('Street Address') }}<span class="text-error">*</span>
        </span>
        <span class="mt-1.5 flex">
            <input
                type="text"
                wire:model="form.address"
                class="form-input peer w-full rounded-lg border border-slate-400 bg-transparent px-3 py-2 placeholder:text-slate-400 focus:border-accent"
                x-ref="address"
                placeholder="{{ __('Street Address') }}"
                x-on:input="fetchAddresses('')"
                x-on:focusin="displaySuggestionsBox = true"
                x-on:keydown.down="focusAddress('')"
                required
            >
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
                        class="flex h-8 justify-between gap-x-2 items-center cursor-pointer px-3 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800"
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
                <span class="text-error text-sm+">{{ $message }}</span>
            </div>
        @enderror
    </label>

    <label class="block">
        <span class="font-semibold tracking-wide text-black text-sm lg:text-base">
            {{ __('City') }}<span class="text-error">*</span>
        </span>
        <span class="relative mt-1.5 flex">
            <input
                type="text"
                wire:model="form.city"
                class="form-input peer w-full rounded-lg border border-slate-400 bg-transparent px-3 py-2 placeholder:text-slate-400 focus:border-accent"
                placeholder="{{ __('City') }}"
                required
            >
        </span>
        @error('form.city')
            <div class="mt-1">
                <span class="text-error text-sm+">{{ $message }}</span>
            </div>
        @enderror
    </label>

    <label class="block">
        <span class="font-semibold tracking-wide text-black text-sm lg:text-base">
            {{ __('State') }}<span class="text-error">*</span>
        </span>
        <span class="relative mt-1.5 flex">
            <select
                wire:ignore
                x-ref="state"
                x-bind="stateEffect"
                class="w-full rounded !border-slate-400 placeholder:text-slate-400 hover:border-black focus:border-primary"
                placeholder="{{ __('State') }}"
            >
                <option value="">{{ __('State') }}</option>
                @foreach (State::displaySelectionBox() as $key => $state)
                    <option value="{{ $key }}">{{ $state }}</option>
                @endforeach
            </select>
        </span>
        @error('form.state')
            <div class="mt-1">
                <span class="text-error text-sm+">{{ $message }}</span>
            </div>
        @enderror
    </label>

    <label class="block">
        <span class="font-semibold tracking-wide text-black text-sm lg:text-base">
            {{ __('Zip') }}<span class="text-error">*</span>
        </span>
        <span class="relative mt-1.5 flex">
            <input
                type="text"
                wire:model="form.zip"
                class="form-input peer w-full rounded-lg border border-slate-400 bg-transparent px-3 py-2 placeholder:text-slate-400 focus:border-accent"
                placeholder="{{ __('Zip') }}"
                required
            >
        </span>
        @error('form.zip')
            <div class="mt-1">
                <span class="text-error text-sm+">{{ $message }}</span>
            </div>
        @enderror
    </label>
</div>

@script
    <script>
        Alpine.data('accountDetails', () => ({
            displaySuggestionsBox: false,
            isClickableAddress: true,
            addresses: [],
            suggesstionEntries: 5,
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
                this.suggesstionEntries = this.addresses?.length
                this.isClickableAddress = true
                this.displaySuggestionsBox = true
            },
            async selectAddress(suggestion) {
                this.suggesstionEntries = 5

                this.$event.preventDefault()

                if (! this.isClickableAddress) return

                if (suggestion.entries > 1) {
                    let selected = suggestion.street_line + ' ' + suggestion.secondary +  ' ' + '(' + suggestion.entries + ')' + ' ' + suggestion.city + ' ' + suggestion.state + ' ' + suggestion.zipcode
                    this.isClickableAddress = false
                    this.suggesstionEntries = suggestion.entries
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
            },
            focusAddress(index = '') {
                this.$event.preventDefault()
                const numOfAddresses = this.suggesstionEntries

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

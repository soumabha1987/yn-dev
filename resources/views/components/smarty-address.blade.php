@props([
    'blockTitle' => __('Address'),
    'placeholder' => [
        'address' => __('Enter Address'),
        'city' => __('Enter City'),
        'state' => __('State'),
        'zip' => __('Enter Zip Code'),
    ],
    'label' => [
        'address' => __('Address'),
        'city' => __('City'),
        'state' => __('State'),
        'zip' => __('Zip Code'),
    ],
    'wireElement' => [
        'address' => 'form.address',
        'city' => 'form.city',
        'state' => 'form.state',
        'zip' => 'form.zip',
    ],
    'required' => false,
])
@use('App\Enums\State')

<div
    x-data="smartyAddress"
    x-on:fetch-addresses.window="fetchAddresses($event.detail.search, $event.detail.selected)"
>
    <h2 class="text-lg font-medium tracking-wide text-slate-700">
        {{ $blockTitle }}
    </h2>
    <hr class="mt-2 h-px bg-slate-200">
    <div {{ $attributes->merge(['class' => 'grid grid-cols-1 md:grid-cols-3 gap-x-3 mb-4 mt-2']) }}>
        <div
            class="my-2 relative"
            x-on:click.outside="displaySuggestionsBox = false"
        >
            <x-form.input-field
                type="text"
                :label="$label['address']"
                :name="$wireElement['address']"
                :placeholder="$placeholder['address']"
                wire:model="{{ $wireElement['address'] }}"
                x-modelable="address"
                class="peer w-full rounded-full border bg-transparent placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary"
                x-ref="address"
                x-on:input.debounce.500="$dispatch('fetch-addresses', { search: $el.value, selected: '' })"
                x-on:focusin="displaySuggestionsBox = true"
                :$required
            />
            <template x-if="typeof errorMessages !== 'undefined' && errorMessages['address']">
                <div class="mt-0.5">
                    <span class="text-error text-sm+" x-text="errorMessages['address']"></span>
                </div>
            </template>
            <div
                x-show="displaySuggestionsBox"
                class="absolute rounded-md z-20 bg-white min-w-full max-h-44 overflow-y-auto scrollbar-sm"
                :class="displaySuggestionsBox && addresses?.length > 0 && isClickableAddress && 'border border-slate-150 py-1.5'"
            >
                <template x-if="!isClickableAddress">
                    <div class="flex h-40 justify-between rounded-md gap-x-2 border border-slate-150 py-1.5 items-center cursor-pointer px-3 font-medium tracking-wide outline-none transition-all">
                        <x-lucide-loader-2 class="animate-spin size-10 mx-auto" />
                    </div>
                </template>
                <template x-if="isClickableAddress && displaySuggestionsBox === true">
                    <template x-for="suggestion in addresses">
                        <div
                            class="flex h-8 justify-between gap-x-2 items-center cursor-pointer px-3 py-1 font-medium tracking-wide outline-none transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800"
                            x-bind:class="! isClickableAddress && 'opacity-50'"
                            x-on:click="() => {
                                if (! isClickableAddress) return
                                if (suggestion.entries > 1) {
                                    $dispatch('fetch-addresses', {
                                        search: $refs.address.value,
                                        selected: suggestion.street_line + ' ' + suggestion.secondary +  ' ' +
                                        '(' + suggestion.entries + ')' + ' ' + suggestion.city +
                                        ' ' + suggestion.state + ' ' + suggestion.zipcode
                                    })
                                    return
                                }

                                $refs.address.value = suggestion.street_line + ' ' + suggestion.secondary
                                address = suggestion.street_line + ' ' + suggestion.secondary
                                state = suggestion.state
                                city = suggestion.city
                                zip = suggestion.zipcode
                                displaySuggestionsBox = false
                            }"
                        >
                            <div
                                x-show="isClickableAddress"
                                class="flex w-full justify-between items-center gap-x-4"
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
        </div>
        <div class="my-2">
            <x-form.input-field
                type="text"
                :label="$label['city']"
                :name="$wireElement['city']"
                :placeholder="$placeholder['city']"
                wire:model="{{ $wireElement['city'] }}"
                x-modelable="city"
                x-on:input="city = $el.value"
                class="w-full"
                :$required
            />
            <template x-if="typeof errorMessages !== 'undefined' && errorMessages['city']">
                <div class="mt-0.5">
                    <span class="text-error text-sm+" x-text="errorMessages['city']"></span>
                </div>
            </template>
        </div>
        <div class="my-2">
            <x-form.select
                :options="State::displaySelectionBox()"
                :label="$label['state']"
                :name="$wireElement['state']"
                :placeholder="$placeholder['state']"
                wire:model="{{ $wireElement['state'] }}"
                x-modelable="state"
                x-on:change="state = $el.value"
                class="w-full"
                :$required
            />
            <template x-if="typeof errorMessages !== 'undefined' && errorMessages['state']">
                <div class="mt-0.5">
                    <span class="text-error text-sm+" x-text="errorMessages['state']"></span>
                </div>
            </template>
        </div>
        <div class="my-2">
            <x-form.input-field
                type="text"
                :label="$label['zip']"
                :name="$wireElement['zip']"
                :placeholder="$placeholder['zip']"
                wire:model="{{ $wireElement['zip'] }}"
                x-modelable="zip"
                x-on:input="zip = $el.value"
                class="w-full"
                maxlength="5"
                :$required
            />
            <template x-if="typeof errorMessages !== 'undefined' && errorMessages['zip']">
                <div class="mt-0.5">
                    <span class="text-error text-sm+" x-text="errorMessages['zip']"></span>
                </div>
            </template>
        </div>
    </div>
    @script
        <script>
            Alpine.data('smartyAddress', () => ({
                displaySuggestionsBox: false,
                isClickableAddress: true,
                addresses: [],
                address: '',
                state: '',
                city: '',
                zip: '',

                async fetchAddresses(search, selected = '') {
                    this.$refs.address.value = search
                    this.address = search
                    this.isClickableAddress = false
                    if (search === '' || search === null) {
                        this.displaySuggestionsBox = false
                        this.isClickableAddress = true
                        return
                    }

                    const baseUrl = 'https://us-autocomplete-pro.api.smartystreets.com/lookup'

                    const queryParams = {
                        key: @js(config('services.smarty.key')),
                        max_results: 5,
                        'search': search,
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
                    this.displaySuggestionsBox = true
                    this.isClickableAddress = true
                }
            }))
        </script>
    @endscript
</div>

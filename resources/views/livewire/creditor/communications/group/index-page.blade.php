@use('App\Enums\GroupConsumerState')
@use('App\Enums\GroupCustomRules')
@use('App\Enums\State')

<div>
    <div class="card px-4 pb-4 sm:px-5">
        <form
            method="POST"
            autocomplete="off"
            wire:submit="createOrUpdate"
        >
            <div class="grid grid-cols-1 sm:grid-cols-2 sm:gap-4">
                <div class="mt-5">
                    <x-form.input-field
                        type="text"
                        wire:model="form.name"
                        name="form.name"
                        :label="__('Group Name')"
                        :placeholder="__('Enter group name')"
                        class="w-full"
                        required
                    />
                </div>
                <div class="mt-5">
                    <x-form.select
                        wire:model="form.consumer_state"
                        :label="__('Select Consumer Status')"
                        :options="GroupConsumerState::displaySelectionBox()"
                        name="form.consumer_state"
                        :placeholder="__('Consumer state')"
                        required
                    />
                </div>
            </div>
            <div
                x-data="customRules"
                class="mt-5"
                x-on:update-custom-rules.window="() => editGroup()"
                x-on:reset-custom-rules.window="() => resetCustomRules()"
            >
                <div class="mb-1 sm:mb-2 flex flex-col sm:flex-row sm:items-center gap-2">
                    <span class="font-semibold tracking-wide text-black lg:text-md">
                        {{ __('Custom Rules to Select Customers') }}
                    </span>
                    <template x-if="rules.length !== no_of_rules">
                        <x-form.button
                            type="button"
                            variant="primary"
                            x-on:click="add"
                        >
                            {{ __('Apply Custom Rules') }}
                        </x-form.button>
                    </template>
                </div>

                @if($errors->get('form.custom_rules.*'))
                    <ul class="border border-error bg-error/10 rounded-lg py-4 px-8 w-fit list-disc">
                        @foreach ($errors->get('form.custom_rules.*') as $messages)
                            @foreach ($messages as $message)
                                <li class="text-error text-xs">{{ $message }}</li>
                            @endforeach
                        @endforeach
                    </ul>
                @endif

                <template
                    x-for="(rule, index) in rules"
                    :key="index"
                >
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 items-center gap-4 mt-4">
                        <div class="w-full">
                            <select
                                x-model="custom_rules[index]"
                                class="form-select capitalize w-full rounded-lg border border-slate-300 bg-white px-3 py-2 pr-8 invalid:text-slate-500 hover:border-slate-400 focus:border-primary"
                            >
                                <option value="">{{ __('Select custom rule') }}</option>
                                @foreach (GroupCustomRules::displaySelectionBox() as $value => $name)
                                    <option value="{{ $value }}" x-show="displayOption">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-full">
                            <template x-if="custom_rules[index] === @js(GroupCustomRules::UPLOAD_DATE->value)">
                                <div class="flex items-center justify-between gap-2 sm:gap-4">
                                    <div class="min-w-0 flex-1">
                                        <input
                                            type="date"
                                            wire:model="form.custom_rules.created_at.start_date"
                                            name="form.custom_rules.created_at.start_date"
                                            placeholder="{{ __('Start date') }}"
                                            class="form-input w-full rounded-lg border text-black border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                                            required
                                        >
                                    </div>
                                    <span>to</span>
                                    <div class="min-w-0 flex-1">
                                        <input
                                            type="date"
                                            wire:model="form.custom_rules.created_at.end_date"
                                            name="form.custom_rules.created_at.end_date"
                                            placeholder="{{ __('End date') }}"
                                            class="form-input w-full rounded-lg border text-black border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                                            required
                                        >
                                    </div>
                                </div>
                            </template>
                            <template x-if="custom_rules[index] === @js(GroupCustomRules::PLACEMENT_DATE->value)">
                                <div class="flex items-center justify-between gap-2 sm:gap-4">
                                    <div class="min-w-0 flex-1">
                                        <input
                                            type="date"
                                            wire:model="form.custom_rules.placement_date.start_date"
                                            name="form.custom_rules.placement_date.start_date"
                                            placeholder="{{ __('Start Date') }}"
                                            class="form-input w-full rounded-lg border text-black border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                                            required
                                        >
                                    </div>
                                    <span>to</span>
                                    <div class="min-w-0 flex-1">
                                        <input
                                            type="date"
                                            wire:model="form.custom_rules.placement_date.end_date"
                                            name="form.custom_rules.placement_date.end_date"
                                            placeholder="{{ __('End Date') }}"
                                            class="form-input w-full rounded-lg border text-black border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                                            required
                                        >
                                    </div>
                                </div>
                            </template>
                            <template x-if="custom_rules[index] === @js(GroupCustomRules::BALANCE_RANGE->value)">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="basis-1/2">
                                        <input
                                            type="number"
                                            wire:model="form.custom_rules.current_balance.minimum_balance"
                                            name="form.custom_rules.current_balance.minimum_balance"
                                            placeholder="{{ __('Min balance') }}"
                                            class="form-input w-full rounded-lg border text-black border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                                            required
                                        >
                                    </div>
                                    <span>to</span>
                                    <div class="basis-1/2">
                                        <input
                                            type="number"
                                            wire:model="form.custom_rules.current_balance.maximum_balance"
                                            name="form.custom_rules.current_balance.maximum_balance"
                                            placeholder="{{ __('Max balance') }}"
                                            class="form-input w-full rounded-lg border text-black border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                                            required
                                        >
                                    </div>
                                </div>
                            </template>
                            <template x-if="custom_rules[index] === @js(GroupCustomRules::STATE->value)">
                                <x-form.select
                                    wire:model="form.custom_rules.state"
                                    name="form.custom_rules.state"
                                    :options="State::displaySelectionBox()"
                                    :placeholder="__('State')"
                                    class="w-full !mt-0"
                                    required
                                />
                            </template>
                            <template x-if="custom_rules[index] === @js(GroupCustomRules::CITY->value)">
                                <input
                                    type="text"
                                    wire:model="form.custom_rules.city"
                                    name="form.custom_rules.city"
                                    placeholder="{{ __('City') }}"
                                    class="form-input w-full rounded-lg border text-black border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                                    required
                                >
                            </template>
                            <template x-if="custom_rules[index] === @js(GroupCustomRules::ZIP->value)">
                                <input
                                    type="number"
                                    wire:model="form.custom_rules.zip"
                                    name="form.custom_rules.zip"
                                    placeholder="{{ __('Zip') }}"
                                    class="form-input w-full rounded-lg border text-black border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                                    required
                                >
                            </template>
                            <template x-if="custom_rules[index] === @js(GroupCustomRules::DATE_OF_BIRTH->value)">
                                <div class="flex flex-row items-center justify-between gap-4">
                                    <div class="basis-1/2">
                                        <input
                                            type="text"
                                            x-mask="9999"
                                            wire:model="form.custom_rules.dob.from_year"
                                            name="form.custom_rules.dob.from_year"
                                            placeholder="{{ __('From Year') }}"
                                            class="form-input w-full rounded-lg border text-black border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                                            required
                                        >
                                    </div>
                                    <span>to</span>
                                    <div class="basis-1/2">
                                        <input
                                            type="text"
                                            x-mask="9999"
                                            wire:model="form.custom_rules.dob.to_year"
                                            name="form.custom_rules.dob.to_year"
                                            placeholder="{{ __('To Year') }}"
                                            class="form-input w-full rounded-lg border text-black border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                                            required
                                        >
                                    </div>
                                </div>
                            </template>
                            <template x-if="custom_rules[index] === @js(GroupCustomRules::EXPIRATION_DATE->value)">
                                <div class="flex flex-row items-center justify-between gap-4">
                                    <div class="basis-1/2">
                                        <input
                                            type="text"
                                            x-mask="9999"
                                            wire:model="form.custom_rules.expiry_date.from_year"
                                            name="form.custom_rules.expiry_date.from_year"
                                            placeholder="{{ __('From Year') }}"
                                            class="form-input w-full rounded-lg border text-black border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                                            required
                                        >
                                    </div>
                                    <span>to</span>
                                    <div class="basis-1/2">
                                        <input
                                            type="text"
                                            x-mask="9999"
                                            wire:model="form.custom_rules.expiry_date.to_year"
                                            name="form.custom_rules.expiry_date.to_year"
                                            placeholder="{{ __('To Year') }}"
                                            class="form-input w-full rounded-lg border text-black border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                                            required
                                        >
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div>
                            <button
                                type="button"
                                x-on:click="remove(index)"
                            >
                                <x-lucide-trash-2 class="size-6 text-error hover:text-error-focus" />
                            </button>
                        </div>
                    </div>
                </template>
            </div>
            <div class="flex flex-col sm:flex-row sm:space-x-2 space-y-2 items-stretch sm:items-end justify-start mt-9">
                <a
                    wire:navigate
                    href="{{ route('home') }}"
                    class="btn border focus:border-slate-400 bg-slate-150 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80"
                >
                    {{ __('Cancel') }}
                </a>
                <x-communications.group-size
                    wire:model="openModal"
                    :$groupSize
                    :$totalBalance
                >
                    <x-form.button
                        type="button"
                        variant="primary"
                        class="disabled:opacity-50 border focus:border-primary-focus w-full"
                        wire:click="calculateGroupSize"
                        wire:loading.attr="disabled"
                    >
                        <x-lucide-eye class="size-4.5 sm:size-5 mr-1"/>
                        {{ __('Preview Group Size') }}
                    </x-form.button>
                </x-communications.group-size>

                <x-form.button
                    type="submit"
                    variant="success"
                    class="disabled:opacity-50 border focus:border-success-focus"
                    wire:loading.attr="disabled"
                    wire:target="createOrUpdate"
                >
                    <x-lucide-loader-2
                        wire:loading
                        wire:target="createOrUpdate"
                        class="size-4 animate-spin mr-2"
                    />
                    {{ $form->group_id ? __('Update') : __('Save') }}
                </x-form.button>
            </div>
        </form>
    </div>

    <livewire:creditor.communications.group.list-view />

    @script
        <script>
            Alpine.data('customRules', () => {
                return {
                    rules: [],
                    no_of_rules: @js(count(GroupCustomRules::values())),
                    custom_rules: [],
                    resetCustomRules() {
                        this.rules = []
                        this.custom_rules = []
                    },
                    add() {
                        if (this.custom_rules.length !== this.no_of_rules) this.rules.push(Math.random().toString(36).substring(7))
                    },
                    remove(index) {
                        this.rules.splice(index, 1)
                        let customRule = this.custom_rules[index]
                        this.custom_rules.splice(index, 1)
                        if (customRule) delete this.$wire.form.custom_rules[customRule]
                    },
                    displayOption() {
                        return !this.custom_rules.includes(this.$el.value)
                    },
                    editGroup() {
                        const backendCustomRules = Object.keys(this.$wire.form.custom_rules)
                        while (backendCustomRules.length > this.rules.length) this.add()
                        if (this.custom_rules.length !== backendCustomRules.length) this.custom_rules.push(...backendCustomRules)
                    }
                }
            })
        </script>
    @endscript

</div>

@use('App\Enums\CompanyBusinessCategory')
@use('App\Enums\DebtType')
@use('App\Enums\IndustryType')
@use('App\Enums\Timezone')
@use('Carbon\Carbon')

<div>
    <x-account-profile.card
        :cardTitle="__('Company Profile')"
        x-data="form"
        wire:submit="store"
    >
        <x-slot name="actionButtons">
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="store"
                class="btn space-x-2 disabled:opacity-50 bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
            >
                <span>{{ __('Save & Next') }}</span>
                <x-lucide-loader-2
                    wire:loading
                    wire:target="store"
                    class="size-5 animate-spin"
                />
                <x-heroicon-o-arrow-long-right
                    wire:loading.remove
                    wire:target="store"
                    class="size-5"
                />
            </button>
        </x-slot>

        <div class="mt-4 space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-form.input-field
                        x-model="company_name"
                        wire:model="form.company_name"
                        type="text"
                        :label="__('Company Name')"
                        name="form.company_name"
                        class="w-full"
                        :placeholder="__('Enter Company Name')"
                        required
                    />
                </div>
                <div>
                    <x-form.input-field
                        x-model="owner_full_name"
                        wire:model="form.owner_full_name"
                        type="text"
                        :label="__('Contact Name')"
                        name="form.owner_full_name"
                        class="w-full"
                        :placeholder="__('Enter Contact Name')"
                        required
                    />
                </div>

                <div>
                    <x-form.input-field
                        x-model="owner_email"
                        wire:model="form.owner_email"
                        type="text"
                        :label="__('Contact Email')"
                        name="form.owner_email"
                        class="w-full"
                        :placeholder="__('Enter Company Email')"
                        required
                    />
                </div>
                <div>
                    <x-form.us-phone-number
                        :label="__('Contact Phone')"
                        name="form.owner_phone"
                        :placeholder="__('Enter Contact Phone')"
                        required
                    />
                </div>

                <div>
                    <x-form.select
                        x-model="business_category"
                        wire:model="form.business_category"
                        :label="__('Member Type')"
                        :options="CompanyBusinessCategory::displaySelectionBox()"
                        name="form.business_category"
                        :placeholder="__('Member Type')"
                        required
                    />
                </div>
                <div>
                    <x-form.select
                        x-model="debt_type"
                        wire:model="form.debt_type"
                        :label="__('Debt Type')"
                        :options="DebtType::displaySelectionBox()"
                        name="form.debt_type"
                        :placeholder="__('Debt Type')"
                        required
                    />
                </div>
                <div>
                    <x-form.select
                        x-model="timezone"
                        wire:model="form.timezone"
                        :label="__('Timezone')"
                        :options="Timezone::displaySelectionBox()"
                        name="form.time_zone"
                        :placeholder="__('Timezone')"
                        class="w-full"
                        required
                    />
                </div>
                <div>
                    <x-form.input-field
                        x-model="url"
                        wire:model="form.url"
                        type="text"
                        :label="__('URL')"
                        name="form.url"
                        class="w-full"
                        :placeholder="__('Enter URL')"
                        required
                    />
                </div>
                <div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-form.select
                                wire:model="form.from_day"
                                x-model="from_day"
                                :label="__('From Day')"
                                :options="Carbon::getDays()"
                                name="form.from_day"
                                :placeholder="__('From Day')"
                                class="w-full"
                                required
                            />
                        </div>
                        <div>
                            <x-form.select
                                x-model="to_day"
                                wire:model="form.to_day"
                                :label="__('To Day')"
                                :options="Carbon::getDays()"
                                name="form.to_day"
                                :placeholder="__('To Day')"
                                class="w-full"
                                required
                            />
                        </div>
                    </div>
                </div>
                <div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block">
                                <span class="inline-flex font-semibold tracking-wide text-black lg:text-md">
                                    {{ __('From Time') }}
                                    <span class="text-error text-base leading-none">*</span>
                                </span>
                            </label>
                            <input
                                x-model="from_time"
                                wire:model="form.from_time"
                                x-ref="fromTimePicker"
                                type="text"
                                name="form.from_time"
                                class="w-full form-input mt-1.5 rounded-lg border text-black border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                                placeholder="{{ __('Enter From Time') }}"
                                required
                            />
                            @error('form.from_time')
                                <div class="mt-2">
                                    <span class="text-error text-sm+">
                                        {{ $message }}
                                    </span>
                                </div>
                            @enderror
                        </div>
                        <div>
                            <label class="block">
                                <span class="inline-flex font-semibold tracking-wide text-black lg:text-md">
                                    {{ __('To Time') }}
                                    <span class="text-error text-base leading-none">*</span>
                                </span>
                            </label>
                            <input
                                x-model="to_time"
                                wire:model="form.to_time"
                                x-ref="toTimePicker"
                                type="text"
                                name="form.to_time"
                                class="w-full form-input mt-1.5 rounded-lg border text-black border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                                placeholder="{{ __('Enter To Time') }}"
                                required
                                :disabled="!from_time"
                            />
                            @error('form.to_time')
                                <div class="mt-2">
                                    <span class="text-error text-sm+">
                                        {{ $message }}
                                    </span>
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div>
                    <x-form.input-field
                        x-model="fed_tax_id"
                        wire:model="form.fed_tax_id"
                        type="text"
                        x-mask="999999999"
                        :label="__('Tax Identification Number')"
                        name="form.fed_tax_id"
                        class="w-full"
                        :placeholder="__('Enter Tax Identification Number')"
                    />
                </div>
                <div class="sm:col-span-2">
                    <x-smarty-address
                        class="mt-2 md:!grid-cols-4 !gap-x-4"
                        required
                    />
                </div>
            </div>
        </div>
    </x-account-profile.card>

    @script
        <script>
            Alpine.data('form', () => {
                return {
                    company_name: Alpine.$persist('').as('company_name'),
                    owner_full_name: Alpine.$persist('').as('owner_full_name'),
                    owner_email: Alpine.$persist('').as('owner_email'),
                    business_category: Alpine.$persist('').as('business_category'),
                    debt_type: Alpine.$persist('').as('debt_type'),
                    url: Alpine.$persist('').as('url'),
                    fed_tax_id: Alpine.$persist('').as('fed_tax_id'),
                    timezone: Alpine.$persist('').as('timezone'),
                    from_day: Alpine.$persist('').as('from_day'),
                    to_day: Alpine.$persist('').as('to_day'),
                    from_time: Alpine.$persist('').as('from_time'),
                    to_time: Alpine.$persist('').as('to_time'),

                    init() {
                        localStorage.setItem('component_name', 'step-component')

                        this.company_name = this.company_name === '' ? this.$wire.form.company_name : this.company_name
                        this.owner_full_name = this.owner_full_name === '' ? this.$wire.form.owner_full_name : this.owner_full_name
                        this.owner_email = this.owner_email === '' ? this.$wire.form.owner_email : this.owner_email
                        this.business_category = this.business_category === '' ? this.$wire.form.business_category : this.business_category
                        this.debt_type = this.debt_type === '' ? this.$wire.form.debt_type : this.debt_type
                        this.url = this.url === '' ? this.$wire.form.url : this.url
                        this.fed_tax_id = this.fed_tax_id === '' ? this.$wire.form.fed_tax_id : this.fed_tax_id
                        this.timezone = this.timezone === '' ? this.$wire.form.timezone : this.timezone
                        this.from_day = this.from_day === '' ? this.$wire.form.from_day : this.from_day
                        this.to_day = this.to_day === '' ? this.$wire.form.to_day : this.to_day
                        this.from_time = this.from_time === '' ? this.$wire.form.from_time : this.from_time
                        this.to_time = this.to_time === '' ? this.$wire.form.to_time : this.to_time

                        // When without storing to database and redirect to next step!
                        this.$wire.form.company_name = this.company_name
                        this.$wire.form.owner_full_name = this.owner_full_name
                        this.$wire.form.owner_email = this.owner_email
                        this.$wire.form.business_category = this.business_category
                        this.$wire.form.debt_type = this.debt_type
                        this.$wire.form.url = this.url
                        this.$wire.form.fed_tax_id = this.fed_tax_id
                        this.$wire.form.timezone = this.timezone
                        this.$wire.form.from_day = this.from_day
                        this.$wire.form.to_day = this.to_day
                        this.$wire.form.from_time = this.from_time
                        this.$wire.form.to_time = this.to_time

                        const fields = [
                            { ref: 'fromTimePicker', value: this.$wire.form.from_time },
                            { ref: 'toTimePicker', value: this.$wire.form.to_time }
                        ];

                        fields.forEach(({ ref, value }) => {
                            this.initializeFlatpickr(this.$refs[ref], value);
                        });
                    },

                    initializeFlatpickr(element, initialValue) {
                        window.flatpickr(element, {
                            allowInvalidPreload: false,
                            enableTime: true,
                            noCalendar: true,
                            dateFormat: 'h:i K',
                            minuteIncrement: 15,
                            defaultDate: initialValue || null,
                            onReady: (selectedDates, dateStr, instance) => {
                                instance.timeContainer.querySelector('.flatpickr-minute').readOnly = true;
                            },
                        });
                    },
                }
            })
        </script>
    @endscript
</div>

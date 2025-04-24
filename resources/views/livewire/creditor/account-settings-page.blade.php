@use('App\Enums\BankAccountType')
@use('App\Enums\CompanyBusinessCategory')
@use('App\Enums\DebtType')
@use('App\Enums\Timezone')
@use('Carbon\Carbon')

<div>
    <div
        x-data="phoneNumber"
        class="card px-4 py-4 sm:px-5"
    >
        <form wire:submit="updateSettings" autocomplete="off">
            <h2 class="text-lg font-medium tracking-wide text-slate-700">
                {{ __('Profile Picture') }}
            </h2>
            <div class="my-2 h-px bg-slate-200"></div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 my-4">
                <div
                    class="flex space-x-4 items-center"
                    x-on:profile-photo-updated.window="(event) => $dispatch('update-profile-photo', event.detail[0])"
                    x-on:livewire-upload-start="$wire.resetImageValidation()"
                >
                    <x-profile-picture :$image />
                </div>
            </div>

            <div class="flex justify-between items-center">
                <h2 class="text-lg font-medium tracking-wide text-slate-700">
                    {{ __('Main Point of Content') }}
                </h2>
            </div>
            <hr class="mt-2 h-px bg-slate-200" />

            <div class="grid grid-cols-1 md:grid-cols-12 gap-x-3 mb-4 mt-2">
                <div class="my-2 col-span-4">
                    <x-form.input-field
                        type="text"
                        wire:model="form.owner_full_name"
                        :label="__('Full Name')"
                        name="form.owner_full_name"
                        :placeholder="__('Enter Full Name')"
                        class="w-full"
                        required
                    />
                </div>
                <div class="my-2 col-span-4">
                    <x-form.input-field
                        type="text"
                        wire:model="form.owner_email"
                        :label="__('Email')"
                        name="form.owner_email"
                        :placeholder="__('Enter Email')"
                        class="w-full"
                        required
                    />
                </div>
                <div class="my-2 col-span-4">
                    <x-form.input-field
                        type="text"
                        x-model="ownerPhoneNumber"
                        x-on:change="setPhoneNumber"
                        x-mask="(999) 999-9999"
                        :label="__('Phone')"
                        name="form.owner_phone"
                        :placeholder="__('Enter Phone')"
                        class="w-full"
                        required
                    />
                </div>
            </div>

            <h2 class="text-lg font-medium tracking-wide text-slate-700">
                {{ __('Company Information') }}
            </h2>
            <hr class="mt-2 h-px bg-slate-200" />
            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-3 mb-4 mt-2">
                <div class="my-2">
                    <x-form.input-field
                        wire:model="form.company_name"
                        :label="__('Company Name')"
                        type="text"
                        name="form.company_name"
                        placeholder="{{ __('Enter Company Name') }}"
                        class="w-full"
                        required
                    />
                </div>
                <div class="my-2">
                    <x-form.input-field
                        wire:model="form.billing_email"
                        type="text"
                        :label="__('Email')"
                        name="form.billing_email"
                        placeholder="{{ __('Enter Email') }}"
                        class="w-full"
                        required
                    />
                </div>
                <div class="my-2">
                    <x-form.input-field
                        type="text"
                        x-mask="(999) 999-9999"
                        x-on:change="setPhoneNumber"
                        x-model="billingPhoneNumber"
                        :label="__('Phone')"
                        name="form.billing_phone"
                        :placeholder="__('Enter Phone')"
                        class="w-full"
                        required
                    />
                </div>
                <div class="my-2">
                    <x-form.select
                        wire:model="form.timezone"
                        :label="__('Timezone')"
                        :options="Timezone::displaySelectionBox()"
                        name="form.timezone"
                        :placeholder="__('Timezone')"
                        class="w-full"
                        required
                    />
                </div>
                <div class="my-2">
                    <div class="grid grid-cols-2 gap-x-1.5">
                        <div>
                            <x-form.select
                                wire:model="form.from_day"
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
                <div class="my-2">
                    <div class="grid grid-cols-2 gap-x-1.5">
                        <div>
                            <label class="block">
                                <span class="inline-flex font-semibold tracking-wide text-black lg:text-md">
                                    {{ __('From Time') }}
                                    <span class="text-error text-base leading-none">*</span>
                                </span>
                            </label>
                            <input
                                wire:model="form.from_time"
                                x-ref="fromTimePicker"
                                type="text"
                                name="form.from_time"
                                class="w-full form-input mt-1.5 rounded-lg border text-black border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                                placeholder="{{ __('Enter From Time') }}"
                                required
                            />
                            @error('form.form_time')
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
                                wire:model="form.to_time"
                                x-ref="toTimePicker"
                                type="text"
                                name="form.to_time"
                                class="w-full form-input mt-1.5 rounded-lg border text-black border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                                placeholder="{{ __('Enter To Time') }}"
                                required
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
                <div class="my-2">
                    <x-form.input-field
                        wire:model="form.url"
                        type="text"
                        :label="__('URL')"
                        name="form.url"
                        class="w-full"
                        :placeholder="__('Enter URL')"
                        required
                    />
                </div>
                <div class="my-2">
                    <x-form.select
                        wire:model="form.business_category"
                        :label="__('Member Type')"
                        :options="CompanyBusinessCategory::displaySelectionBox()"
                        name="form.business_category"
                        :placeholder="__('Member Type')"
                        required
                    />
                </div>
                <div class="my-2">
                    <x-form.select
                        wire:model="form.debt_type"
                        :label="__('Debt Type')"
                        :options="DebtType::displaySelectionBox()"
                        name="form.debt_type"
                        :placeholder="__('Debt Type')"
                        required
                    />
                </div>

                <div class="my-2">
                    <x-form.input-field
                        wire:model="form.fed_tax_id"
                        type="text"
                        x-mask="999999999"
                        :label="__('Tax Identification Number')"
                        name="form.fed_tax_id"
                        class="w-full"
                        :placeholder="__('Enter Tax Identification Number')"
                    />
                </div>
            </div>

            <x-smarty-address
                :blockTitle="__('Physical Address')"
                required
            />

            <div class="my-2 flex justify-center sm:justify-end space-x-2 mt-9">
                <a
                    wire:navigate
                    href="{{ route('home') }}"
                    class="btn border focus:border-slate-400 bg-slate-150 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80"
                >
                    {{ __('Cancel') }}
                </a>
                <x-form.button
                    type="submit"
                    variant="primary"
                    wire:loading.attr="disabled"
                    wire:target="updateSettings"
                    class="disabled:opacity-50 border focus:border-primary-focus"
                >
                    <x-lucide-loader-2
                        wire:loading
                        wire:target="updateSettings"
                        class="size-5 animate-spin mr-2"
                    />
                    {{ __('Update') }}
                </x-form.button>
            </div>
        </form>
    </div>
</div>

@script
    <script>
        Alpine.data('phoneNumber', () => ({
            billingPhoneNumber: '',
            ownerPhoneNumber: '',
            init () {
                this.billingPhoneNumber = this.$wire.form.billing_phone
                this.ownerPhoneNumber = this.$wire.form.owner_phone

                const fields = [
                    { ref: 'fromTimePicker', value: this.$wire.form.from_time },
                    { ref: 'toTimePicker', value: this.$wire.form.to_time }
                ];

                fields.forEach(({ ref, value }) => {
                    this.initializeFlatpickr(this.$refs[ref], value);
                });
            },
            setPhoneNumber() {
                this.$wire.form.billing_phone = this.billingPhoneNumber.replace(/[()-\s]/g, '')
                this.$wire.form.owner_phone = this.ownerPhoneNumber.replace(/[()-\s]/g, '')
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
        }))
    </script>
@endscript

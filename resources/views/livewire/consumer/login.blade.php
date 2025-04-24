<div>
    <div class="px-8 py-8 sm:py-12 my-6 bg-white shadow-sm ring-1 ring-gray-950/5 sm:rounded-xl max-w-xl mx-auto">
        <div class="flex justify-center lg:hidden">
            <x-logo-svg width="320px" />
        </div>

        <h2 class="text-2xl font-semibold text-black">
            {{ __('Consumer Portal Login') }}
        </h2>

        <form
            x-data="login"
            x-effect="resetRecaptcha"
            class="mt-4"
            method="POST"
            wire:submit="authenticate"
            autocomplete="off"
        >
            <label class="relative flex">
                <input
                    wire:model="form.last_name"
                    @class([
                        'form-input peer w-full rounded-lg border text-base border-slate-300 bg-transparent px-5 py-3 pl-9 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary',
                        'border-red-500' => $errors->has('form.last_name'),
                    ])
                    placeholder="{{ __('Last name') }}"
                    required
                    autocomplete="off"
                />
                <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-600 peer-focus:text-primary">
                    <x-lucide-user-2 class="size-5" />
                </span>
            </label>
            @error('form.last_name')
                <div class="mt-1">
                    <span class="text-error">
                        {{ $message }}
                    </span>
                </div>
            @enderror

            <label
                wire:ignore
                class="relative mt-4 flex"
            >
                <input
                    x-init="flatPickr"
                    @class([
                        'form-input peer w-full rounded-lg border text-base border-slate-300 bg-transparent px-5 py-3 pl-9 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary',
                        'border-red-500' => $errors->has('form.dob'),
                    ])
                    placeholder="{{ __('mm/dd/yyyy') }}"
                    required
                    autocomplete="off"
                />
                <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-600 peer-focus:text-primary">
                    <x-lucide-calendar-days class="size-5" />
                </span>
            </label>
            @error('form.dob')
                <div class="mt-1">
                    <span class="text-error">
                        {{ $message }}
                    </span>
                </div>
            @enderror

            <label class="relative mt-4 flex">
                <input
                    wire:model="form.last_four_ssn"
                    type="text"
                    minlength="4"
                    maxlength="4"
                    @class([
                        'form-input peer w-full rounded-lg ssn-field border text-base border-slate-300 bg-transparent px-5 py-3 pl-9 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary',
                        'border-red-500' => $errors->has('form.last_four_ssn'),
                    ])
                    placeholder="{{ __('Last 4 digits of SSN') }}"
                    required
                    autocomplete="off"
                />
                <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-600 peer-focus:text-primary">
                    <x-lucide-lock class="size-5" />
                </span>
            </label>
            @error('form.last_four_ssn')
                <div class="mt-1">
                    <span class="text-error">
                        {{ $message }}
                    </span>
                </div>
            @enderror

            <div
                wire:ignore
                data-callback="handleCaptcha"
                class="g-recaptcha mt-5"
                data-sitekey="{{ config('services.google_recaptcha.site_key') }}"
            ></div>
            @error('form.recaptcha')
                <div class="mt-1">
                    <span class="text-error">
                        {{ $message }}
                    </span>
                </div>
            @enderror

            <button
                type="submit"
                class="btn mt-4 h-10 w-full disabled:opacity-50 bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
                wire:loading.class="opacity-50"
                wire:loading.attr="disabled"
            >
                <div wire:loading wire:target="authenticate">
                    <x-lucide-loader-2 class="size-5 animate-spin mr-2" />
                </div>
                <span>{{ __('Login') }}</span>
            </button>
        </form>

        <div class="my-5 flex justify-center text-base font-semibold text-slate-600">
            <x-consumer.terms-of-use-and-privacy-policy />
        </div>

        <div class="flex w-full text-xl font-semibold text-slate-800 justify-center pt-2">
            {{ __('YouNegotiate offers you free service to resolve your debt without speaking with a collector.') }}
        </div>
    </div>

    @script
        <script>
            Alpine.data('login', () => ({
                flatPickrInstance: null,
                init () {
                    window.handleCaptcha = this.handleCaptcha

                    let script = document.createElement('script')
                    script.src = 'https://www.google.com/recaptcha/api.js'
                    script.async = true
                    script.defer = true
                    window.document.body.appendChild(script)
                },
                flatPickr() {
                    this.flatPickrInstance = window.flatpickr(this.$el, {
                        altInput: true,
                        altFormat: 'm/d/Y',
                        allowInput: true,
                        dateFormat: 'Y-m-d',
                        allowInvalidPreload: true,
                        disableMobile: true,
                        ariaDateFormat: 'm/d/Y',
                        minDate: '1900-01-01',
                        maxDate: @js(today()->subDay()->toDateString()),
                        onClose: function (selectedDates, dateStr, instance) {
                            $wire.form.dob = dateStr
                        },
                        onReady: function(selectedDates, dateStr, instance) {
                            instance.altInput.addEventListener('input', () => {
                                const inputValue = event.target.value.replace(/\//g, '')


                                if (inputValue.length === 8) {
                                    const month = parseInt(inputValue.substring(0, 2), 10)
                                    const day = parseInt(inputValue.substring(2, 4), 10)
                                    const year = parseInt(inputValue.substring(4, 8), 10)

                                    const isValidDate = (year >= 1900 && year <= new Date().getFullYear()) &&
                                                        month >= 1 && month <= 12 &&
                                                        day >= 1 && day <= new Date(year, month, 0).getDate()

                                    if (isValidDate) {
                                        const formattedDate = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`

                                        const enteredDate = new Date(formattedDate)
                                        const minDate = new Date('1900-01-01')
                                        const maxDate = new Date(@js(today()->subDay()->toDateString()))

                                        if (enteredDate >= minDate && enteredDate <= maxDate) {
                                            instance.setDate(formattedDate)
                                        }

                                        return
                                    }

                                    instance.setDate('')
                                }
                            })

                            instance.altInput.setAttribute('placeholder', '{{ __("Date of birth (mm/dd/yyyy)") }}')
                        }
                    })
                },
                resetRecaptcha() {
                    if ($wire.resetCaptcha) {
                        grecaptcha.reset()
                        $wire.form.recaptcha = ''
                        $wire.resetCaptcha = false
                    }
                },
                handleCaptcha (response) {
                    $wire.form.recaptcha = response
                },
                destroy() {
                    this.flatPickrInstance?.destroy()
                }
            }))
        </script>
    @endscript
</div>

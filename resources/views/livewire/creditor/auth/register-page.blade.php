<div>
    <div class="px-8 py-8 sm:py-12 my-6 bg-white shadow-sm ring-1 ring-gray-950/5 sm:rounded-xl max-w-xl mx-auto">
        <div class="flex justify-center lg:hidden">
            <x-logo-svg width="320px" />
        </div>

        <h2 class="text-2xl font-semibold text-black">
            {{ __('Join the Win-Win Team') }}
        </h2>

        <form
            x-data="recaptcha"
            x-effect="resetRecaptcha"
            class="mt-4"
            method="POST"
            wire:submit="register"
            autocomplete="off"
        >
            <label class="relative mt-4 flex">
                <input
                    wire:model="form.name"
                    class="form-input peer w-full rounded-lg border text-base border-slate-300 bg-transparent px-5 py-3 pl-9 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                    placeholder="{{ __('Full Name') }}"
                    required
                    autocomplete="off"
                >
                <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-600 peer-focus:text-primary">
                    <x-heroicon-o-user class="w-5"/>
                </span>
            </label>
            @error('form.name')
                <div class="mt-1">
                    <span class="text-sm+ text-error">
                        {{ $message }}
                    </span>
                </div>
            @enderror

            <label class="relative mt-4 flex">
                <input
                    type="email"
                    class="form-input peer w-full rounded-lg border text-base border-slate-300 bg-transparent px-5 py-3 pl-9 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                    placeholder="{{ __('Email') }}"
                    wire:model="form.email"
                    required
                    autocomplete="off"
                >
                <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-600 peer-focus:text-primary">
                    <x-heroicon-o-envelope class="w-5"/>
                </span>
            </label>
            @error('form.email')
                <div class="mt-1">
                    <span class="text-sm+ text-error">
                        {{ $message }}
                    </span>
                </div>
            @enderror

            <x-auth.password
                name="form.password"
                wire:model="form.password"
                :placeholder="__('Password')"
                class="border text-base border-slate-300 bg-transparent px-5 py-3 pl-9 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                required
            />

            <div x-data="{ confirmShowPassword : false}">
                <label class="relative mt-4 flex">
                    <input
                        wire:model="form.password_confirmation"
                        :type="confirmShowPassword ? 'type' : 'password'"
                        class="form-input peer w-full rounded-lg border text-base border-slate-300 bg-transparent px-5 py-3 pl-9 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                        placeholder="{{ __('Confirm Password') }}"
                        required
                        autocomplete="off"
                    />
                    <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-600 peer-focus:text-primary">
                        <x-heroicon-o-lock-closed class="w-5"/>
                    </span>
                    <span class="flex absolute right-0 h-full pr-3 text-slate-600 peer-focus:text-primary">
                        <x-heroicon-o-eye-slash @click="confirmShowPassword = !confirmShowPassword" x-show="!confirmShowPassword" class="w-5.5" />
                        <x-heroicon-o-eye @click="confirmShowPassword = !confirmShowPassword" x-show="confirmShowPassword" class="w-5.5" />
                    </span>
                </label>
            </div>
            <div class="flex mt-4 justify-between text-base font-semibold text-slate-700">
                <label class="inline-flex text-sm sm:text-base items-center space-x-2">
                    <input
                        wire:model="form.terms_and_conditions"
                        type="checkbox"
                        class="form-checkbox is-basic size-4 sm:size-4.5 rounded border-slate-400/70 checked:bg-primary checked:border-primary hover:border-primary focus:border-primary"
                        required
                        autocomplete="off"
                    />
                    <x-terms-of-use-and-privacy-policy>
                        <x-slot name="buttonLabel">
                            {{ __('I agree to') }} <span class="font-bold text-primary hover:underline">{{ __('Terms and Conditions') }}</span>
                        </x-slot>
                    </x-terms-of-use-and-privacy-policy>
                </label>
            </div>
            @error('form.terms_and_conditions')
                <div class="mt-1">
                    <span class="text-sm+ text-error">
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
                    <span class="text-sm+ text-error">
                        {{ $message }}
                    </span>
                </div>
            @enderror

            <button
                type="submit"
                class="btn mt-4 h-10 w-full bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
                wire:loading.class="opacity-50"
                wire:loading.attr="disabled"
            >
                <div
                    wire:loading
                    wire:target="register"
                    class="p-2"
                >
                    <x-lucide-loader-2 class="size-5 animate-spin" />
                </div>
                <span>{{ __('Join YouNegotiate') }}</span>
            </button>
        </form>

        <div class="flex justify-between mt-2 text-base font-semibold text-slate-700">
            {{ __('Already have an account?') }}
            <a
                wire:navigate
                href="{{ route('login') }}"
                class="text-primary underline hover:text-primary-focus"
            >
                {{ __('Login here!') }}
            </a>
        </div>
    </div>

    @script
        <script>
            Alpine.data('recaptcha', () => ({
                init () {
                    window.handleCaptcha = this.handleCaptcha

                    let script = document.createElement('script')
                    script.src = 'https://www.google.com/recaptcha/api.js'
                    script.async = true
                    script.defer = true
                    window.document.body.appendChild(script)
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
                }
            }))
        </script>
    @endscript
</div>

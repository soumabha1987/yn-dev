<div>
    <div class="px-8 py-8 sm:py-12 my-6 bg-white shadow-sm ring-1 ring-gray-950/5 sm:rounded-xl max-w-xl mx-auto">
        <div class="flex justify-center lg:hidden">
            <x-logo-svg width="320px" />
        </div>

        <h2 class="text-2xl font-semibold text-black">
            {{ __('Reset Password!') }}
        </h2>

        <form
            x-data="recaptcha"
            x-effect="resetRecaptcha"
            class="mt-4"
            method="POST"
            wire:submit="forgotPassword"
            autocomplete="off"
        >
            <label class="relative mt-4 flex">
                <input
                    type="email"
                    wire:model="form.email"
                    class="form-input peer w-full rounded-lg border text-base border-slate-300 bg-transparent px-5 py-3 pl-9 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                    placeholder="{{ __('Enter Email') }}"
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
                    wire:target="forgotPassword"
                    class="p-2"
                >
                    <x-lucide-loader-2  class="size-5 animate-spin" />
                </div>
                <span>{{ __('Send Reset Link!') }}</span>
            </button>
        </form>

        <div class="flex justify-between mt-2 text-base font-semibold text-slate-700">
            {{ __('Want to go back to login?') }}
            <a
                wire:navigate
                href="{{ route('login') }}"
                class="text-primary underline hover:text-primary-focus"
            >
                {{ __('Click here!') }}
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
                        $wire.resetCaptcha = false
                    }
                },
                handleCaptcha (response) {
                    $wire.form.recaptcha = response
                },
            }))
        </script>
    @endscript
</div>

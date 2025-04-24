<div>
    <div class="px-8 py-8 sm:py-12 my-6 bg-white shadow-sm ring-1 ring-gray-950/5 sm:rounded-xl max-w-xl mx-auto">
        <div class="flex justify-center lg:hidden">
            <x-logo-svg width="320px" />
        </div>

        <h2 class="text-2xl font-semibold text-black">
            {{ __('Member Sign In') }}
        </h2>

        <form
            x-data="recaptcha"
            x-effect="resetRecaptcha"
            class="mt-4"
            method="POST"
            wire:submit="authenticate"
            autocomplete="off"
        >
            @csrf
            <label class="relative flex">
                <input
                    type="email"
                    wire:model="form.email"
                    class="form-input peer w-full rounded-lg border text-base text-black border-slate-300 bg-transparent px-5 py-3 pl-9 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                    placeholder="{{ __('Enter Email') }}"
                    required
                    autocomplete="off"
                />
                <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-500 peer-focus:text-primary">
                    <x-heroicon-o-envelope class="w-5" />
                </span>
            </label>
            @error('form.email')
                <div class="mt-1">
                    <span class="text-sm+ text-error">
                        {{ $message }}
                    </span>
                </div>
            @enderror

            <div x-data="{ showPassword : false}">
                <label class="relative mt-4 flex">
                    <input
                        x-bind:type="showPassword ? 'text' : 'password'"
                        wire:model="form.password"
                        class="form-input peer w-full rounded-lg border text-base text-black border-slate-300 bg-transparent py-3 pr-10 pl-9 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                        placeholder="{{ __('Enter Password') }}"
                        required
                        autocomplete="off"
                    />
                    <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-500 peer-focus:text-primary">
                        <x-heroicon-o-lock-closed class="w-5"/>
                    </span>
                    <span class="flex absolute h-full pr-3 right-0 text-slate-600 peer-focus:text-primary">
                        <x-heroicon-o-eye-slash @click="showPassword = !showPassword" x-show="!showPassword" class="w-5.5" />
                        <x-heroicon-o-eye @click="showPassword = !showPassword" x-show="showPassword" class="w-5.5" />
                    </span>
                </label>
                @error('form.password')
                    <div class="mt-1">
                        <span class="text-sm+ text-error">
                            {{ $message }}
                        </span>
                    </div>
                @enderror
            </div>

            <div class="flex mt-4 justify-between font-semibold text-sm sm:text-base text-slate-700">
                <label class="inline-flex items-center space-x-2">
                    <input
                        wire:model.boolean="form.remember"
                        class="form-checkbox is-basic size-4 sm:size-4.5 rounded border-slate-400/70 checked:bg-primary checked:border-primary hover:border-primary focus:border-primary"
                        type="checkbox"
                    >
                    <p>{{ __('Remember me!') }}</p>
                </label>
                <button
                    type="button"
                    wire:click="forgotPassword"
                    class="text-primary underline hover:text-primary-focus"
                >
                    {{ __('Reset my password') }}
                </button>
            </div>

            <div
                wire:ignore
                data-callback="handleCaptcha"
                class="g-recaptcha mt-5"
                data-sitekey="{{ config('services.google_recaptcha.site_key') }}"
            >
            </div>
            @error('form.recaptcha')
                <div class="mt-1">
                    <span class="text-sm+ text-error">
                        {{ $message }}
                    </span>
                </div>
            @enderror

            <button
                type="submit"
                class="btn mt-4 disabled:opacity-50 h-10 w-full bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
                wire:loading.class="opacity-50"
                wire:loading.attr="disabled"
            >
                <div
                    wire:loading
                    wire:target="authenticate"
                    class="p-2"
                >
                    <x-lucide-loader-2 class="size-5 animate-spin" />
                </div>
                <span>{{ __('Login') }}</span>
            </button>
        </form>

        <div class="flex justify-between mt-2 font-semibold text-sm sm:text-base text-slate-700">
            {{ __(' Become a YouNegotiate Hero. ') }}
            <a
                wire:navigate
                href="{{ route('register') }}"
                class="text-primary underline hover:text-primary-focus"
            >
                {{ __('Join Now') }}
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
                },
            }))
        </script>
    @endscript
</div>

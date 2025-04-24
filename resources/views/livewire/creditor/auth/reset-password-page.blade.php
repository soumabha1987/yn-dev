<div>
    <div class="px-8 py-8 sm:py-12 my-6 bg-white shadow-sm ring-1 ring-gray-950/5 sm:rounded-xl max-w-xl mx-auto">
        <div class="flex justify-center lg:hidden">
            <x-logo-svg width="320px" />
        </div>

        <h2 class="text-2xl font-semibold text-black">
            {{ __('Reset Password!') }}
        </h2>

        <form
            class="mt-4"
            wire:submit="resetPassword"
            method="POST"
            autocomplete="off"
        >
            <label class="relative flex">
                <input
                    wire:model="form.email"
                    class="form-input peer w-full rounded-lg border text-base border-slate-300 bg-transparent px-5 py-3 pl-9 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                    placeholder="{{ __('Enter Email') }}"
                    required
                    autocomplete="off"
                >
                <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-600 peer-focus:text-primary">
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

            <x-auth.password
                name="form.password"
                wire:model="form.password"
                class="border text-base border-slate-300 bg-transparent px-5 py-3 pl-9 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
            />

            <div x-data="{ confirmShowPassword : false }">
                <label class="relative mt-4 flex">
                    <input
                        :type="confirmShowPassword ? 'type' : 'password'"
                        class="form-input peer w-full rounded-lg border text-base border-slate-300 bg-transparent px-5 py-3 pl-9 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                        placeholder="{{ __('Confirm Password') }}"
                        wire:model="form.password_confirmation"
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
                @error('form.password_confirmation')
                    <div class="mt-1">
                        <span class="text-sm+ text-error">
                            {{ $message }}
                        </span>
                    </div>
                @enderror
            </div>

            <button
                type="submit"
                class="btn mt-4 h-10 w-full bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
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
                <span>{{ __('Submit') }}</span>
            </button>
        </form>
        <div class="flex w-full text-xl font-semibold text-slate-800 justify-center pt-10">
            {{ __('YouNegotiate offers you free service to resolve your debt without speaking with a collector.') }}
        </div>
    </div>
</div>

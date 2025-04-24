<div>
    <template x-teleport="body">
        <div class="fixed inset-0 z-[100] flex flex-col items-center justify-center overflow-hidden px-4 py-6 sm:px-5">
            <div
                class="absolute inset-0 bg-slate-900/60 transition-opacity duration-300 cursor-not-allowed"
                x-transition:enter="ease-out"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
            ></div>

            <div
                class="relative w-full origin-top rounded-lg bg-white transition-all duration-300 max-w-lg"
                x-transition:enter="easy-out"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="easy-in"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
            >
                <div class="flex justify-items-center">
                    <x-emoji-happy-smile class="inline size-28 mx-auto my-4" />
                </div>

                <h3 class="text-xl py-2 font-bold flex justify-center text-black">
                    <span class="text-3xl font-medium">{{ __("Merchant Under Verification") }}</span>
                </h3>
                <div class="m-3 pl-4 pr-4">
                    <p>{{__('Your merchant account is currently under verification. Once verified, you’ll be able to import consumers. Please allow up to 24 hours for this process to complete.')}}</p>
                </div>
                <div class="p-6 pt-0">
                    <div class="space-x-2 text-center">
                        <a
                            href="{{ route('home') }}"
                            class="btn border focus:border-primary-focus select-none text-white bg-primary hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
                        >
                            {{ __('Go To Dashboard') }}
                        </a>
                        <x-form.default-button 
                            type="button"
                            wire:click="logout"
                        >
                            {{ __('Logout') }}
                        </x-form.default-button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

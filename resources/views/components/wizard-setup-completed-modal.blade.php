<div
    x-data="dialogData"
    x-modelable="dialogOpen"
    tabindex="-1"
>
    <template x-teleport="body">
        <div
            x-dialog
            x-model="dialogOpen"
            class="fixed inset-0 z-[100] flex flex-col items-center justify-center overflow-hidden px-4 py-6 sm:px-5"
        >
            <div
                x-dialog:overlay
                class="absolute inset-0 bg-slate-900/40 transition-opacity duration-300"
                x-transition:enter="ease-out"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
            ></div>

            <div
                x-dialog:panel
                class="relative w-full py-4 origin-top rounded-lg bg-white transition-all duration-300 max-w-xl"
                x-transition:enter="easy-out"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="easy-in"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
            >
                <div class="flex justify-items-center">
                    <x-emoji-happy-smile class="inline size-20 mx-auto my-4" />
                </div>
                <h3 class="text-xl md:text-2xl mb-4 font-bold flex justify-center text-black">
                    <span>{{ __('Great Job!') }}</span>
                </h3>
                <div class="flex justify-center text-lg px-4 sm:px-0">
                    <p>{{__('You\'ve completed your set up.')}}</p>
                </div>

                <div class="p-4 pt-4">
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-2">
                        <a
                            wire:navigate
                            href="{{ route('creditor.import-consumers.index') }}"
                            class="w-full sm:w-36 btn select-none text-white bg-success hover:bg-success-focus focus:bg-success-focus active:bg-success-focus/90"
                        >
                            <span>{{ __('Upload a File') }}</span>
                        </a>
                        <x-dialog.close>
                            <x-form.default-button
                                type="button"
                                class="w-full sm:w-36"
                            >
                                {{ __('Close') }}
                            </x-form.default-button>
                        </x-dialog.close>
                    </div>
                </div>

                <div class="p-2 text-center text-base sm:text-lg">
                    <p>
                        {{ __('All Set up profiles can be found in') }}
                        <span class="underline bold text-primary">
                            {{__('Manage Account')}}
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </template>

    @script
        <script>
            Alpine.data('dialogData', () => {
                return {
                    dialogOpen: true,
                    init() {
                        document.addEventListener('livewire:navigate', () => {
                            this.dialogOpen = false
                        })
                    }
                }
            })
        </script>
    @endscript
</div>

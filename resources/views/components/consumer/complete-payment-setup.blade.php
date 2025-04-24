<div
    x-data="{ dialogOpen: true }"
    tabindex="-1"
>
    <template x-teleport="body">
        <div
            x-dialog
            x-bind:open="dialogOpen"
        >
            <x-dialog.panel confirm-box>
                <x-slot name="headerCancelIcon">
                    <div class="flex justify-end px-4">
                        <button
                            type="button"
                            x-on:click="$dialog.close()"
                            x-on:close.window="dialogOpen = false"
                            class="btn size-10 text-black rounded-full p-2 mr-1 hover:bg-slate-300/20"
                        >
                            <x-heroicon-o-x-mark class="size-8 fill-white text-black" />
                        </button>
                    </div>
                </x-slot>
                <x-slot name="svg">
                    <span class="flex justify-center">
                        <x-lucide-circle-check-big class="size-20 text-success" />
                    </span>
                </x-slot>
                <x-slot name="heading">
                    <span class="text-2xl sm:text-3xl font-medium">{{ __('Congratulation!') }}</span>
                </x-slot>
                <x-slot name="message">
                    <span class="text-base font-medium text-black">
                        {{ __('Your payment setup has been successfully completed.') }}
                    </span>
                </x-slot>
                <x-slot name="buttons">
                    <x-form.default-button
                        type="button"
                        x-on:click="$dialog.close()"
                        x-on:close.window="dialogOpen = false"
                        class="w-24 sm:text-base"
                    >
                        {{ __('Close') }}
                    </x-form.default-button>
                </x-slot>
            </x-dialog.panel>
        </div>
    </template>
</div>

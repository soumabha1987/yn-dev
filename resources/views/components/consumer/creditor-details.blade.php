@props(['creditorDetails' => []])

<x-consumer.dialog>
    <x-consumer.dialog.open>
        {{ $slot }}
    </x-consumer.dialog.open>

    <x-consumer.dialog.panel :blankPanel="true" class="h-96" size="md">
        <div class="p-5">
            <div class="flex justify-between items-center">
                <div class="badge bg-success/30 size-10 rounded-full">
                    <x-heroicon-o-check-circle class="size-8 stroke-2 text-success-focus" />
                </div>
                <button
                    type="button"
                    x-on:click="$dialog.close()"
                    x-on:close.window="dialogOpen = false"
                    class="btn size-10 text-black rounded-full p-2 mr-1 hover:bg-slate-500/20"
                >
                    <x-heroicon-o-x-mark class="size-8 fill-black text-black" />
                </button>
            </div>
            <div class="my-5">
                <div>
                    <h3 class="text-xl font-semibold text-black">
                        {{ __('Account Contact Details') }}
                    </h3>

                    <p class="mt-1 text-sm text-slate-400">
                        {{ __('Please find the contact details below') }}
                    </p>
                </div>


                <div class="grid grid-cols-1 gap-3 mt-4 sm:gap-5">
                    @if (filled($creditorDetails))
                        <div class="mt-0.5">
                            <p class="text-xs">{{ __('Current Account Placement') }}</p>
                            <div class="overflow-x-auto is-scrollbar-hidden min-w-full">
                                <p class="text-lg capitalize font-semibold text-slate-800">
                                    {{ $creditorDetails['company_name'] }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-0.5">
                            <p class="text-base font-semibold">{{ __('About') }}</p>
                            <div class="prose overflow-x-auto min-w-full max-h-52">
                                {!! $creditorDetails['custom_content'] !!}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </x-consumer.dialog.panel>
</x-consumer.dialog>

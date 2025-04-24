<div>
    <x-consumer.dialog>
        <x-consumer.dialog.open>
            <x-consumer.menu.item>
                <div @close-menu.window="menuOpen = false">
                    <span>{{ __('Change First Pay Date') }}</span>
                </div>
            </x-consumer.menu.item>
        </x-consumer.dialog.open>

        <x-consumer.dialog.panel
            size="xl"
            :need-dialog-panel="false"
        >
            <x-slot name="heading">{{ __('First Pay Date') }}</x-slot>

            <form
                method="POST"
                x-data="changeFirstPayDate"
                wire:submit="changeFirstPayDate"
                wire:loading.attr="disabled"
                x-on:close-dialog.window="closeDialog()"
                autocomplete="off"
            >
                <div class="my-2">
                    <span class="inline-flex font-semibold tracking-wide text-black lg:text-md">
                        {{ __('Date') }}
                        <span class="text-error text-base leading-none">*</span>
                    </span>
                    <label class="relative mt-1 flex" wire:ignore>
                        <input
                            wire:model="first_pay_date"
                            x-effect="if(dialogOpen) flatPickr()"
                            type="date"
                            @class([
                                'form-input peer w-full rounded-lg border bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary',
                                'border-red-500' => $errors->has('first_pay_date'),
                                'border-slate-300' => $errors->missing('first_pay_date'),
                            ])
                            autocomplete="off"
                        >
                        <span
                            @class([
                            'pointer-events-none absolute flex h-full w-10 items-center justify-center peer-focus:text-primary',
                            'text-error' => $errors->has('first_pay_date'),
                            'text-slate-400' => $errors->missing('first_pay_date'),
                       ])>
                            <x-lucide-calendar-days class="size-5 transition-colors duration-200" />
                        </span>
                    </label>
                    @error('first_pay_date')
                        <div class="mt-1 text-error">
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>

                <div class="flex items-end gap-x-2 justify-end mt-5">
                    <x-consumer.dialog.close>
                        <x-form.default-button type="button">
                            {{ __('Cancel') }}
                        </x-form.default-button>
                    </x-consumer.dialog.close>
                    <button
                        type="submit"
                        class="btn border focus:border-info-focus bg-info disabled:opacity-50 text-center font-medium text-white hover:bg-info-focus focus:bg-info-focus active:bg-info-focus/90"
                    >
                        {{ __('Update First Pay Date') }}
                    </button>
                </div>
            </form>
        </x-consumer.dialog.panel>
    </x-consumer.dialog>
    @script
        <script>
            Alpine.data('changeFirstPayDate', () => ({
                flatPickrInstance: null,
                flatPickr() {
                    this.flatPickrInstance = window.flatpickr(this.$el, {
                        altInput: true,
                        altFormat: 'm/d/Y',
                        dateFormat: 'Y-m-d',
                        allowInvalidPreload: true,
                        disableMobile: true,
                        minDate: @js(now()->addDay()->toDateString()),
                        onReady: function (selectedDates, dateStr, instance) {
                            instance.input.setAttribute('placeholder', "{{ __('Select date') }}")
                        },
                    })
                },
                destroy() {
                    this.flatPickrInstance?.destroy()
                },
                closeDialog () {
                    this.dialogOpen = false;
                    this.$dispatch('refresh-page');
                }
            }))
        </script>
    @endscript
</div>

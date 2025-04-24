@use('App\Enums\ConsumerStatus')

<div>
    <x-consumer.dialog>
        <x-consumer.dialog.open>
            <x-consumer.menu.close>
                <x-consumer.menu.item>
                    <div class="flex items-center space-x-2">
                        @if ($page === 'schedule-plan')
                            <x-lucide-edit class="size-4.5" />
                        @endif
                        <span>{{ $consumer->status === ConsumerStatus::HOLD ? __('Change Restart Date') : __('Temporary Hold') }}</span>
                    </div>
                </x-consumer.menu.item>
            </x-consumer.menu.close>
        </x-consumer.dialog.open>

        <x-consumer.dialog.panel
            size="xl"
            :need-dialog-panel="false"
        >
            <x-slot name="heading">{{ __('Manage Hold Account') }}</x-slot>

            <form
                method="POST"
                x-data="holdDate"
                wire:submit="hold"
                wire:loading.attr="disabled"
                x-on:close-dialog.window="closeDialog()"
                autocomplete="off"
            >
                <div class="my-2">
                    <span class="inline-flex font-semibold tracking-wide text-black lg:text-md">
                        {{ __('Date') }}
                        <span class="text-error text-base leading-none">*</span>
                    </span>
                    <label class="relative mt-1 flex">
                        <input
                            wire:model="form.restart_date"
                            x-effect="if(dialogOpen) holdFlatPickr()"
                            type="date"
                            @class([
                                'form-input peer w-full rounded-lg border bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary',
                                'border-red-500' => $errors->has('form.restart_date'),
                                'border-slate-300' => $errors->missing('form.restart_date'),
                            ])
                            autocomplete="off"
                        >
                        <span
                            @class([
                            'pointer-events-none absolute flex h-full w-10 items-center justify-center peer-focus:text-primary',
                            'text-error' => $errors->has('form.restart_date'),
                            'text-slate-400' => $errors->missing('form.restart_date'),
                       ])>
                            <x-lucide-calendar-days class="size-5 transition-colors duration-200" />
                        </span>
                    </label>
                    @error('form.restart_date')
                        <div class="mt-1 text-error">
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>
                <div class="my-2">
                    <x-form.text-area
                        name="form.hold_reason"
                        :label="__('Enter Message')"
                        wire:model="form.hold_reason"
                        rows="3"
                        required
                    />
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center sm:justify-end gap-2 mt-5">
                    <x-consumer.dialog.close>
                        <x-form.default-button class="w-full" type="button">
                            {{ __('Cancel') }}
                        </x-form.default-button>
                    </x-consumer.dialog.close>
                    <button
                        type="submit"
                        class="btn border focus:border-info-focus bg-info disabled:opacity-50 text-center font-medium text-white hover:bg-info-focus focus:bg-info-focus active:bg-info-focus/90"
                    >
                        {{ $consumer->status === ConsumerStatus::HOLD ? __('Update Restart Date') : __('Submit') }}
                    </button>
                </div>
            </form>
        </x-consumer.dialog.panel>
    </x-consumer.dialog>
    @script
        <script>
            Alpine.data('holdDate', () => ({
                flatPickrInstance: null,
                holdFlatPickr() {
                    this.flatPickrInstance = window.flatpickr(this.$el, {
                        altInput: true,
                        altFormat: 'm/d/Y',
                        dateFormat: 'Y-m-d',
                        allowInvalidPreload: true,
                        disableMobile: true,
                        defaultDate: this.$wire.form.restart_date,
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

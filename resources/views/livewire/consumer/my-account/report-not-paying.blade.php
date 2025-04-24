<div>
    <x-dialog>
        <x-dialog.open>
            @if ($view === 'negotiate')
                <span class="font-bold text-red-500 hover:underline cursor-pointer">
                    {{ __('Click here') }}
                </span>
            {{-- @elseif ($view === 'my-account')
                <x-consumer.menu.item>
                    {{ __('Report Not Paying') }}
                </x-consumer.menu.item> --}}
            @elseif ($view === 'view-offer')
                <button
                    type="button"
                    class="btn border w-full border-info/30 bg-info/10 font-medium text-info hover:bg-info/20 focus:bg-info/20 active:bg-info/25"
                >
                    {{ __('Report Not Paying') }}
                </button>
            @endif
        </x-dialog.open>

        <x-consumer.dialog.panel
            wire:model="dialogOpenReport"
            :heading="__('Reason I\'m Not Paying')"
        >
            <form
                x-ref="reportNotPayingForm"
                wire:submit="reportNotPaying"
                autocomplete="off"
            >
                <div class="mx-5">
                    <div class="space-y-2 text-black">
                        @foreach ($reasons as $id => $label)
                            <div class="flex items-center text-xs sm:text-sm pb-1">
                                <x-form.input-radio
                                    wire:model="reason"
                                    :label="$label"
                                    :value="$id"
                                    labelClass="!pr-0"
                                />
                            </div>
                        @endforeach
                    </div>

                    <template x-if="$wire.reason === @js((string) $reasons->flip()->get('Other'))">
                        <div>
                            <x-form.text-area
                                label=""
                                rows="3"
                                type="text"
                                wire:model="other"
                                name="other"
                                :placeholder="__('Enter Reason')"
                                class="w-full"
                            />
                        </div>
                    </template>
                    @error('reason')
                        <div class="mt-2 text-error">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <x-slot name="footer" class="mt-5">
                    <x-consumer.dialog.close>
                        <x-form.default-button type="button">
                            {{ __('Cancel') }}
                        </x-form.default-button>
                    </x-consumer.dialog.close>
                    <x-consumer.form.button
                        type="submit"
                        variant="primary"
                        class="border focus:border-primary-focus disabled:opacity-50"
                        @click="$refs.reportNotPayingForm.requestSubmit()"
                        wire:target="reportNotPaying"
                        wire:loading.attr="disabled"
                    >
                        {{ __('Submit') }}
                    </x-consumer.form.button>
                </x-slot>
            </form>
        </x-consumer.dialog.panel>
    </x-dialog>
</div>

@props(['consumer' => ''])

<div x-data="paymentLink">
    <x-consumer.dialog x-model="openDialog">
        <x-consumer.dialog.open>
            {{ $slot }}
        </x-consumer.dialog.open>

        <x-consumer.dialog.panel size="2xl" :heading="__('Your Helping Hand Link')">
            <p class="text-base font-semibold text-black">
                {{ __('Debt free faster! This Debt Free Americans link makes helping you a tax deduction!') }}
            </p>
            <p class="text-base font-semibold text-black">
                {{ __('Debt free is the new Coolest Gift Ever!') }}
            </p>
            <div class="mt-4 flex items-center justify-between text-base p-2 text-black sm:px-5">
                <input
                    type="text"
                    class="w-full border-2 border-r-0 border-primary rounded-l px-2"
                    x-ref="paymentLinkInput"
                    value="{{ url()->signedRoute('consumer.external-payment', ['c' => bin2hex((string) $consumer->id)]) }}"
                    readonly
                    disabled
                    autocomplete="off"
                >
                <button
                    type="button"
                    class="btn h-7 shrink-0 font-semibold border-2 border-primary rounded-l-none bg-primary/20 text-base px-2 text-primary"
                    x-on:click="copyGeneratedLink"
                >
                    {{ __('Copy') }}
                </button>
            </div>
        </x-consumer.dialog.panel>
    </x-consumer.dialog>
    @script
        <script>
            Alpine.data('paymentLink', () => ({
                openDialog: false,
                copyGeneratedLink() {
                    if (navigator.clipboard && document.hasFocus()) {
                        navigator.clipboard.writeText(this.$refs.paymentLinkInput.value)
                            .then(() => {
                                this.openDialog = false;
                                this.$notification({ text: @js(__('Payment link copied in your clipboard.')) })
                            })
                    }
                }
            }))
        </script>
    @endscript
</div>

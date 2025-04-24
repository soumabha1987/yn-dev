@props(['consumer'])

<div x-data="offerAccepted">
    <template x-if="! visible">
        <x-consumer.dialog x-model="isOfferAccepted">
            <x-consumer.dialog.panel
                :blur="true"
                :confirmBox="true"
                size="2xl"
            >
                <x-slot name="heading">
                    <h2 class="text-3xl font-semibold text-black">{{ __('Awesome! Your offer is approved!') }}</h2>
                </x-slot>
                <x-slot name="svg">
                    <x-lucide-circle-check-big class="inline size-28 text-success" />
                </x-slot>
                <x-slot name="message">
                    <div class="mx-12">
                        <p class="font-medium text-lg mt-5">{{ __('Your offer has been automatically accepted. To proceed and secure your approved offer, please set up your payment details. You can also do this later if you prefer.') }}</p>
                        <p class="font-medium text-lg mt-5">{{ __('Always know we\'ll never share your payment information with your creditor or use it for anything outside of your approved and agreed upon payments.') }}</p>
                    </div>
                </x-slot>

                <x-slot name="buttons">
                    <div class="flex gap-x-3 text-center justify-center">
                        <a
                            wire:navigate
                            href="{{ route('consumer.payment', ['consumer' => $consumer]) }}"
                            class="btn mt-6 bg-success font-semibold text-lg text-white hover:bg-success-focus focus:bg-success-focus active:bg-success-focus"
                        >
                            {{ __('Set up payment profile!!') }}
                        </a>
                        <a
                            wire:navigate
                            href="{{ route('consumer.account') }}"
                            class="btn mt-6 bg-primary font-semibold text-lg text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
                        >
                            {{ __('My Accounts') }}
                        </a>
                    </div>
                </x-slot>
            </x-consumer.dialog.panel>
        </x-consumer.dialog>
    </template>
</div>

@script
    <script>
        Alpine.data('offerAccepted', () => ({
            isOfferAccepted: false,
            init() {
                this.isOfferAccepted = this.$wire.form.isOfferAccepted

                this.$wire.$watch('form.isOfferAccepted', () => {
                    if (this.$wire.form.isOfferAccepted) {
                        this.isOfferAccepted = this.$wire.form.isOfferAccepted
                    }
                })

                this.$watch('isOfferAccepted', () => {
                    if (this.$wire.form.isOfferAccepted) {
                        this.isOfferAccepted = this.$wire.form.isOfferAccepted
                    }
                })
            },
        }))
    </script>
@endscript

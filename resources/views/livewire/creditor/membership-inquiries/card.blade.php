<div class="rounded-xl border border-slate-400">
    <div
        @class([
            'flex flex-col h-full rounded-xl bg-slate-50 p-4 text-center',
            'justify-between' => ! $membershipInquiryCreatedAt,
        ])
    >
        @if ($membershipInquiryCreatedAt)
            <div class="mt-10 flex justify-center">
                <x-lucide-circle-check-big class="text-success size-20"/>
            </div>
            <div class="mt-10 text-black font-medium text-center">
                <p>
                    {!! __('Your inquiry was sent to on <br>(<b>:date</b>)!', ['date' => $membershipInquiryCreatedAt->formatWithTimezone()]) !!}
                </p>
                <p class="my-2">
                    {{ __('Please be on the lookout for a call or email from us AND check your account for a new custom membership plan within the next 24 hours.') }}
                </p>
                <b class="text-xl">🥂 {{ __('Cheers!') }} 🥂</b>

                <div class="flex flex-col space-y-2 font-bold mt-5">
                    <a
                        href="mailto:help@younegotiate.com"
                        class="text-primary hover:underline"
                        target="_blank"
                    >
                        help@younegotiate.com
                    </a>
                    <span>or</span>
                    <a
                        href="tel:3212000010"
                        class="hover:underline text-primary"
                        target="_blank"
                    >
                        (321)-200-0010
                    </a>
                </div>

            </div>
        @else
            <div class="mt-4">
                <h4 class="text-xl font-semibold text-slate-700">
                    {{ __('Enterprise') }}
                </h4>
                <p class="mt-2">{{ __('Custom plans for enterprises that need to scale') }}</p>
            </div>
            <div class="mt-4 flex items-baseline justify-center">
                <span class="text-3xl tracking-tight text-primary">
                    {{ __('Contact Us') }}
                </span>
            </div>
            <div class="mt-4 space-y-1 text-left">
                <div class="flex items-start space-x-reverse">
                    <div class="flex size-6 shrink-0 items-center justify-center rounded-full">
                        <x-heroicon-m-check class="size-4.5 text-success" />
                    </div>
                    <span class="font-medium text-black">
                        {{ __('Premium support') }}
                    </span>
                </div>
                <div class="flex items-start space-x-reverse">
                    <div class="flex size-6 shrink-0 items-center justify-center rounded-full">
                        <x-heroicon-m-check class="size-4.5 text-success" />
                    </div>
                    <span class="font-medium text-black">
                        {{ __('Dedicated Consumer Success Manager') }}
                    </span>
                </div>
            </div>
            <div class="mt-3">
                <x-dialog wire:model="dialogOpen">
                    <x-dialog.open>
                        <button
                            type="button"
                            class="btn rounded-full font-medium text-white bg-primary hover:bg-primary-400"
                        >
                            {{ __('Contact Us') }}
                        </button>
                    </x-dialog.open>
                    <x-dialog.panel :heading="__('Enterprise Inquiry')">
                        <form
                            wire:submit="membershipInquiry"
                            method="POST"
                            autocomplete="off"
                        >
                            <div
                                x-data="account_scope"
                                class="my-3"
                            >
                                <x-form.input-field
                                    type="text"
                                    x-on:input="formatWithCommas"
                                    :label="__('Accounts in Scope')"
                                    name="inquiryForm.accounts_in_scope"
                                    :placeholder="__('Number of accounts')"
                                    class="w-full"
                                    required
                                />
                            </div>
                            <x-form.text-area
                                :label="__('Message')"
                                name="inquiryForm.description"
                                wire:model="inquiryForm.description"
                                rows="5"
                                maxlength="1000"
                                :placeholder="__('Enter Message')"
                            />
                            <div class="mt-2 text-right space-x-2">
                                <x-dialog.close>
                                    <x-form.default-button type="button">
                                        {{ __('Cancel') }}
                                    </x-form.default-button>
                                </x-dialog.close>
                                <x-form.button
                                    type="submit"
                                    variant="primary"
                                    wire:target="membershipInquiry"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="disabled:opacity-50"
                                >
                                    <span
                                        wire:loading
                                        wire:target="membershipInquiry"
                                    >
                                        {{ __('Submitting..') }}
                                    </span>
                                    <span
                                        wire:loading.remove
                                        wire:target="membershipInquiry"
                                    >
                                        {{ __('Submit') }}
                                    </span>
                                </x-form.button>
                            </div>
                        </form>
                    </x-dialog.panel>
                </x-dialog>
            </div>
        @endif
    </div>
    @script
        <script>
            Alpine.data('account_scope', () => ({
                formatWithCommas() {
                    let input = this.$event.target.value.replace(/[^0-9]/g, '');

                    this.$event.target.value = input.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

                    this.$wire.inquiryForm.accounts_in_scope = this.$event.target.value.replace(/[,\s]/g, '')
                },
            }))
        </script>
    @endscript
</div>

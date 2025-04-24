<div>
    <form
        method="POST"
        wire:submit="save"
        autocomplete="off"
    >
        <div
            x-data="{ isOpenDescription: true }"
            class="card px-4 py-4 sm:px-5"
        >
            <x-form.select
                wire:model.live="form.pay_terms"
                :label="__('Apply to')"
                :options="$payTermsOption"
                name="form.pay_terms"
                class="w-1/2 mt-3"
                :placeholder="__('pay terms')"
                required
            />

            <div
                class="inline-block w-fit font-semibold pt-5 text-success cursor-pointer"
                x-on:click="isOpenDescription = !isOpenDescription"
            >
                <span x-text="isOpenDescription ? @js(__('Hide Descriptions')) : @js(__('View Descriptions'))" />
            </div>

            <div class="my-4">
                <h2 class="text-lg font-semibold text-black">{{ __('Regular Pay-Terms (Auto-Approve if Equal or Lower)') }}</h2>
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-5">
                <div class="rounded-lg border bg-white-150 p-2">
                    <div class="flex flex-col justify-between h-full">
                        <span class="font-semibold tracking-wide text-black lg:text-md">
                            {{ __('Pay In Full Balance Discount %') }}<span class="text-error text-base font-semibold">*</span>
                        </span>
                        <p
                            x-show="isOpenDescription"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 scale-90"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-300"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-90"
                            class="text-xs+ py-2 text-justify"
                        >
                            {{ __('Surveyed consumers said they are more likely to pay off a balance if they can get a discount, and if they don’t they do nothing or go to a debt negotiation service! If my consumer can pay in full, I would like to discount their balance by this %. Example: Account Balance $500.00, My PIF Balance Discount 20% ($100.00), My Consumer can pay off this account for $400.00!') }}
                        </p>
                        <div class="mt-auto">
                            <x-form.input-field
                                wire:model="form.pif_balance_discount_percent"
                                type="number"
                                name="form.pif_balance_discount_percent"
                                step="1"
                                :placeholder="__('% Amount')"
                                class="w-full"
                            />
                        </div>
                    </div>
                </div>
                <div class="rounded-lg border bg-white-150 p-2">
                    <div class="flex flex-col justify-between h-full">
                        <span class="font-semibold tracking-wide text-black lg:text-md">
                            {{ __('Payment Plan Balance Discount %') }}<span class="text-error text-base">*</span>
                        </span>
                        <p
                            x-show="isOpenDescription"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 scale-90"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-300"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-90"
                            class="text-xs+ py-2 text-justify"
                        >
                            {{ __('98% of consumers surveyed said they will not call a creditor to set up payments, so we want your consumer to start making payments on You Negotiate! Increase your chances by discounting the balance if they set up and stick with a payment plan! If my consumer commits to a Payment Plan (I approve), I would like to discount their pay off balance by this %. Example: Account Balance $500.00, My Payment Plan Balance Discount is 10% ($50.00), If my consumer sets up a payment plan, My Consumer will make a total of $450.00 in payments to pay off this account.') }}
                        </p>
                        <div class="mt-auto">
                            <x-form.input-field
                                wire:model="form.ppa_balance_discount_percent"
                                type="number"
                                step="1"
                                name="form.ppa_balance_discount_percent"
                                :placeholder="__('% Payoff Balance Discount')"
                                class="w-full"
                            />
                        </div>
                    </div>
                </div>
                <div class="rounded-lg border bg-white-150 p-2">
                    <div class="flex flex-col justify-between h-full">
                        <span class="font-semibold tracking-wide text-black lg:text-md">
                            {{ __('Minimum Monthly Payment % Of Balance') }}<span class="text-error text-base">*</span>
                        </span>
                        <p
                            x-show="isOpenDescription"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 scale-90"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-300"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-90"
                            class="text-xs+ py-2 text-justify"
                        >
                            {{ __('99% of consumers surveyed said they will not set up a plan if they can\'t afford it and don\'t call because they feel too much pressure from a collector. The goal here is to get a payment plan they can afford in place as quickly as possible before they throw in the towel with the attitude of - do what you need to do! % of Balance Minimum % of balance that must be paid per month to digitally approve. Example: account balance $500.00, minimum monthly payment % is 10%, which means the minimum monthly payment offered is $50.00 (it will take 10 months for the Consumer to pay their balance in full)') }}
                        </p>
                        <div class="mt-auto">
                            <x-form.input-field
                                wire:model="form.min_monthly_pay_percent"
                                type="number"
                                step="1"
                                name="form.min_monthly_pay_percent"
                                :placeholder="__('% of Discount Payoff Balance')"
                                class="w-full"
                            />
                        </div>
                    </div>
                </div>
                <div class="rounded-lg border bg-white-150 p-2">
                    <div class="flex flex-col justify-between h-full">
                        <span class="font-semibold tracking-wide text-black lg:text-md">
                            {{ __('Maximum Days to First Payment') }}<span class="text-error text-base">*</span>
                        </span>
                        <p
                            x-show="isOpenDescription"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 scale-90"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-300"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-90"
                            class="text-xs+ py-2 text-justify"
                        >
                            {{ __('99% of consumers surveyed want to pay off the debt and make a payment as soon as they can. Life comes up with other pressing bills to pay, so we want to give them enough time to make the payment, yet not let them stretch it out to eternity! From the date the consumer sets up a payment plan, what is the # of days you allow to schedule their first payment. Example: Consumer Agrees to Payment Plan on May 1, My Max days is set at 30 days, Consumer must schedule this first payment on or before June 1st, otherwise requires them to submit an offer.') }}
                        </p>
                        <div class="mt-auto">
                            <x-form.input-field
                                wire:model="form.max_days_first_pay"
                                type="number"
                                name="form.max_days_first_pay"
                                :placeholder="__('Recommended 14-30 days')"
                                class="w-full"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <div class="my-4">
                <h2 class="text-lg font-semibold text-black">{{ __('Lowest Custom Offer Terms') }}</h2>
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-5">
                <div class="rounded-lg border bg-white-150 p-2">
                    <div class="flex flex-col justify-between h-full">
                        <span class="font-semibold tracking-wide text-black lg:text-md">
                            {{ __('Minimum Pay In Full Discount %') }}<span class="text-error text-base">*</span>
                        </span>
                        <p
                            x-show="isOpenDescription"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 scale-90"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-300"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-90"
                            class="text-xs+ py-2 text-justify"
                        >
                            {{ __('Surveyed consumers said they’re more motivated to pay off their debt in full if they can get a discount. This setting allows you to define the lowest discount you’re willing to accept for a full balance payment. Consumers cannot submit an offer with a lower discount. Example: Account Balance $500.00, My Min Pay In Full Discount is 15% ($75.00), My Consumer must offer at least $75.00 to pay off this account in full.') }}
                        </p>
                        <div class="mt-auto">
                            <x-form.input-field
                                wire:model="form.minimum_settlement_percentage"
                                type="number"
                                name="form.minimum_settlement_percentage"
                                :placeholder="__('% of minimum settlement payment')"
                                class="w-full"
                            />
                        </div>
                    </div>
                </div>
                <div class="rounded-lg border bg-white-150 p-2">
                    <div class="flex flex-col justify-between h-full">
                        <span class="font-semibold tracking-wide text-black lg:text-md">
                            {{ __('Maximum Days to First Payment') }}<span class="text-error text-base">*</span>
                        </span>
                        <p
                            x-show="isOpenDescription"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 scale-90"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-300"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-90"
                            class="text-xs+ py-2 text-justify"
                        >
                            {{ __('99% of consumers surveyed want to make a payment quickly, but sometimes need a bit of time to prepare. This setting defines the maximum number of days you’re willing to wait for the first payment after the consumer sets up a payment plan. Offers requesting more days than this will not be allowed. Example: Consumer Agrees to Payment Plan on May 1, My Max Days is 30 Days, Consumer must schedule their first payment on or before June 1st to submit a valid offer.') }}
                        </p>
                        <div class="mt-auto">
                            <x-form.input-field
                                wire:model="form.max_first_pay_days"
                                type="number"
                                name="form.max_first_pay_days"
                                :placeholder="__('Days To First Payment')"
                                class="w-full"
                            />
                        </div>
                    </div>
                </div>
                <div class="rounded-lg border bg-white-150 p-2">
                    <div class="flex flex-col justify-between h-full">
                        <span class="font-semibold tracking-wide text-black lg:text-md">
                            {{ __('Minimum Monthly Payment % of Balance') }}<span class="text-error text-base">*</span>
                        </span>
                        <p
                            x-show="isOpenDescription"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 scale-90"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-300"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-90"
                            class="text-xs+ py-2 text-justify"
                        >
                            {{ __('Most consumers will not commit to a plan if they feel overwhelmed, so we want it to feel manageable — but still effective. This setting defines the minimum monthly payment amount as a percentage of the total balance you’ll accept. Offers with lower monthly payments will not be allowed. Example: Account Balance $500.00, Minimum Monthly Payment % is 10%, so the Consumer must offer to pay at least $50.00 per month (and the plan must be structured to pay off the full balance).') }}
                        </p>
                        <div class="mt-auto">
                            <x-form.input-field
                                wire:model="form.minimum_payment_plan_percentage"
                                type="number"
                                name="form.minimum_payment_plan_percentage"
                                :placeholder="__('% of minimum payment plan')"
                                class="w-full"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-center sm:justify-end space-x-2 mt-3">
                <a
                    wire:navigate
                    href="{{ route('creditor.pay-terms') }}"
                    class="btn border focus:border-slate-400 bg-slate-150 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80 min-w-[7rem]"
                >
                    {{ __('Cancel') }}
                </a>
                <x-form.button
                    type="submit"
                    variant="primary"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="border focus:border-primary-focus font-medium min-w-[7rem] disabled:opacity-50"
                >
                    <x-lucide-loader-2
                        wire:loading
                        wire:target="save"
                        class="size-5 animate-spin mr-2"
                    />
                    @if ($form->pay_terms === 'master_terms' && filled($form->pif_balance_discount_percent))
                        {{ __('Update Pay Terms') }}
                    @else
                        {{ __('Save Pay Terms') }}
                    @endif
                </x-form.button>
            </div>
        </div>
    </form>
</div>

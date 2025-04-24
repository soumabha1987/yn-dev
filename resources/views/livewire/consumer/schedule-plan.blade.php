@use('Illuminate\Support\Number')
@use('App\Enums\ConsumerStatus')
@use('App\Enums\MerchantType')
@use('App\Enums\NegotiationType')
@use('App\Enums\InstallmentType')
@use('App\Enums\TransactionStatus')
@use('App\Enums\TransactionType')
@use('App\Models\ScheduleTransaction')
@use('App\Models\Transaction')

@php
    $number = function (ScheduleTransaction $scheduleTransaction) {
        if ($scheduleTransaction->paymentProfile?->method === MerchantType::CC) {
            return $scheduleTransaction->paymentProfile->last4digit;
        }

        if ($scheduleTransaction->paymentProfile?->method === MerchantType::ACH) {
            return $scheduleTransaction->paymentProfile->account_number;
        }
    };

    $transactionNumber = function (Transaction $transaction) {
        if ($transaction->paymentProfile?->method === MerchantType::CC) {
            return $transaction->paymentProfile->last4digit;
        }

        if ($transaction->paymentProfile?->method === MerchantType::ACH) {
            return $transaction->paymentProfile->account_number;
        }
    };

    $externalTransactionNumber = function (Transaction $transaction) {
        if ($transaction->externalPaymentProfile->method === MerchantType::CC) {
            return $transaction->externalPaymentProfile->last_four_digit;
        }

        if ($transaction->externalPaymentProfile->method === MerchantType::ACH) {
            return $transaction->externalPaymentProfile->account_number;
        }
    }
@endphp

<div>
    <main
        x-data="{ openPaymentPlanPayOffDialog: false }"
        class="w-full pb-8 text-black"
    >
        @if (session()->pull('complete-payment-setup'))
            <x-consumer.complete-payment-setup />
        @endif

        <div
            x-on:refresh-page.window="$wire.$refresh"
            class="grid grid-cols-8 gap-4 sm:gap-5 lg:gap-6 relative"
        >
            <div class="col-span-12 grid grid-cols-12 items-center rounded-lg bg-[#1d5dec] py-5 sm:py-6">
                <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                    <div class="px-4 text-white sm:px-5">
                        <div class="flex justify-between items-start">
                            <div class="mt-2 sm:mt-0">
                                <div class="flex items-center space-x-2">
                                    <p class="text-xl lg:text-2xl font-extrabold">
                                        {{ $creditorDetails['contact_person_name'] }}
                                    </p>
                                </div>
                                <p class="text-xs text-indigo-100">{{ $consumer->account_number }}</p>
                            </div>

                            <x-menu>
                                <x-menu.button class="hover:bg-[#677ff0] p-1 rounded-full">
                                    <x-heroicon-m-ellipsis-vertical class="size-7" />
                                </x-menu.button>

                                <x-consumer.menu.items class="w-64">
                                    @if (
                                        (float) ($consumer->consumerNegotiation->payment_plan_current_balance ?? $this->negotiationCurrentAmount($consumer)) > 0
                                        && ((float) $consumer->current_balance) > 0
                                        && $consumer->status !== ConsumerStatus::SETTLED
                                    )
                                        <x-consumer.menu.item x-on:click="openPaymentPlanPayOffDialog = true">
                                            <x-lucide-wallet class="size-4.5" />
                                            <span>{{ __('Payoff - Plan Pay Method')  }}</span>
                                        </x-consumer.menu.item>
                                    @endif

                                    <x-consumer.menu.item disabled>
                                        <x-lucide-dollar-sign class="size-5" />
                                        <span>{{ __('Payoff - Different method')  }} <span class="text-primary font-bold">(coming soon)</span></span>
                                    </x-consumer.menu.item>

                                    @if ($consumer->status !== ConsumerStatus::SETTLED)
                                        <livewire:consumer.my-account.hold
                                            :$consumer
                                            page="schedule-plan"
                                            :key="str()->random(5)"
                                        />
                                    @endif
                                    @if ($consumer->status === ConsumerStatus::HOLD)
                                       <livewire:consumer.my-account.restart-plan
                                           :$consumer
                                           page="schedule-plan"
                                           :key="str()->random(5)"
                                       />
                                    @endif

                                    <x-consumer.menu.item
                                        wire:click="downloadAgreement({{ $consumer->id }})"
                                        wire:target="downloadAgreement({{ $consumer->id }})"
                                        wire:loading.attr="disabled"
                                    >
                                        <x-lucide-loader-2
                                            wire:loading
                                            wire:target="downloadAgreement({{ $consumer->id }})"
                                            class="size-5 animate-spin"
                                        />
                                        <x-lucide-download
                                            wire:loading.remove
                                            wire:target="downloadAgreement({{ $consumer->id }})"
                                            class="size-5"
                                        />
                                        <span>{{ __('Agreement/Payments Made') }}</span>
                                    </x-consumer.menu.item>

                                    <x-consumer.menu.item disabled>
                                        <x-lucide-pencil class="size-5" />
                                        <span>{{ __('Recurring Payment Date')  }}<span class="text-primary font-bold">{{ __('(coming soon)') }}</span></span>
                                    </x-consumer.menu.item>

                                    <x-consumer.menu.item disabled>
                                        <x-lucide-x class="size-5" />
                                        <span>{{ __('Cancel Plan')  }} <span class="text-primary font-bold">{{ __('(coming soon)') }}</span></span>
                                    </x-consumer.menu.item>
                                </x-consumer.menu.items>
                            </x-menu>
                        </div>

                        @if ($consumer->status === ConsumerStatus::HOLD)
                            <div class="text-warning font-bold text-sm pt-1">
                                {{ __('Restart on :date', ['date' => $consumer->restart_date->format('M d, Y')]) }}
                            </div>
                        @endif

                        <div class="hidden sm:flex relative h-36 w-auto shrink-0 rounded-lg bg-gradient-to-br from-[#ffffff55] to-[#ffffff20] mt-4">
                            <div class="absolute inset-0 flex flex-col justify-between rounded-lg border border-white/10 p-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-lg font-semibold text-white">
                                        {{ $consumer->paymentProfile->method->displayName() }}
                                    </span>
                                    @svg($consumer->paymentProfile->method === MerchantType::CC ? 'lucide-credit-card' : 'lucide-landmark', ['class' => 'size-6 text-indigo-100'])
                                </div>
                                <div class="text-white">
                                    @if ($consumer->paymentProfile->method === MerchantType::CC)
                                        <p class="text-lg font-semibold tracking-wide">
                                            **** **** **** {{ $consumer->paymentProfile->last4digit }}
                                        </p>
                                        <p class="mt-2 text-xs font-medium">{{ $consumer->paymentProfile->expirity }}</p>
                                    @endif

                                    @if ($consumer->paymentProfile->method === MerchantType::ACH)
                                        <p class="text-lg font-semibold tracking-wide">
                                            *** *** *** {{ $consumer->paymentProfile->account_number }}
                                        </p>
                                    @endif

                                    <p class="mt-1 text-sm font-medium">
                                        {{ $consumer->paymentProfile->fname . ' ' . $consumer->paymentProfile->lname }}
                                    </p>
                                </div>
                            </div>
                            @if ($consumer->status !== ConsumerStatus::SETTLED)
                                <a
                                    wire:navigate
                                    href="{{ route('consumer.payment', ['consumer' => $consumer->id]) }}"
                                    class="absolute -top-4 -right-3 text-white bg-[#677ff0] opacity-80 hover:opacity-100 p-2 rounded-full"
                                    x-tooltip.placement.right="@js(__('Change Payment Method'))"
                                >
                                    <x-lucide-edit  class="size-5" />
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <x-loader
                    wire:loading
                    wire:target="payRemainingAmount, payInstallmentAmount"
                />

                <div class="col-span-12 sm:col-span-6 lg:col-span-6 lg:mx-auto lg:max-w-lg">
                    <div class="grid grid-cols-2 items-center gap-2 sm:gap-5 text-white px-4 sm:px-5 mt-5 sm:mt-0">
                        <div>
                            <div class="flex items-center space-x-2">
                                <h2 class="text-xs md:text-base text-indigo-100 font-medium tracking-wide">
                                    {{ __('Settlement Balance') }}
                                </h2>
                            </div>
                            <div class="mt-1">
                                <p class="text-lg sm:text-xl lg:text-2xl font-bold">
                                    {{ Number::currency($this->negotiateAmount($consumer)) }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center space-x-2">
                                <h2 class="text-xs md:text-base text-indigo-100 font-medium tracking-wide">
                                    {{ __('Current Balance') }}
                                </h2>
                            </div>
                            <div class="mt-1">
                                <p class="text-lg sm:text-xl lg:text-2xl font-bold">
                                    {{ Number::currency((float) ($consumer->consumerNegotiation->payment_plan_current_balance ?? $this->negotiationCurrentAmount($consumer))) }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center space-x-2">
                                <h2 class="text-xs md:text-base text-indigo-100 font-medium tracking-wide">
                                    {{ __('Paid to Date') }}
                                </h2>
                            </div>
                            <div class="mt-1">
                                <p class="text-lg sm:text-xl lg:text-2xl font-bold">
                                    {{ Number::currency($this->negotiateAmount($consumer) - ($consumer->consumerNegotiation->payment_plan_current_balance ?? $this->negotiationCurrentAmount($consumer))) }}
                                </p>
                            </div>
                        </div>

                        @if ($consumer->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT)
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h2 class="text-xs md:text-base text-indigo-100 font-medium tracking-wide">
                                        {{ $consumer->consumerNegotiation->installment_type->displayName() . __(' Payment') }}
                                    </h2>
                                </div>

                                @php
                                    $monthlyAmount = $consumer->consumerNegotiation->counter_offer_accepted
                                        ? $consumer->consumerNegotiation->counter_monthly_amount
                                        : $consumer->consumerNegotiation->monthly_amount;
                                @endphp

                                <div class="mt-1">
                                    <p class="text-lg sm:text-xl lg:text-2xl font-bold">
                                        @if ($consumer->consumerNegotiation->installment_type === InstallmentType::MONTHLY)
                                            {{ Number::currency((float) $monthlyAmount) }}
                                        @elseif ($consumer->consumerNegotiation->installment_type === InstallmentType::BIMONTHLY)
                                            {{ Number::currency((float) $monthlyAmount) }}
                                        @elseif ($consumer->consumerNegotiation->installment_type === InstallmentType::WEEKLY)
                                            {{ Number::currency((float) $monthlyAmount) }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-span-12 mt-3 lg:mt-0 px-4 sm:px-5 lg:col-span-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-1 gap-4">
                        @if (
                            (float) ($consumer->consumerNegotiation->payment_plan_current_balance ?? $this->negotiationCurrentAmount($consumer)) > 0
                            && ((float) $consumer->current_balance) > 0
                            && $consumer->status !== ConsumerStatus::SETTLED
                        )
                            <div class="hidden lg:block">
                                <x-consumer.dialog x-model="openPaymentPlanPayOffDialog">
                                    <x-consumer.dialog.open>
                                        <button
                                            type="button"
                                            class="btn w-full rounded-lg border border-slate-300 px-4 text-xs sm:text-sm font-medium text-white hover:bg-gradient-to-br from-[#ffffff55] to-[#ffffff20]"
                                        >
                                            <div class="flex w-full items-center gap-x-3">
                                                <span class="text-left">
                                                    <x-lucide-wallet class="size-4.5" />
                                                </span>
                                                <span>{{ __('Payoff Current Balance') }}</span>
                                            </div>
                                        </button>
                                    </x-consumer.dialog.open>
                                    <x-consumer.dialog.panel :heading="__('Confirm Full Balance Payment')">
                                        <div class="border">
                                            <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold border-b">
                                                <h3 class="text-black">{{ __("Remaining Balance to Pay") }}</h3>
                                                <p class="text-primary">{{ Number::currency($scheduleTransactions->sum('amount')) }}</p>
                                            </div>
                                            <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold">
                                                <h3 class="text-black">{{ __("Payment Method") }}</h3>
                                                <p class="text-primary">
                                                    {{ $consumer->paymentProfile->method === MerchantType::CC
                                                        ? $consumer->paymentProfile->method->displayName() . ' (xx-' . $consumer->paymentProfile->last4digit . ')'
                                                        : $consumer->paymentProfile->method->displayName() . ' (xx-' . $consumer->paymentProfile->account_number . ')'
                                                    }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="p-3 text-sm">
                                            <p>{{__('You are about to pay the full remaining balance for your plan. Once completed, no further payments will be required.')}}</p>
                                            <p>{{__('Would you like to proceed?')}}</p>
                                        </div>

                                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-4">
                                            <x-dialog.close>
                                                <x-form.default-button
                                                    type="button"
                                                    class="w-full sm:w-32"
                                                >
                                                    {{ __('Cancel') }}
                                                </x-form.default-button>
                                            </x-dialog.close>
                                            <x-dialog.close>
                                                <x-form.button
                                                    type="button"
                                                    variant="primary"
                                                    wire:click="payRemainingAmount"
                                                    class="disabled:opacity-50 w-full sm:w-32"
                                                >
                                                    {{ __('Pay Now') }}
                                                </x-form.button>
                                            </x-dialog.close>
                                        </div>
                                    </x-consumer.dialog.panel>
                                </x-consumer.dialog>
                            </div>

                            <x-consumer.generate-payment-link :$consumer>
                                <button
                                    type="button"
                                    class="btn disabled:opacity-50 space-x-2 rounded-lg border border-slate-300 px-4 text-sm font-medium text-white hover:bg-gradient-to-br from-[#ffffff55] to-[#ffffff20] w-full"
                                >
                                    <div class="flex w-full items-center gap-x-3">
                                        <span class="text-left">
                                            <x-lucide-heart-handshake class="size-4.5" />
                                        </span>
                                        <span>{{ __('Helping Hand Link') }}</span>
                                    </div>
                                </button>
                            </x-consumer.generate-payment-link>
                        @endif

                        <div>
                            @if ($consumer->status !== ConsumerStatus::SETTLED)
                                <a
                                    wire:navigate
                                    href="{{ route('consumer.payment', ['consumer' => $consumer->id]) }}"
                                    class="btn disabled:opacity-50 space-x-2 rounded-lg border border-slate-300 px-4 text-sm font-medium text-white hover:bg-gradient-to-br from-[#ffffff55] to-[#ffffff20] w-full"
                                >
                                    <div class="flex w-full items-center gap-x-3">
                                        <span class="text-left">
                                            <x-lucide-square-pen class="size-4.5 text-[#ffffff]" />
                                        </span>
                                        <span>{{ __('Payment Method')  }}</span>
                                        <span class="block sm:hidden">
                                            {{  $consumer->paymentProfile->method === MerchantType::CC
                                                ? ' (xx-' . $consumer->paymentProfile->last4digit . ')'
                                                : ' (xx-' . $consumer->paymentProfile->account_number . ')'
                                            }}
                                        </span>
                                    </div>
                                </a>
                            @endif
                        </div>

                        <div class="hidden">
                            <div class="btn disabled:opacity-50 space-x-2 rounded-lg border border-slate-300 px-4 text-sm font-medium text-white hover:bg-gradient-to-br from-[#ffffff55] to-[#ffffff20] w-full">
                                <div class="flex w-full items-center gap-x-3">
                                    <span class="text-left">
                                        <x-lucide-pencil class="size-4.5 text-[#ffffff]" />
                                    </span>
                                    <span>{{ __('Recurring Payment Date')  }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                @class([
                    'absolute text-xs+ sm:left-0 text-white font-semibold badge rounded bg-gradient-to-r from-success to-success-focus',
                    '!from-error !to-error-focus' => $consumer->status === ConsumerStatus::HOLD,
                ])
            >
                {{ $consumer->status->displayLabel() }}
            </div>
        </div>

        @if ($scheduleTransactions->isNotEmpty())
            <div class="mt-8">
                <div class="flex flex-col mb-3 text-center text-slate-900 items-center">
                    <span class="text-base sm:text-xl font-semibold">
                        {{ __('Payment Schedule') }}
                    </span>
                    <span class="text-error font-bold text-xs sm:text-sm+">
                        {{ __(':scheduleTransactions payments to payoff!',  ['scheduleTransactions' => $scheduleTransactions->count()]) }}
                    </span>
                </div>
                <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                    <table class="w-full text-base text-left">
                        <thead>
                            <tr class="border-y border-slate-200 text-xs+ sm:text-xs">
                                <th class="whitespace-nowrap rounded-tl-lg border border-x border-slate-200 bg-slate-50 px-4 py-3 font-semibold uppercase text-slate-800">
                                    {{ __('Schedule Date') }}
                                </th>
                                <th class="whitespace-nowrap border border-x border-slate-200 bg-slate-50 px-4 py-3 font-semibold uppercase text-slate-800">
                                    {{ __('Amount') }}
                                </th>
                                <th class="whitespace-nowrap border border-x border-slate-200 bg-slate-50 px-4 py-3 font-semibold uppercase text-slate-800">
                                    {{ __('Status') }}
                                </th>
                                <th class="whitespace-nowrap rounded-tr-lg text-center border border-x border-slate-200 bg-slate-50 px-4 py-3 font-semibold uppercase text-slate-800">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($scheduleTransactions as $key => $scheduleTransaction)
                                <tr
                                    wire:key="{{ str()->random() }}"
                                    class="border-y border-slate-200 text-xs sm:text-base"
                                >
                                    <td class="whitespace-nowrap border-x px-4 py-3 sm:px-5">
                                        <span>{{ $scheduleTransaction->schedule_date->format('M d, Y') }}</span>
                                        @if ($previousScheduleTransactionDate = $scheduleTransaction->previous_schedule_date)
                                            <p class="text-success text-xs">
                                                {{ __('Originally scheduled on: ') . $previousScheduleTransactionDate->format('M d, Y') }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="border-x px-4 py-3 sm:px-5">
                                        <div class="flex items-center gap-x-2">
                                            {{ Number::currency((float) $scheduleTransaction->amount) }}
                                            @if ($scheduleTransaction->external_payment_profile_id)
                                                @svg('lucide-circle-help', [
                                                    'class' => 'size-5 text-black',
                                                    'x-tooltip.placement.bottom' => "'Partial payment by {$scheduleTransaction->externalPaymentProfile->first_name} {$scheduleTransaction->externalPaymentProfile->last_name}'"
                                                ])
                                            @endif
                                        </div>
                                    </td>
                                    <td class="border-x px-4 py-3 sm:px-5">
                                        <div class="flex space-x-2">
                                            <div
                                                @class([
                                                    'rounded-full font-semibold',
                                                    'text-error' => TransactionStatus::FAILED === $scheduleTransaction->status,
                                                    'text-info' => TransactionStatus::SCHEDULED === $scheduleTransaction->status,
                                                ])
                                            >
                                                @if ($consumer->status === ConsumerStatus::HOLD)
                                                    <span class="text-xs sm:text-base text-error">{{ __('Consumer Hold') }}</span>
                                                @else
                                                    {{ $scheduleTransaction->status->displayName() }}
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap border-x px-4 py-3 sm:px-5">
                                        <div class="inline-flex space-x-2">
                                            @if ($scheduleTransaction->status === TransactionStatus::FAILED && $consumer->status !== ConsumerStatus::SETTLED)
                                                <button
                                                    type="button"
                                                    wire:click="reschedule({{ $scheduleTransaction->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="reschedule({{ $scheduleTransaction->id }})"
                                                    class="btn text-xs sm:text-sm+ disabled:opacity-50 py-2 px-3 bg-success font-medium text-white hover:bg-success-focus focus:bg-success-focus active:bg-success-focus/90"
                                                >
                                                    <div
                                                        wire:loading.flex
                                                        wire:target="reschedule({{ $scheduleTransaction->id }})"
                                                        class="flex items-center gap-x-2"
                                                    >
                                                        <x-lucide-loader-2 class="size-5 animate-spin" />
                                                        <span>{{ __('Rescheduling...') }}</span>
                                                    </div>
                                                    <div
                                                        wire:loading.remove
                                                        wire:target="reschedule({{ $scheduleTransaction->id }})"
                                                    >
                                                        {{ __('Reschedule Today') }}
                                                    </div>
                                                </button>
                                            @else
                                                @if ($consumer->consumerNegotiation->negotiation_type !== NegotiationType::PIF)
                                                    <x-consumer.dialog>
                                                        <x-consumer.dialog.open>
                                                            <x-form.button
                                                                type="button"
                                                                variant="primary"
                                                                class="text-xs sm:text-sm+ py-2 px-3"
                                                            >
                                                                {{ __('Change Date') }}
                                                            </x-form.button>
                                                        </x-consumer.dialog.open>

                                                        <x-consumer.dialog.panel
                                                            size="xl"
                                                            :need-dialog-panel="false"
                                                        >
                                                            <x-slot name="heading">{{ __('Change Date') }}</x-slot>

                                                            <form
                                                                method="POST"
                                                                x-data="changeDate"
                                                                wire:submit="updateScheduleDate({{ $scheduleTransaction->id }})"
                                                                x-on:close-dialog.window="dialogOpen = false"
                                                                autocomplete="off"
                                                            >
                                                                <label class="relative mt-4 flex">
                                                                    <input
                                                                        wire:model="new_date"
                                                                        x-init="flatPickr('{{ $scheduleTransactions->get($key + 1)?->schedule_date->subDay()->toDateString() }}')"
                                                                        type="date"
                                                                        @class([
                                                                            'form-input peer w-full rounded-lg border bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary',
                                                                            'border-red-500' => $errors->has('new_date'),
                                                                            'border-slate-300' => $errors->missing('new_date'),
                                                                        ])
                                                                        autocomplete="off"
                                                                    >
                                                                    <span @class([
                                                                        'pointer-events-none absolute flex h-full w-10 items-center justify-center peer-focus:text-primary',
                                                                        'text-error' => $errors->has('new_date'),
                                                                        'text-slate-400' => $errors->missing('new_date'),
                                                                    ])>
                                                                        <x-lucide-calendar-days class="size-5 transition-colors duration-200" />
                                                                    </span>
                                                                </label>
                                                                @error('new_date')
                                                                    <div class="mt-1 text-error">
                                                                        <span>{{ $message }}</span>
                                                                    </div>
                                                                @enderror

                                                                <div class="flex items-end gap-x-2 justify-end mt-5">
                                                                    <x-consumer.dialog.close>
                                                                        <x-form.default-button type="button">
                                                                            {{ __('Cancel') }}
                                                                        </x-form.default-button>
                                                                    </x-consumer.dialog.close>
                                                                    <button
                                                                        type="submit"
                                                                        wire:loading.attr="disabled"
                                                                        wire:target="updateScheduleDate({{ $scheduleTransaction->id }})"
                                                                        wire:loading.class="opacity-50"
                                                                        class="btn border focus:border-info-focus bg-info disabled:opacity-50 text-center font-medium text-white hover:bg-info-focus focus:bg-info-focus active:bg-info-focus/90"
                                                                    >
                                                                        {{ __('Update') }}
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </x-consumer.dialog.panel>
                                                    </x-consumer.dialog>

                                                    <x-consumer.dialog>
                                                        <x-consumer.dialog.open>
                                                            <x-form.button
                                                                type="button"
                                                                variant="success"
                                                                class="text-xs sm:text-sm+ py-2 px-3"
                                                            >
                                                                <span>{{ __('Pay Now') }}</span>
                                                            </x-form.button>
                                                        </x-consumer.dialog.open>
                                                        <x-consumer.dialog.panel :heading="__('Confirm Payment')">
                                                            <div class="border">
                                                                <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold border-b">
                                                                    <h3 class="text-black">{{ __("Installment Amount") }}</h3>
                                                                    <p class="text-primary">{{ Number::currency((float) $scheduleTransaction->amount) }}</p>
                                                                </div>
                                                                <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold">
                                                                    <h3 class="text-black">{{ __("Payment Method") }}</h3>
                                                                    <p class="text-primary">{{ $scheduleTransaction->paymentProfile?->method->displayName() . ' (xx-' . $number($scheduleTransaction) . ')' }}</p>
                                                                </div>
                                                            </div>

                                                            <p class="p-3 text-sm">{{__('You are about to make an early installment payment. Do you want to continue?')}}</p>

                                                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-4">
                                                                <x-dialog.close>
                                                                    <x-form.default-button
                                                                        type="button"
                                                                        class="w-full sm:w-32"
                                                                    >
                                                                        {{ __('Cancel') }}
                                                                    </x-form.default-button>
                                                                </x-dialog.close>
                                                                <x-dialog.close>
                                                                    <x-form.button
                                                                        type="button"
                                                                        variant="primary"
                                                                        wire:click="payInstallmentAmount({{ $scheduleTransaction->id }})"
                                                                        class="disabled:opacity-50 w-full sm:w-32"
                                                                    >
                                                                        {{ __('Pay Now') }}
                                                                    </x-form.button>
                                                                </x-dialog.close>
                                                            </div>
                                                        </x-consumer.dialog.panel>
                                                    </x-consumer.dialog>
                                                @endif
                                            @endif

                                            @if (! $loop->last && $key === 0 && $consumer->status !== ConsumerStatus::SETTLED)
                                                <div>
                                                    <x-consumer.confirm-box
                                                        size="2xl"
                                                        :need-dialog-panel="false"
                                                    >
                                                        <x-slot name="heading">
                                                            <span class="text-lg sm:text-xl font-semibold text-black">
                                                                {{ __('Understanding Skip a Payment') }}
                                                            </span>
                                                        </x-slot>

                                                        <x-slot name="svg">
                                                            <div class="flex items-center justify-center">
                                                                <span class="text-4xl lg:text-8xl">🤓</span>
                                                            </div>
                                                        </x-slot>

                                                        <x-slot name="buttons">
                                                            <div class="flex flex-col sm:flex-row justify-center items-stretch sm:items-center gap-2">
                                                                <x-consumer.generate-payment-link :$consumer>
                                                                    <x-form.button
                                                                        variant="primary"
                                                                        type="button"
                                                                        x-on:click="openGenerateLink = true"
                                                                        class="w-full sm:w-auto"
                                                                    >
                                                                        {{ __('Helping Hand Link') }}
                                                                    </x-form.button>
                                                                </x-consumer.generate-payment-link>

                                                                <x-consumer.form.button
                                                                    type="button"
                                                                    variant="error"
                                                                    class="border focus:border-error-focus w-full sm:w-auto"
                                                                    wire:click="skipPayment({{ $scheduleTransaction->id }})"
                                                                    wire:target="skipPayment({{ $scheduleTransaction->id }})"
                                                                    wire:loading.attr="disabled"
                                                                >
                                                                    <div
                                                                        wire:loading.flex
                                                                        wire:target="skipPayment({{ $scheduleTransaction->id }})"
                                                                        class="flex items-center gap-x-2"
                                                                    >
                                                                        <x-lucide-loader-2 class="size-5 animate-spin" />
                                                                        <span>{{ __('Skipping...') }}</span>
                                                                    </div>
                                                                    <span wire:loading.remove>{{ __('Skip This Payment') }}</span>
                                                                </x-consumer.form.button>

                                                                <x-consumer.dialog.close>
                                                                    <x-form.default-button
                                                                        type="button"
                                                                        class="w-full sm:w-auto"
                                                                    >
                                                                        {{ __('Cancel') }}
                                                                    </x-form.default-button>
                                                                </x-consumer.dialog.close>
                                                            </div>
                                                        </x-slot>

                                                        <x-slot name="message">
                                                            <div class="text-sm space-y-3 mx-4 text-black text-start max-h-[40vh] sm:max-h-[20vh] md:max-h-[28vh] lg:max-h-full overflow-y-auto scroll-bar-visible">
                                                                <p>{{ __("When you skip a payment, it moves to the back of your payment plan and your next payment is still due on the scheduled due date (unless the date is changed).") }}</p>
                                                                <p>{{ __('YouNegotiate will never turn off your plan so you can jump back in when you are able.') }}</p>
                                                                <p>{{ __("Note that some creditors may pull your account to send to a collection agency or law firm if you skip and/or change the dates too many times, so please do your best to honor your win-win agreement!") }}</p>
                                                                <p>{{ __("Don't forget! You have a tax deductible helping hand link so anyone can make payments on your behalf and get the tax write off!! Debt Free gifting is the coolest new gift ever!!") }}</p>
                                                                <p class="text-lg font-semibold">{{ __("Cheers!") }}</p>
                                                                <div>
                                                                    <img src="{{ asset('images/dfa.png') }}" class="w-28">
                                                                </div>
                                                            </div>
                                                        </x-slot>

                                                        <button
                                                            type="button"
                                                            class="btn text-xs sm:text-sm+ py-2 px-3 bg-error font-medium text-white hover:bg-error-focus focus:bg-error-focus active:bg-error-focus/90"
                                                        >
                                                            {{ __('Skip') }}
                                                        </button>
                                                    </x-consumer.confirm-box>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($transactions->isNotEmpty())
            <div class="mt-8">
                <div class="flex">
                    <span class="mx-auto text-base sm:text-xl text-slate-900 my-3 font-semibold">{{ __('Processed Payments') }}</span>
                </div>

                <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                    <table class="w-full text-base text-left">
                        <thead>
                            <tr class="border-y border-slate-200 text-xs+ sm:text-xs">
                                <th class="whitespace-nowrap rounded-tl-lg border border-x border-slate-200 bg-slate-50 px-4 py-3 font-semibold uppercase text-slate-800">
                                    {{ __('Schedule Date') }}
                                </th>
                                <th class="whitespace-nowrap border border-x border-slate-200 bg-slate-50 px-4 py-3 font-semibold uppercase text-slate-800">
                                    {{ __('Processed Date') }}
                                </th>
                                <th class="whitespace-nowrap border border-x border-slate-200 bg-slate-50 px-4 py-3 font-semibold uppercase text-slate-800">
                                    {{ __('Transaction Id') }}
                                </th>
                                <th class="whitespace-nowrap border border-x border-slate-200 bg-slate-50 px-4 py-3 font-semibold uppercase text-slate-800">
                                    {{ __('Payment Method') }}
                                </th>
                                <th class="whitespace-nowrap border border-x border-slate-200 bg-slate-50 px-4 py-3 font-semibold uppercase text-slate-800">
                                    {{ __('Amount') }}
                                </th>
                                <th class="whitespace-nowrap rounded-tr-lg text-center border border-x border-slate-200 bg-slate-50 px-4 py-3 font-semibold uppercase text-slate-800">
                                    {{ __('Status') }}
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($transactions as $transaction)
                                <tr class="border-y border-slate-200 text-xs sm:text-base">
                                    <td class="whitespace-nowrap border-x px-4 py-3 sm:px-5">
                                        {{ $transaction->scheduleTransaction?->schedule_date->format('M d, Y') ?? 'N/A' }}
                                        @if ($transaction->transaction_type === TransactionType::PARTIAL_PIF)
                                            <p class="text-success text-xs">{{ __('Partial Payment') }}</p>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap border-x px-4 py-3 sm:px-5">
                                        {{ $transaction->created_at->formatWithTimezone() }}
                                        @if ($transaction->external_payment_profile_id)
                                            <p class="text-success text-xs">
                                                {!! __('Paid by <b>:name</b>', ['name' => $transaction->externalPaymentProfile->first_name . ' ' . $transaction->externalPaymentProfile->last_name]) !!}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap border-x px-4 py-3 sm:px-5">
                                        {{ $transaction->transaction_id }}
                                    </td>
                                    <td class="whitespace-nowrap border-x px-4 py-3 sm:px-5">
                                        @if ($transaction->payment_profile_id)
                                            {{ $transaction->paymentProfile->method->displayName() . ' (xx-' . $transactionNumber($transaction) . ')' }}
                                        @elseif ($transaction->external_payment_profile_id)
                                            {{ $transaction->externalPaymentProfile->method->displayName() . ' (xx-'. $externalTransactionNumber($transaction) . ')' }}
                                        @else
                                            {{ 'N/A' }}
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap border-x px-4 py-3 sm:px-5">
                                        {{ Number::currency((float) $transaction->amount) }}
                                    </td>
                                    <td class="whitespace-nowrap font-medium border-x px-4 py-3 sm:px-5">
                                        <div @class([
                                            'text-success' => $transaction->status === TransactionStatus::SUCCESSFUL,
                                            'text-error' => $transaction->status === TransactionStatus::FAILED
                                        ])>
                                            {{ 'Transaction ' . $transaction->status?->displayName() }}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach

                            @foreach ($cancelledScheduledTransactions as $cancelledScheduledTransaction)
                                <tr class="border-y border-slate-200 text-xs sm:text-base">
                                    <td class="whitespace-nowrap border-x px-4 py-3 sm:px-5">
                                        {{ $cancelledScheduledTransaction->schedule_date->format('M d, Y') ?? 'N/A' }}
                                    </td>
                                    <td class="whitespace-nowrap border-x px-4 py-3 sm:px-5">
                                        -
                                    </td>
                                    <td class="whitespace-nowrap border-x px-4 py-3 sm:px-5">
                                        -
                                    </td>
                                    <td class="whitespace-nowrap border-x px-4 py-3 sm:px-5">
                                        -
                                    </td>
                                    <td class="whitespace-nowrap border-x px-4 py-3 sm:px-5">
                                        {{ Number::currency((float) $cancelledScheduledTransaction->amount) }}
                                    </td>
                                    <td class="whitespace-nowrap font-medium border-x px-4 py-3 sm:px-5 text-slate-400">
                                        {{ __('Cancelled By Creditor') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </main>

    @script
        <script>
            Alpine.data('changeDate', () => ({
                flatPickrInstance: null,
                flatPickr(maxDate) {
                    this.flatPickrInstance = window.flatpickr(this.$el, {
                        altInput: true,
                        altFormat: 'm/d/Y',
                        dateFormat: 'Y-m-d',
                        allowInvalidPreload: true,
                        disableMobile: true,
                        minDate: @js(now()->addDay()->toDateString()),
                        maxDate,
                        ariaDateFormat: 'm/d/Y',
                        onReady: function (selectedDates, dateStr, instance) {
                            instance.input.setAttribute('placeholder', "{{ __('Select change date') }}")
                        },
                        onClose: function (selectedDates, dateStr, instance) {
                            $wire.new_date = dateStr
                        },
                    })
                },
                destroy() {
                    this.flatPickrInstance?.destroy()
                }
            }))
        </script>
    @endscript
</div>

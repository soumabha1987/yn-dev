@use('Illuminate\Support\Number')
@use('App\Enums\MerchantType')
@use('App\Enums\TransactionStatus')
@use('App\Enums\State')

<div>
    <main class="w-full pb-8">
        @if (session()->pull('complete-payment'))
            <x-consumer.complete-payment />
        @endif

        <div class="flex items-center space-x-4 py-5 lg:py-6">
            <h2 class="font-semibold text-slate-800 text-xl lg:text-2xl">
                {{ __('Complete Payment') }}
            </h2>
        </div>

        <div class="card px-4 pb-4 sm:px-5">
            <div class="my-10 flex flex-col items-center justify-center">
                <div>
                    <x-heroicon-o-check-circle class="inline size-24 sm:size-28 lg:size-40 text-primary" />
                </div>
                <span class="mt-4">
                    <h1 class="font-bold text-slate-700 text-lg lg:text-2xl">
                        {{ __('You\'re All Done, :consumerName', ['consumerName' => $consumer->first_name .' '.$consumer->last_name]) }}
                    </h1>
                </span>
                <span class="justify-center">
                    <p class="text-base text-slate-700">
                        {{ __('Congratulation on taking the steps to resolve your debt.') }}
                    </p>
                </span>
            </div>
        </div>

        <div class="flex items-center space-x-4 py-5 lg:py-6">
            <h2 class="font-semibold text-slate-800 text-xl lg:text-2xl">
                {{ __('Your next steps.') }}
            </h2>
        </div>

        <div class="card px-4 pb-4 sm:px-5">
            <div class="my-3 flex flex-col justify-between">
                <div class="flex items-start sm:items-center space-x-3">
                    <x-lucide-download class="text-black size-12 sm:size-16" />
                    <div class="w-full">
                        <p class="font-semibold text-slate-700 text-lg">
                            {{ __('Download') }}
                        </p>
                        <span class="text-slate-700 text-md">
                            {{ __('We suggest that you download and/or print a copy for your records. As always, you can log back into this account to view your payment summary. ') }}
                        </span>
                        <div class="flex mt-4 space-x-3">
                            <button
                                type="button"
                                wire:click="downloadAgreement({{ $consumer->id }})"
                                class="btn text-xs sm:text-sm bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
                            >
                            <div
                                wire:loading.flex
                                class="flex items-center gap-x-2"
                                wire:target="downloadAgreement({{ $consumer->id }})"
                            >
                                <x-lucide-loader-2 class="size-5 animate-spin" />
                                    {{ __('Downloading...') }}
                                </div>
                                <div
                                    class="flex items-center gap-x-2"
                                    wire:loading.remove
                                    wire:target="downloadAgreement({{ $consumer->id }})"
                                >
                                    <x-lucide-download class="size-4.5 text-slate-300" />
                                    <span>{{ __('Download Agreement') }}</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="flex items-start sm:items-center space-x-3 mt-8">
                    <x-lucide-circle-help class="text-black size-12 sm:size-16" />
                    <div class="w-full">
                        <p class="font-semibold text-slate-700 text-lg">
                            {{ __('Questions') }}
                        </p>
                        <span class="text-slate-700 text-md">
                            {{ __('For further questions contact our support center.') }}
                        </span>
                        <div class="flex flex-col sm:flex-row mt-4 sm:space-x-3">
                            <span class="text-slate-700 font-medium text-md">
                                {{ __('Collection Agency') }}:
                                <span class="font-semibold">{{ __('YouNegotiate') }}</span>
                            </span>

                            <span class="text-slate-700 font-medium text-md">
                                {{ __('Phone') }}:
                                <span class="font-semibold">(470) 205-4014</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center space-x-4 py-5 lg:py-6">
            <h2 class="font-semibold text-slate-800 text-xl lg:text-2xl">
                {{ __('Your Payment Summary') }}
            </h2>
        </div>

        <div class="grid grid-cols-1">
            <div class="card px-6 py-10 lg:px-16">
                <h2 class="text-xl lg:text-2xl font-semibold uppercase text-primary">
                    {{ __('Account Information') }}
                </h2>
                <div class="flex flex-col justify-between sm:flex-row">
                    <div class="space-y-1 text-left pt-2">
                        <p>
                            <span class="font-semibold">{{ __('Full Name') }}:</span>
                            <span class="capitalize">{{ $consumer->first_name . ' ' . $consumer->last_name }}</span>
                        </p>
                        <div class="flex items-center space-x-2">
                            <div>
                                <p>{{ $consumer->address1 }}</p>
                                <p>{{ $consumer->address2 }}</p>
                                <p>{{ $consumer->city. ', ' . $consumer->state . ', ' . $consumer->zip }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-1 text-left pt-2">
                        <div>
                            <span class="font-semibold"> {{__('Account Name')}}: </span>
                            <span class="capitalize">{{ $consumer->original_account_name }}</span>
                        </div>
                        <div>
                            <span class="font-semibold"> {{__('Account Number')}}: </span>
                            <span class="capitalize">{{ $consumer->account_number }}</span>
                        </div>
                    </div>
                </div>

                <div class="my-5 h-px bg-slate-200"></div>

                <div class="flex flex-col justify-between sm:flex-row">
                    <div class="text-left">
                        <h4 class="text-lg font-semibold uppercase text-primary">
                            {{ __('Payment Paid To') }}
                        </h4>
                        <div class="space-y-1 pt-2">
                            <div>
                                <span class="font-semibold">{{ __('Company Name') }}: </span>
                                <span class="capitalize">{{ $consumer->company->company_name }}</span>
                            </div>
                            <div>
                                <span class="font-semibold">{{ __('Member Account Number') }}: </span>
                                {{ $consumer->member_account_number ?? 'N/A' }}
                            </div>
                            <div>
                                <span class="font-semibold">{{ __('Payment Method') }}: </span>
                                {{ $consumer->paymentProfile?->method->displayName() ??  $consumer->externalPaymentProfile?->method->displayName() ?? 'N/A' }}
                            </div>
                        </div>
                    </div>
                    <div class="text-left mt-5 sm:mt-0">
                        <h4 class="text-lg font-semibold uppercase text-primary">
                            {{ __('Payments') }}
                        </h4>
                        <div class="space-y-1 pt-2">
                            <div>
                                <span class="font-semibold">{{ __('Original Balance') }}: </span>
                                {{ Number::currency((float) $consumer->total_balance) }}
                            </div>
                            <div>
                                <span class="font-semibold">{{ __('Negotiate Payoff Balance') }}: </span>
                                {{ Number::currency((float) $transactions->sum('amount')) }}
                            </div>
                            <p class="text-slate-700 text-md">
                                {{ __('View Agreement') }}
                                <span
                                    wire:click="downloadAgreement({{ $consumer->id }})"
                                    class="font-semibold cursor-pointer hover:underline text-primary"
                                >
                                    {{ __('Click here.') }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="my-5 h-px bg-slate-200"></div>

                @if ($transactions->isNotEmpty())
                    <hr class="my-5 h-px bg-slate-200">
                    <div class="is-scrollbar-hidden min-w-full overflow-x-auto my-5">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border border-slate-200 bg-slate-50 text-xs+ sm:text-xs">
                                    <th class="whitespace-nowrap border-r rounded-l-lg px-3 py-3 font-semibold uppercase text-slate-800 lg:px-5">
                                        {{ __('Payment Date') }}
                                    </th>
                                    <th class="whitespace-nowrap border-r px-4 py-3 font-semibold uppercase text-slate-800 lg:px-5">
                                        {{ __('Payment Method') }}
                                    </th>
                                    <th class="whitespace-nowrap border-r px-3 py-3 font-semibold uppercase text-slate-800 lg:px-5">
                                        {{ __('Amount') }}
                                    </th>
                                    <th class="whitespace-nowrap border-r px-3 py-3 font-semibold uppercase text-slate-800 lg:px-5">
                                        {{ __('Status') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transactions as $transaction)
                                    <tr class="border border-slate-200 text-xs sm:text-base">
                                        <td class="whitespace-nowrap border-r rounded-l-lg font-semibold px-4 py-3 sm:px-5">
                                            {{ $transaction->created_at->formatWithTimezone() }}
                                        </td>
                                        <td class="whitespace-nowrap border-r px-4 py-3 font-semibold text-primary sm:px-5">
                                            @if ($transaction->paymentProfile?->method === MerchantType::CC)
                                                CARD (xx-{{ $transaction->paymentProfile->last4digit }})
                                            @elseif ($transaction->paymentProfile?->method === MerchantType::ACH)
                                                ACH (xx-{{ $transaction->paymentProfile->account_number }})
                                            @elseif ($transaction->externalPaymentProfile?->method === MerchantType::CC)
                                                CARD (xx-{{ $transaction->externalPaymentProfile->last_four_digit }})
                                            @elseif ($transaction->externalPaymentProfile?->method === MerchantType::ACH)
                                                ACH (xx-{{ $transaction->externalPaymentProfile->account_number }})
                                            @endif
                                        </td>
                                        <td class="w-3/12 whitespace-nowrap border-r px-4 py-3 sm:px-5">
                                            {{ Number::currency((float) $transaction->amount) }}
                                        </td>
                                        <td class="w-3/12 whitespace-nowrap border-r px-4 py-3 sm:px-5">
                                            <div @class([
                                                'badge rounded-full border',
                                                'border-success text-success' => $transaction->status === TransactionStatus::SUCCESSFUL,
                                                'border-error text-error' => $transaction->status === TransactionStatus::FAILED,
                                                'border-info text-info' => $transaction->status === TransactionStatus::SCHEDULED,
                                                'border-warning text-warning' => $transaction->status === TransactionStatus::RESCHEDULED,
                                            ])>
                                                {{ $transaction->status->displayName() }}
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </main>
</div>

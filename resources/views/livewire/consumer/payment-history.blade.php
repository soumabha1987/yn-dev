@use('Illuminate\Support\Number')
@use('App\Enums\MerchantType')
@use('App\Enums\TransactionStatus')
@use('App\Models\Transaction')

@php
    $transactionNumber = function (Transaction $transaction) {
        if ($transaction->paymentProfile->method === MerchantType::CC) {
            return $transaction->paymentProfile->last4digit;
        }

        if ($transaction->paymentProfile->method === MerchantType::ACH) {
            return $transaction->paymentProfile->account_number;
        }
    };
@endphp

<div>
    <main class="w-full pb-8">
        <div class="grid grid-cols-8 gap-4 sm:gap-5 lg:gap-6">
            <div class="col-span-12 grid grid-cols-12 rounded-lg bg-gradient-to-r from-[#006FFA] to-[#677ff0] py-5 sm:py-6">
                <div class="col-span-12 sm:col-span-6 lg:col-span-4 2xl:col-span-3">
                    <div class="px-4 text-white sm:px-5">
                        <div>
                            <p class="text-indigo-100">{{ __('Account') }}</p>
                            <div class="mt-1 flex items-center space-x-2">
                                <p class="text-base sm:text-lg lg:text-2xl font-semibold">{{ '#' . $consumer->account_number }}</p>
                            </div>
                        </div>

                        <div class="mt-4 flex space-x-7">
                            <div>
                                <p class="text-indigo-100">{{ __('Creditor Name') }}</p>
                                <div class="mt-1 flex items-center space-x-2 hover:underline text-white hover:text-white">
                                    <x-consumer.creditor-details :$creditorDetails>
                                        <p class="text-base sm:text-lg font-medium cursor-pointer">
                                            {{ $creditorDetails['contact_person_name'] }}
                                        </p>
                                    </x-consumer.creditor-details>
                                </div>
                            </div>
                        </div>

                        <button
                            wire:click="downloadAgreement({{ $consumer->id }})"
                            type="button"
                            class="btn mt-4 space-x-2 rounded-lg border border-slate-300 px-4 text-sm font-medium text-white hover:bg-gradient-to-br from-[#ffffff55] to-[#ffffff20] w-full lg:w-auto"
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
                                class="flex items-center gap-x-2 text-xs sm:text-sm"
                                wire:loading.remove
                                wire:target="downloadAgreement({{ $consumer->id }})"
                            >
                                <x-lucide-download class="size-4.5 text-slate-300" />
                                <span>{{ __('Download Agreement(s)') }}</span>
                            </div>
                        </button>
                    </div>
                </div>

                <div class="col-span-12 sm:col-span-6 lg:col-span-4 2xl:col-span-6">
                    <div class="px-4 text-white mt-5 sm:mt-0 sm:px-5">
                        <div class="flex sm:flex-col justify-between">
                            <div>
                                <h2 class="text-base text-indigo-100 font-medium tracking-wide">{{ __('Original Balance') }}</h2>
                                <div class="mt-1">
                                    <p class="text-base sm:text-lg lg:text-2xl font-semibold">
                                        {{ Number::currency((float) $consumer->total_balance) }}
                                    </p>
                                </div>
                            </div>

                            <div class="sm:mt-4 flex space-x-7">
                                <div>
                                    <p class="text-indigo-100">{{ __('Current Balance') }}</p>
                                    <div class="mt-1 flex items-center space-x-2">
                                        <p class="text-base sm:text-lg lg:text-xl font-semibold">
                                            {{ Number::currency((float) $consumer->current_balance) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="items-center">
                                <span class="badge rounded-lg text-sm+ px-4 border text-white">
                                    {{ __('Creditor Removed') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-span-12 mt-5 lg:mt-0 sm:col-span-6 lg:col-span-4 2xl:col-span-3 px-4 sm:px-5">
                    <div class="xl:flex 2xl:block xl:justify-end">
                        <div class="relative h-40 w-auto xl:w-[17.5rem] 2xl:w-auto shrink-0 rounded-lg bg-gradient-to-br from-[#ffffff55] to-[#ffffff20]">
                            <div class="absolute inset-0 flex flex-col justify-between rounded-lg border border-white/10 p-5">
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
                                        <p class="mt-2 text-xs font-medium">
                                            {{ $consumer->paymentProfile->expirity }}
                                        </p>
                                    @endif
                                    @if ($consumer->paymentProfile->method === MerchantType::ACH)
                                        <p class="text-lg font-semibold tracking-wide">
                                            **** **** **** {{ $consumer->paymentProfile->account_number }}
                                        </p>
                                    @endif
                                    <p class="mt-1 text-sm font-medium">
                                        {{ $consumer->paymentProfile->fname . ' ' . $consumer->paymentProfile->lname }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($transactions->isNotEmpty())
            <div class="mt-8">
                <div class="flex">
                    <span class="mx-auto text-base sm:text-xl text-slate-900 my-3 font-semibold">{{ __('Completed Transaction On The Account') }}</span>
                </div>
                <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border border-slate-200 bg-slate-50 text-xs+ sm:text-xs">
                                <th class="whitespace-nowrap border-r rounded-tl-lg px-4 py-3 font-semibold uppercase text-slate-800 lg:px-5">
                                    {{ __('Schedule Date') }}
                                </th>
                                <th class="whitespace-nowrap border-r px-4 py-3 font-semibold uppercase text-slate-800 lg:px-5">
                                    {{ __('Processed Date') }}
                                </th>
                                <th class="whitespace-nowrap border-r px-4 py-3 font-semibold uppercase text-slate-800 lg:px-5">
                                    {{ __('Transaction Id') }}
                                </th>
                                <th class="whitespace-nowrap border-r px-4 py-3 font-semibold uppercase text-slate-800 lg:px-5">
                                    {{ __('Payment Method') }}
                                </th>
                                <th class="whitespace-nowrap border-r px-4 py-3 font-semibold uppercase text-slate-800 lg:px-5">
                                    {{ __('Amount') }}
                                </th>
                                <th class="whitespace-nowrap border-r rounded-tr-lg px-4 py-3 font-semibold uppercase text-slate-800 lg:px-5">
                                    {{ __('Status') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transactions as $key => $transaction)
                                <tr class="border border-slate-200 text-xs sm:text-base">
                                    <td class="whitespace-nowrap border-r px-4 py-3 sm:px-5">
                                        {{ $transaction->scheduleTransaction?->schedule_date->format('M d, Y') ?? 'N/A' }}
                                    </td>
                                    <td class="whitespace-nowrap border-r px-4 py-3 sm:px-5">
                                        {{ $transaction->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="whitespace-nowrap border-r px-4 py-3 sm:px-5">
                                        {{ $transaction->transaction_id }}
                                    </td>
                                    <td class="whitespace-nowrap border-r px-4 py-3 sm:px-5">
                                        @if ($transaction->paymentProfile)
                                            {{ $transaction->paymentProfile->method->displayName() . ' (xx-' . $transactionNumber($transaction) . ')' }}
                                        @else
                                            {{ 'N/A' }}
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap border-r px-4 py-3 sm:px-5">
                                        {{ Number::currency((float) $transaction->amount) }}
                                    </td>
                                    <td class="whitespace-nowrap border-r px-4 py-3 sm:px-5">
                                        <div
                                            @class([
                                                'badge rounded-full border',
                                                'border-success text-success' => $transaction->status === TransactionStatus::SUCCESSFUL,
                                                'border-error text-error' => $transaction->status === TransactionStatus::FAILED,
                                                'border-info text-info' => $transaction->status === TransactionStatus::SCHEDULED,
                                                'border-warning text-warning' => $transaction->status === TransactionStatus::RESCHEDULED,
                                            ])
                                        >
                                            {{ __('Transaction :transactionStatus', ['transactionStatus' => $transaction->status?->displayName()]) }}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </main>
</div>

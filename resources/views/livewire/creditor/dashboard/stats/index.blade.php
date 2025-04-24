@use('Illuminate\Support\Number')

<div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <a
            wire:navigate
            href="{{ route('manage-consumers') }}"
            class="card rounded-2xl px-4 py-8 sm:px-5 hover:outline outline-secondary"
        >
            <div class="flex flex-col h-full justify-between">
                <div class="flex space-x-3 items-start">
                    <div class="mask is-hexagon flex size-14 rounded shrink-0 items-center justify-center bg-secondary">
                        <x-heroicon-o-user-group class="text-white w-7" />
                    </div>

                    <h2 class="text-base font-semibold tracking-wide text-black">
                        {{ __('Total Accounts') }}
                    </h2>
                </div>
                <div class="py-4">
                    <p>{{ __('Total Accounts and Balance') }}</p>
                </div>
                <div class="flex justify-between items-start gap-4">
                    <p class="text-2xl font-bold text-black">
                        <span class="text-black">{{ $this->stats['consumer']['total_count'] }}</span>
                        <span class="text-slate-600 italic text-xs">&nbsp;and&nbsp;</span>
                        <span class="text-black">{{ Number::currency((float) $this->stats['consumer']['total_balance_count']) }}</span>
                    </p>
                </div>
            </div>
        </a>

        <a
            wire:navigate
            href="{{ route('creditor.dashboard.payment-plan') }}"
            class="card rounded-2xl px-4 py-8 sm:px-5 hover:outline outline-secondary-green"
        >
            <div class="flex flex-col h-full justify-between">
                <div class="flex space-x-3 items-start">
                    <div class="mask is-hexagon flex size-14 rounded shrink-0 items-center justify-center bg-secondary-green">
                        <x-lucide-calendar-clock class="text-white size-7" />
                    </div>

                    <h2 class="text-base font-semibold tracking-wide text-black">
                        {{ __('Total Payment Plans') }}
                    </h2>
                </div>
                <div class="py-4">
                    <p>
                       {{ __("Number of payment plans in YouNegotiate") }}
                    </p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-black">{{ $this->stats['consumer']['accepted_count'] ?? 0 }}</p>
                </div>
            </div>
        </a>

        <a
            wire:navigate
            href="{{ route('creditor.dashboard.payment-forecast') }}"
            class="card rounded-2xl px-4 py-8 sm:px-5 hover:outline outline-accent"
        >
            <div class="flex flex-col h-full justify-between">
                <div class="flex space-x-3 items-start">
                    <div class="mask is-hexagon flex size-14 rounded shrink-0 items-center justify-center bg-accent">
                        <x-lucide-hourglass class="text-white w-7" />
                    </div>

                    <h2 class="text-base font-semibold tracking-wide text-black">
                        {{ __('Forecasted Payments') }}
                    </h2>
                </div>
                <div class="py-4">
                    <p>
                        {{ __("Upcoming payments due within the next 30 days") }}
                    </p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-black">
                        {{ Number::currency((float) $this->stats['scheduleTransaction']['scheduled_payments'] ?? 0) }}
                    </p>
                </div>
            </div>
        </a>

        <a
            wire:navigate
            href="{{ route('creditor.dashboard.successful-transaction') }}"
            class="card rounded-2xl px-4 py-8 sm:px-5 hover:outline outline-success"
        >
            <div class="flex flex-col h-full justify-between">
                <div class="flex space-x-3 items-start">
                    <div class="mask is-hexagon flex size-14 rounded shrink-0 items-center justify-center bg-success">
                        <x-lucide-check-check class="text-white w-7" />
                    </div>

                    <h2 class="text-base font-semibold tracking-wide text-black">
                        {{ __('Successful Payments') }}
                    </h2>
                </div>
                <div class="py-4">
                    <p>
                        {{ __('Payments successfully processed within the last 30 days') }}
                    </p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-black">
                        {{ Number::currency((float) $this->stats['transaction']['successful_payments'] ?? 0) }}
                    </p>
                </div>
            </div>
        </a>

        <a
            wire:navigate
            href="{{ route('creditor.dashboard.failed-transaction') }}"
            class="card rounded-2xl px-4 py-8 sm:px-5 hover:outline outline-error"
        >
            <div class="flex flex-col h-full justify-between">
                <div class="flex space-x-3 items-start">
                    <div class="mask is-hexagon flex size-14 rounded shrink-0 items-center justify-center bg-error">
                        <x-lucide-circle-x class="text-white w-7" />
                    </div>

                    <h2 class="text-base font-semibold tracking-wide text-black">
                        {{ __('Failed Payments') }}
                    </h2>
                </div>
                <div class="py-4">
                    <p>
                        {{ __('Payments that failed to process within last 30 days') }}
                    </p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-black">
                        {{ Number::currency((float) $this->stats['scheduleTransaction']['failed_payments'] ?? 0) }}
                    </p>
                </div>
            </div>
        </a>
    </div>
</div>

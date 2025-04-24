@use('Illuminate\Support\Number')
@use('App\Enums\MembershipFrequency')

@php
    $currentMembershipEndDate = $currentCompanyMembership->current_plan_end;
@endphp

<div>
    <div class="card">
        <div class="flex items-center space-x-2 py-3 px-5">
            <h2 class="text-md text-black font-semibold lg:text-lg">
                {{ __('Membership Plan') }}
            </h2>
            @if ($isLastTransactionFailed)
                <div class="badge bg-warning text-white font-semibold rounded-full">
                    {{ __('Failed Payment') }}
                </div>
            @elseif ($currentMembershipEndDate < now())
                <div class="badge bg-warning text-white font-semibold rounded-full">
                    {{ __('Cancelled') }}
                </div>
            @else
                <div class="badge bg-success text-white font-semibold rounded-full">
                    {{ __('Active') }}
                </div>
            @endif
        </div>

        <div
            x-data="{ tilledErrorMessage: '' }"
            class="grid grid-cols-12 items-center gap-6 px-4 mb-3"
        >
            <div
                x-modelable="tilledErrorMessage"
                wire:model="tilledErrorMessage"
                class="col-span-12 rounded-lg p-3 sm:col-span-6 lg:col-span-8"
                x-on:scroll-tilled-error-message.window="$el.scrollIntoView({ behavior: 'smooth', block: 'center' })"
            >
                <div
                    x-show="tilledErrorMessage"
                    class="py-3"
                >
                    <div class="alert flex rounded-lg border border-error/30 bg-error/10 py-4 px-4 text-error sm:px-5 items-center">
                        <div x-text="tilledErrorMessage" class="flex-grow"></div>
                        <button
                            type="button"
                            class="text-error text-xl leading-none focus:outline-none"
                            @click="tilledErrorMessage = ''"
                        >
                            <x-lucide-x class="size-5" />
                        </button>
                    </div>
                </div>

                <x-membership-current-plan-status
                    :name="$currentMembership->name"
                    :end-date="$currentMembershipEndDate"
                    :is-auto-renew-plan="$currentCompanyMembership->auto_renew"
                >
                    @php
                        $confirmBoxData = [
                            'action' => null,
                            'okButtonLabel' => null,
                            'message' => null,
                            'buttonText' => null,
                            'extraClasses' => '',
                        ];
                        $note = null;

                        if ($isLastTransactionFailed) {
                            $confirmBoxData = [
                                'action' => "activePlan({$currentMembership->id})",
                                'okButtonLabel' => __('Reprocess Payment'),
                                'heading' => 'Reprocess Failed Payment',
                                'message' => __('Are you sure you want to reprocess the failed payment? We will attempt to process :price from your account.', [
                                    'price' => '<b class="text-primary">' . Number::currency((float) $currentMembership->price) . '</b>',
                                ]),
                                'buttonText' => __('Reprocess'),
                                'extraClasses' => 'btn text-white bg-primary hover:bg-primary-400',
                            ];
                            $note = __('Your membership is inactive due to a failed payment. Click here to reprocess your payment.');
                        } elseif ($currentCompanyMembership->next_membership_plan_id !== null) {
                            $confirmBoxData = [
                                'action' => 'nextPlanUpdate',
                                'okButtonLabel' => __('Confirm'),
                                'heading' => 'Subscription Cancellation Scheduled',
                                'message' => __('You have scheduled the cancellation of your :planName subscription. Your subscription will remain active until :cancellationDate, after which you will lose access to all features associated with this plan.', [
                                    'planName' => '<b class="text-primary">' . $currentCompanyMembership->nextMembershipPlan->name . '</b>',
                                    'cancellationDate' => '<b class="text-primary">' . $currentCompanyMembership->current_plan_end->formatWithTimezone() . '</b>',
                                ]),
                                'buttonText' => __('Cancel Downgrade Plan'),
                            ];
                            $note = __('Your plan will be downgraded to :nextPlanName on :planEndDate and you will be charged :nextPlanPrice.', [
                                'nextPlanName' => '<b>' . $currentCompanyMembership->nextMembershipPlan->name . '</b>',
                                'planEndDate' => '<b>' . $currentMembershipEndDate->formatWithTimezone() . '</b>',
                                'nextPlanPrice' => '<b>' . Number::currency((float) $currentCompanyMembership->nextMembershipPlan->price) . '</b>',
                            ]);
                        } elseif ($currentCompanyMembership->auto_renew) {
                            $confirmBoxData = [
                                'message' => __('Hold on! Cancelling your :planName plan means losing access to its features. Have you considered upgrading to a different plan instead?', [
                                    'planName' => '<b class="text-primary">' . $currentMembership->name . '</b>',
                                ]),
                                'buttonText' => __('Cancel Plan (end of billing period)'),
                                'isCancelPlan' => true,
                            ];
                            $days = now()->diffInDays($currentMembershipEndDate ?? 0);
                            $note = __('Your current plan :planName will renew on :planEndDate (in :days) for :planPrice.', [
                                'planName' => '<b>' . $currentMembership->name . '</b>',
                                'planEndDate' => '<b>' . $currentMembershipEndDate->formatWithTimezone() . '</b>',
                                'planPrice' => '<b>' . Number::currency((float) $currentMembership->price) . '</b>',
                                'days' => '<b>' . ($days > 0 ? $days .' days' : 'today') . '</b>',
                            ]);
                        } elseif ($currentMembershipEndDate->lte(today())) {
                            $confirmBoxData = null;
                            $note = __('Your membership is expired. Please choose your plan and start membership with YouNegotiate.');
                        } else {
                            $confirmBoxData = [
                                'action' => 'undoCancelled',
                                'heading' => 'Resume Your Plan?',
                                'okButtonLabel' => __('Yes, Resume Plan'),
                                'message' => __('Your YouNegotiate plan :planName is active until :planEndDate. To continue enjoying all its features, consider renewing your plan before the expiration date.', [
                                    'planName' => '<b class="text-primary">' . $currentMembership->name . '</b>',
                                    'planEndDate' => '<b class="text-primary">' . $currentMembershipEndDate->formatWithTimezone() . '</b>',
                                ]),
                                'buttonText' => __('Undo Cancellation'),
                            ];
                            $note = __('Your current plan :planName will expire on :planEndDate. After this date, you won\'t be able to access YouNegotiate unless you choose to resume your plan.', [
                                'planName' => '<b>' . $currentMembership->name . '</b>',
                                'planEndDate' => '<b>' . $currentMembershipEndDate->formatWithTimezone() . '</b>',
                            ]);
                        }
                    @endphp

                    @if ($confirmBoxData)
                        <x-slot name="button">
                            @if ($confirmBoxData['isCancelPlan'] ?? false)
                                <x-dialog>
                                    <x-dialog.open>
                                        <x-form.button
                                            class="text-sm+ sm:mb-4 lg:mb-0"
                                            type="button"
                                            variant="primary"
                                        >
                                            <span>{{ $confirmBoxData['buttonText'] }}</span>
                                        </x-form.button>
                                    </x-dialog.open>
                                    <x-dialog.panel confirm-box>
                                        <x-slot name="headerCancelIcon">
                                            <div class="flex justify-end">
                                                <button
                                                    type="button"
                                                    x-on:click="$dialog.close()"
                                                    x-on:close.window="dialogOpen = false"
                                                    class="btn size-10 text-black rounded-full p-2 hover:bg-slate-300/20"
                                                >
                                                    <x-heroicon-o-x-mark class="size-8 fill-white text-black" />
                                                </button>
                                            </div>
                                        </x-slot>
                                        <x-slot name="svg">
                                            <span class="text-6xl">😢</span>
                                        </x-slot>
                                        <x-slot name="message">
                                            {!! $confirmBoxData['message'] !!}
                                        </x-slot>

                                        <x-slot name="buttons">
                                            <div class="grid grid-cols-2 gap-2">
                                                <button
                                                    wire:click="cancelAutoRenewPlan"
                                                    wire:target="cancelAutoRenewPlan"
                                                    wire:loading.class="opacity-50"
                                                    wire:loading.attr="disabled"
                                                    type="button"
                                                    class="btn select-none bg-success/10 border border-success text-success focus:text-white active:text-white hover:text-white hover:bg-success-focus focus:bg-success-focus active:bg-success-focus/90"
                                                >
                                                    <div class="flex-col gap-2">
                                                        <div>{{ __('Keep My User Profile') }}</div>
                                                        <div class="text-xs+"> {{ __('I might reinstate plan later') }}</div>
                                                    </div>
                                                </button>

                                                <button
                                                    wire:click="cancelAutoRenewPlan('true')"
                                                    wire:target="cancelAutoRenewPlan"
                                                    wire:loading.class="opacity-50"
                                                    wire:loading.attr="disabled"
                                                    type="button"
                                                    class="btn select-none text-error bg-error/10 border border-error focus:text-white active:text-white hover:text-white hover:bg-error-focus focus:bg-error-focus active:bg-error-focus/90"
                                                >
                                                    <div class="flex-col gap-2">
                                                        <div>{{ __('Cancel My User Profile End of Billing Period') }}</div>
                                                        <div class="text-xs+"> {{ __('I will create a new profile if needed') }} </div>
                                                    </div>
                                                </button>
                                            </div>
                                        </x-slot>
                                    </x-dialog.panel>
                                </x-dialog>
                            @else
                            <x-dialog wire:model="dialogOpen">
                                <x-dialog.open>
                                    <x-form.button
                                        type="button"
                                        variant="primary"
                                        class="{{ $confirmBoxData['extraClasses'] ?? '' }}"
                                        wire:target="{{ $confirmBoxData['action'] }}"
                                        wire:loading.attr="disabled"
                                        wire:loading.class="disabled:opacity-50"
                                    >
                                        <span>{{ $confirmBoxData['buttonText'] }}</span>
                                    </x-form.button>
                                </x-dialog.open>
                                <x-dialog.panel :heading="$confirmBoxData['heading']">
                                    @if($confirmBoxData['action'] === 'nextPlanUpdate')
                                        <div class="border mb-4">
                                            <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold border-b">
                                                <h3 class="text-black">{{ __("Current Plan") }}</h3>
                                                <p class="text-primary">{{ $currentCompanyMembership->nextMembershipPlan->name }}</p>
                                            </div>
                                            <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold">
                                                <h3 class="text-black">{{ __("Billing Cycle Ends On") }}</h3>
                                                <p class="text-primary">{{ $currentCompanyMembership->current_plan_end->formatWithTimezone() }}</p>
                                            </div>
                                        </div>
                                    @elseif($confirmBoxData['action'] === "activePlan({$currentMembership->id})")
                                        <div class="border mb-4">
                                            <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold border-b">
                                                <h3 class="text-black">{{ __("Amount") }}</h3>
                                                <p class="text-primary">{{ Number::currency((float) $currentMembership->price) }}</p>
                                            </div>
                                            <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold">
                                                <h3 class="text-black">{{ __("Payment Method") }}</h3>
                                                <p class="text-primary">CARD (xx-{{ $membershipPaymentProfile?->last_four_digit }})</p>
                                            </div>
                                        </div>
                                    @endif
                                    <p>{!! $confirmBoxData['message'] !!}</p>
                                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-2 pt-4">
                                        <x-dialog.close>
                                            <x-form.default-button
                                                type="button"
                                                class="w-full sm:w-auto"
                                            >
                                                {{ __('Cancel') }}
                                            </x-form.default-button>
                                        </x-dialog.close>
                                        <x-dialog.close>
                                            <x-form.button
                                                type="button"
                                                variant="primary"
                                                class="{{ ($confirmBoxData['extraClasses'] ?? '') . ' w-full sm:w-auto' }}"
                                                wire:click="{{ $confirmBoxData['action'] }}"
                                                wire:target="{{ $confirmBoxData['action'] }}"
                                                wire:loading.attr="disabled"
                                                wire:loading.class="disabled:opacity-50"
                                            >
                                                {{ $confirmBoxData['okButtonLabel'] }}
                                            </x-form.button>
                                        </x-dialog.close>
                                    </div>
                                </x-dialog.panel>
                            </x-dialog>
                            @endif
                        </x-slot>
                    @endif

                    @if ($note)
                        <x-slot name="note">
                            <span class="text-gray-700 text-sm">
                                {!! $note !!}
                            </span>
                        </x-slot>
                    @endif
                </x-membership-current-plan-status>
            </div>
            <div class="col-span-12 sm:col-span-6 lg:col-span-4 p-3">
                <livewire:creditor.membership-settings.account-details />
            </div>
        </div>

        <div class="flex-row grid pb-5 justify-center items-center">
            <div class="text-center mb-4">
                <h3 class="mt-1 text-xl font-semibold text-black">
                    {{ __('Choose the plan that fits your needs') }}
                </h3>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 sm:gap-5 lg:gap-6 px-8">
                <div class="rounded-xl border-4 border-primary p-1">
                    <div class="flex flex-col justify-between relative h-full rounded-xl bg-slate-50 p-3 text-center">
                        <div class="absolute top-0 right-0 p-3">
                            <div class="badge rounded-full px-2 text-info bg-info/10">
                                {{ __('Current Plan') }}
                            </div>
                        </div>
                        <div class="mt-4">
                            <h4 class="text-xl font-semibold text-slate-700">
                                {{ str($currentMembership->name)->title() }}
                            </h4>
                            <span
                                x-tooltip.placement.bottom="'{{ $currentMembership->description }}'"
                                class="mt-2 line-clamp-2 hover:underline hover:cursor-pointer"
                            >
                                {{ str($currentMembership->description) }}
                            </span>
                        </div>
                        <div class="mt-4 flex flex-col">
                            <span class="text-4xl tracking-tight text-primary">
                                {{ Number::currency((float) $currentMembership->price) }}
                            </span>/ {{ $currentMembership->frequency->displayName() }}
                        </div>
                        <div class="mt-5 space-y-1 text-left">
                            <div class="flex items-start space-x-3 space-x-reverse">
                                <div class="flex size-6 shrink-0 items-center justify-center rounded-full">
                                    <x-heroicon-m-check class="size-4.5 text-success" />
                                </div>
                                <span class="font-medium text-black">
                                    <span class="font-medium">
                                        {{ __('Upload account limit :accounts', ['accounts' => $currentMembership->upload_accounts_limit]) }}
                                    </span>
                                </span>
                            </div>
                            <div class="flex items-start space-x-3 space-x-reverse">
                                <div class="flex size-6 shrink-0 items-center justify-center rounded-full">
                                    <x-heroicon-m-check class="size-4.5 text-success" />
                                </div>
                                <span class="font-medium text-black">
                                    {{ __(':fees fee on all consumer payments', ['fees' => Number::percentage($currentMembership->fee, 2)]) }}
                                </span>
                            </div>
                            <div class="flex items-start space-x-3 space-x-reverse">
                                <div class="flex size-6 shrink-0 items-center justify-center rounded-full">
                                    <x-heroicon-m-check class="size-4.5 text-success" />
                                </div>
                                <span class="font-medium text-black">
                                    <span class="font-medium">
                                        {{ __(':amount per e-letter', ['amount' => Number::currency((float) $currentMembership->e_letter_fee)]) }}
                                    </span>
                                </span>
                            </div>
                            @foreach ($currentMembership->enableFeatures as $name => $value)
                                <div class="flex items-start space-x-reverse">
                                    <div class="flex size-6 shrink-0 items-center justify-center rounded-full">
                                        <x-heroicon-m-check class="size-5 text-success" />
                                    </div>
                                    <span class="font-medium text-black">
                                        {{ $value }}
                                    </span>
                                </div>
                            @endforeach
                            @foreach ($currentMembership->disableFeatures as $name => $value)
                                <div class="flex items-start space-x-reverse">
                                    <div class="flex size-6 shrink-0 items-center justify-center rounded-full">
                                        <x-heroicon-m-x-mark class="size-5 text-error" />
                                    </div>
                                    <span class="font-medium">
                                        {{ $value }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4">
                            @if ($currentMembershipEndDate->lt(now()) && $currentMembership->status)
                                <x-dialog>
                                    <x-dialog.open>
                                        <x-form.button
                                            type="button"
                                            variant="primary"
                                            @class([
                                                'btn text-white rounded-full bg-primary hover:!bg-primary-400',
                                            ])
                                        >
                                            <div class="flex space-x-1 items-center">
                                                <span>{{ __('Choose Plan') }}</span>
                                            </div>
                                        </x-form.button>
                                    </x-dialog.open>
                                    <x-dialog.panel :heading="__('Confirm Plan Selection?')">
                                        <div class="border mb-4">
                                            <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold border-b">
                                                <h3 class="text-black">{{ __("Selected Plan") }}</h3>
                                                <p class="text-primary">{{ str($currentMembership->name)->title() }}</p>
                                            </div>
                                            <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold border-b">
                                                <h3 class="text-black">{{ __("Amount") }}</h3>
                                                <p class="text-primary">{{ Number::currency((float) $currentMembership->price) }}</p>
                                            </div>
                                            <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold border-b">
                                                <h3 class="text-black">{{ __("Billing Cycle") }}</h3>
                                                <p class="text-primary">{{ $currentMembership->frequency }}</p>
                                            </div>
                                            <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold">
                                                <h3 class="text-black">{{ __("Payment Method") }}</h3>
                                                <p class="text-primary">CARD (xx-{{ $membershipPaymentProfile?->last_four_digit }})</p>
                                            </div>
                                        </div>
                                        <p>{!! __("Your previous membership expired due to a failed payment. Are you sure you want to choose this plan? We will process :amount from your account to activate your membership.", ['amount' => '<span class="font-bold text-primary">' . Number::currency((float) $currentMembership->price) . '</span>']) !!}</p>
                                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-2 pt-4">
                                            <x-dialog.close>
                                                <x-form.default-button
                                                    type="button"
                                                    class="w-full sm:w-auto"
                                                >
                                                    {{ __('Cancel') }}
                                                </x-form.default-button>
                                            </x-dialog.close>
                                            <x-dialog.close>
                                                <x-form.button
                                                    type="button"
                                                    variant="primary"
                                                    wire:click="activePlan({{ $currentMembership->id }})"
                                                    class="w-full sm:w-auto"
                                                >
                                                    {{ __('Confirm & Pay') }}
                                                </x-form.button>
                                            </x-dialog.close>
                                        </div>
                                    </x-dialog.panel>
                                </x-dialog>
                            @else
                                <button
                                    type="button"
                                    disabled
                                    class="btn text-white disabled:opacity-50 rounded-full bottom-0 cursor-none bg-primary hover:bg-primary-400"
                                >
                                    {{ __('Current Plan') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
                @foreach ($memberships as $key => $membership)
                    @if ($membership->id !== $currentMembership->id)
                        <div class="rounded-xl border border-slate-400 p-1">
                            <div class="flex flex-col justify-between rounded-xl h-full bg-slate-50 p-3 text-center">
                                <div class="mt-4">
                                    <h4 class="text-xl font-semibold text-slate-700">
                                        {{ str($membership->name)->title() }}
                                    </h4>
                                    <span
                                        x-tooltip.placement.bottom="'{{ $membership->description }}'"
                                        class="mt-2 line-clamp-2 hover:underline hover:cursor-pointer"
                                    >
                                        {{ str($membership->description) }}
                                    </span>
                                </div>
                                <div class="mt-4 flex flex-col">
                                    <span class="text-4xl tracking-tight text-primary">
                                        {{ Number::currency((float) $membership->price) }}
                                    </span>/ {{ $membership->frequency->displayName() }}
                                </div>
                                <div class="mt-5 space-y-1 text-left">
                                    <div class="flex items-start space-x-3 space-x-reverse">
                                        <div class="flex size-6 shrink-0 items-center justify-center rounded-full">
                                            <x-heroicon-m-check class="size-4.5 text-success" />
                                        </div>
                                        <span class="font-medium text-black">
                                            {{ __('Upload account limit :accounts', ['accounts' => $membership->upload_accounts_limit]) }}
                                        </span>
                                    </div>
                                    <div class="flex items-start space-x-3 space-x-reverse">
                                        <div class="flex size-6 shrink-0 items-center justify-center rounded-full">
                                            <x-heroicon-m-check class="size-4.5 text-success" />
                                        </div>
                                        <span class="font-medium text-black">
                                            {{ __(':fees fee on all consumer payments', ['fees' => Number::percentage($membership->fee, 2)]) }}
                                        </span>
                                    </div>
                                    <div class="flex items-start space-x-3 space-x-reverse">
                                        <div class="flex size-6 shrink-0 items-center justify-center rounded-full">
                                            <x-heroicon-m-check class="size-4.5 text-success" />
                                        </div>
                                        <span class="font-medium text-black">
                                            {{ __(':amount per e-letter', ['amount' => Number::currency((float) $membership->e_letter_fee)]) }}
                                        </span>
                                    </div>
                                    @foreach ($membership->enableFeatures as $name => $value)
                                        <div class="flex items-start space-x-reverse">
                                            <div class="flex size-6 shrink-0 items-center justify-center rounded-full">
                                                <x-heroicon-m-check class="size-4.5 text-success" />
                                            </div>
                                            <span class="font-medium text-black">
                                                {{ $value }}
                                            </span>
                                        </div>
                                    @endforeach
                                    @foreach ($membership->disableFeatures as $name => $value)
                                        <div class="flex items-start space-x-reverse">
                                            <div class="flex size-6 shrink-0 items-center justify-center rounded-full">
                                                <x-heroicon-m-x-mark class="size-4.5 text-error" />
                                            </div>
                                            <span class="font-medium">
                                                {{ $value }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mt-8">
                                    @if ($currentMembershipEndDate->gt(now()) && $currentMembership->price_per_day > 0)
                                        <x-dialog>
                                            <x-dialog.open>
                                                <button
                                                    type="button"
                                                    class="btn rounded-full text-white bg-primary hover:bg-primary-400"
                                                >
                                                    {{ $membership->price_per_day > $currentMembership->price_per_day ? __('Upgrade Plan') : __('Downgrade Plan') }}
                                                </button>
                                            </x-dialog.open>
                                            <x-dialog.panel size="xl">
                                                <x-slot name="heading">
                                                    {{ $membership->price_per_day > $currentMembership->price_per_day ? __('Upgrade Plan') : __('Downgrade Plan') }}
                                                </x-slot>
                                                <div>
                                                    <div class="border-t border-x">
                                                        <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold border-b">
                                                            <h3 class="text-black">{{ __('Current Plan') }}</h3>
                                                            <p class="text-primary">{{ $currentMembership->name }}</p>
                                                        </div>
                                                        <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold">
                                                            <h3 class="text-black">{{ __('New plan') }}</h3>
                                                            <p class="text-primary">{{ $membership->name }}</p>
                                                        </div>
                                                    </div>
                                                    @if ($membership->price_per_day > $currentMembership->price_per_day)
                                                        @if ($membership->new_plan_date !== null)
                                                            <div class="border mb-4">
                                                                <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold">
                                                                    <h3 class="text-black">{{ __('New plan End Date') }}</h3>
                                                                    <p class="text-primary">{{ $membership->new_plan_date }}</p>
                                                                </div>
                                                            </div>
                                                            <p>
                                                                {!! __('Your current plan is set to end on :planDate. However, if you upgrade to :selectPlanName, the upgrade will take effect immediately, and your current plan will end right away.', ['planDate' => '<span class="font-bold text-primary">' . $membership->new_plan_date . '</span>', 'selectPlanName' => '<span class="font-bold text-primary">' . str($membership->name)->title() . '</span>']) !!}
                                                            </p>
                                                        @else
                                                            <div class="border mb-4">
                                                                <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold">
                                                                    <h3 class="text-black">{{ __('Current payable amount') }}</h3>
                                                                    <p class="text-primary">{{ Number::currency((float) $membership->new_plan_amount) }}</p>
                                                                </div>
                                                            </div>
                                                            <p>
                                                                {!! __('New plan will be renewed on :currentPlanEndDate and you\'ll be charged for :selectPlanPrice on :currentPlanEndDate.', ['currentPlanEndDate' => '<span class="font-bold text-primary">' . $currentMembershipEndDate->formatWithTimezone() . '</span>', 'selectPlanPrice' => '<span class="font-bold text-primary">' . Number::currency((float) $membership->price) . '</span>']) !!}
                                                            </p>
                                                        @endif
                                                    @else
                                                        <div class="border mb-4">
                                                            <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold">
                                                                <h3 class="text-black">{{ __('Current Plan End Date') }}</h3>
                                                                <p class="text-primary">{{ $currentMembershipEndDate->formatWithTimezone() }}</p>
                                                            </div>
                                                        </div>
                                                        <p>
                                                            {!! __('Your current plan will be downgraded :newPlan on :currentPlanEndDate and you will be charged :selectPlanPrice on that date.', ['newPlan' => '<span class="font-bold text-primary">' . $membership->name . '</span>', 'currentPlanEndDate' => '<span class="font-bold text-primary">' . $currentMembershipEndDate->formatWithTimezone() . '</span>', 'selectPlanPrice' => '<span class="font-bold text-primary">' . Number::currency((float) $membership->price) . '</span>']) !!}
                                                        </p>
                                                    @endif
                                                </div>
                                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-2 pt-4">
                                                    <x-dialog.close>
                                                        <x-form.default-button
                                                            type="button"
                                                            class="w-full sm:w-auto"
                                                        >
                                                            {{ __('Close') }}
                                                        </x-form.default-button>
                                                    </x-dialog.close>
                                                    <x-form.button
                                                        x-on:click="dialogOpen = false"
                                                        wire:click="updateMembership({{ $membership->id }})"
                                                        variant="primary"
                                                        type="button"
                                                        class="border focus:border-primary-focus"
                                                        wire:loading.attr="disabled"
                                                        wire:loading.class="disabled:opacity-50"
                                                    >
                                                        <span
                                                            wire:loading
                                                            wire:target="updateMembership({{ $membership->id }})"
                                                        >
                                                            {{ __('Submitting..') }}
                                                        </span>
                                                        <span
                                                            wire:loading.remove
                                                            wire:target="updateMembership({{ $membership->id }})"
                                                        >
                                                            {{ __('Confirm') }}
                                                        </span>
                                                    </x-form.button>
                                                </div>
                                            </x-dialog.panel>
                                        </x-dialog>
                                    @else
                                        <x-dialog>
                                            <x-dialog.open>
                                                <x-form.button
                                                    type="button"
                                                    variant="primary"
                                                    @class([
                                                        'btn text-white rounded-full bg-primary hover:!bg-primary-400',
                                                    ])
                                                >
                                                    {{ $currentMembershipEndDate < now() ? __('Choose Plan') : __('Upgrade Plan') }}
                                                </x-form.button>
                                            </x-dialog.open>
                                            <x-dialog.panel :heading="__('Confirm Plan Selection?')">
                                                <div class="border mb-4">
                                                    <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold border-b">
                                                        <h3 class="text-black">{{ __("Selected Plan") }}</h3>
                                                        <p class="text-primary">{{ $membership->name }}</p>
                                                    </div>
                                                    <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold border-b">
                                                        <h3 class="text-black">{{ __("Amount") }}</h3>
                                                        <p class="text-primary">{{ Number::currency((float) $membership->price) }}</p>
                                                    </div>
                                                    <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold border-b">
                                                        <h3 class="text-black">{{ __("Billing Cycle") }}</h3>
                                                        <p class="text-primary">{{ $membership->frequency }}</p>
                                                    </div>
                                                    <div class="flex items-center justify-between py-2 px-3 text-sm+ font-semibold">
                                                        <h3 class="text-black">{{ __("Payment Method") }}</h3>
                                                        <p class="text-primary">CARD (xx-{{ $membershipPaymentProfile?->last_four_digit }})</p>
                                                    </div>
                                                </div>
                                                <p>{!! __("Your previous membership expired due to a failed payment. Are you sure you want to choose this plan? We will process :amount from your account to activate your membership.", ['amount' => '<span class="font-bold text-primary">' . Number::currency((float) $membership->price) . '</span>']) !!}</p>
                                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-2 pt-4">
                                                    <x-dialog.close>
                                                        <x-form.default-button
                                                            type="button"
                                                            class="w-full sm:w-auto"
                                                        >
                                                            {{ __('Cancel') }}
                                                        </x-form.default-button>
                                                    </x-dialog.close>
                                                    <x-dialog.close>
                                                        <x-form.button
                                                            type="button"
                                                            variant="primary"
                                                            wire:click="activePlan({{$membership->id}})"
                                                            class="w-full sm:w-auto"
                                                        >
                                                            {{ __('Confirm & Pay') }}
                                                        </x-form.button>
                                                    </x-dialog.close>
                                                </div>
                                            </x-dialog.panel>
                                        </x-dialog>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach

                <x-loader
                    wire:loading
                    wire:target.except="membershipInquiry"
                />

                @if (! $specialMembershipExists)
                    <livewire:creditor.membership-inquiries.card />
                @endif

                <x-dialog wire:model="cancelledPlanKeepProfile">
                    <x-dialog.panel confirm-box>
                        <x-slot name="headerCancelIcon">
                            <div class="flex justify-end px-4">
                                <button
                                    type="button"
                                    x-on:click="$dialog.close()"
                                    x-on:close.window="dialogOpen = false"
                                    class="btn size-10 text-black rounded-full p-2 mr-1 hover:bg-slate-300/20"
                                >
                                    <x-heroicon-o-x-mark class="size-8 fill-white text-black" />
                                </button>
                            </div>
                        </x-slot>
                        <x-slot name="svg">
                            <span class="text-7xl">💐</span>
                        </x-slot>
                        <x-slot name="heading">
                            <span class="text-3xl font-medium">{{ __('We Appreciate You') }}</span>
                        </x-slot>
                        <x-slot name="message">
                            <span class="text-base text-black">
                                {{ __('Your user profile will remain active after the billing period ends.') }}
                                {{ __('We are here for you day and night!') }}
                            </span>
                        </x-slot>
                    </x-dialog.panel>
                </x-dialog>

                <x-dialog wire:model="cancelledPlanRemoveProfile">
                    <x-dialog.panel :heading="__('We\'re Sorry to See You Go!')">
                        <div class="text-sm">
                            <p>{{ __('Thank you, we will cancel your user account at the end of this billing period. Feel free to manage your membership untill the end of the billing period.') }}</p>
                            <p>{{ __('Any insights to why are cancelling are sincerely appreciated to help us serve you in the future.') }}</p>
                        </div>
                        <div class="mt-4 text-start">
                            <x-form.text-area
                                :label="__('Enter your feedback (optional)')"
                                name="cancelled_note"
                                wire:model="cancelled_note"
                                maxlength="250"
                                :placeholder="__('Enter Message')"
                                rows="4"
                            />
                        </div>
                        <div class="flex items-center justify-center">
                            <x-form.button
                                type="button"
                                variant="primary"
                                class="text-sm+ mt-2 w-32 border focus:border-primary-focus"
                                wire:click="submitCancelledNote"
                                wire:target="submitCancelledNote"
                                wire:loading.class="opacity-50"
                                wire:loading.attr="disabled"
                            >
                                {{ __('Submit') }}
                            </x-form.button>
                        </div>
                    </x-dialog.panel>
                </x-dialog>
            </div>
        </div>
    </div>
</div>

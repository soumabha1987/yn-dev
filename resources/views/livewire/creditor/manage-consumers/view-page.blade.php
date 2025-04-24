@use('Illuminate\Support\Number')
@use('App\Enums\NegotiationType')
@use('App\Enums\ConsumerStatus')
@use('App\Enums\Role')

<div x-data="scrollTab">
    <div
        x-on:refresh-parent.window="$wire.$refresh"
        class="card mb-8 px-4 sm:px-5"
    >
        <div class="flex flex-col py-4">
            <div class="mt-2">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block">
                            <span class="font-medium text-black tracking-wide">
                                {{ __('Account Status') }}
                            </span>
                            <div class="flex items-center gap-x-1 mt-2 bg-transparent">
                                @if (in_array($consumer->status, [ConsumerStatus::DEACTIVATED, ConsumerStatus::DISPUTE, ConsumerStatus::NOT_PAYING]))
                                    {{ __('Removed') }}
                                @else
                                    {{ __('Active') }}
                                @endif

                                @if ($consumer->reason_id)
                                    @svg('lucide-circle-help', [
                                        'class' => 'size-5 text-rose-500',
                                        'x-tooltip.placement.bottom' => "$consumer->reason.label"
                                    ])
                                @endif
                            </div>
                        </label>
                    </div>
                    <div>
                        <label class="block">
                            <span class="font-medium text-black tracking-wide">
                                {{ __('First Name') }}
                            </span>
                            <div class="mt-2 bg-transparent">
                                {{ str($consumer->first_name)->title() }}
                            </div>
                        </label>
                    </div>
                    <div>
                        <label class="block">
                            <span class="font-medium text-black tracking-wide">
                                {{ __('Last Name') }}
                            </span>
                            <div class="mt-2 bg-transparent">
                                {{ str($consumer->last_name)->title() }}
                            </div>
                        </label>
                    </div>
                    <div>
                        <label class="block">
                            <span class="font-semibold text-black tracking-wide">
                                {{ __('Account Name') }}
                            </span>
                            <div class="mt-2 bg-transparent">
                                {{ filled($consumer->original_account_name) ? str($consumer->original_account_name)->title() : 'N/A' }}
                            </div>
                        </label>
                    </div>
                    <div>
                        <label class="block">
                            <span class="font-semibold text-black tracking-wide">
                                {{ __('Account Number') }}
                                <span class="text-xs+"> {{ __('(Master if no master default to Original)') }} </span>
                            </span>
                            <div class="mt-2 bg-transparent">
                                {{  str($consumer->account_number)->title() }}
                            </div>
                        </label>
                    </div>
                    <div>
                        <label class="block">
                            <span class="font-semibold text-black tracking-wide">
                                {{ __('Beginning Balance') }}
                            </span>
                            <div class="mt-2 bg-transparent">
                                {{ Number::currency((float) $consumer->total_balance) }}
                            </div>
                        </label>
                    </div>
                    <div>
                        <label class="block">
                            <span class="font-semibold text-black tracking-wide">
                                {{ __('Current Balance') }}
                            </span>
                            <div class="mt-2 bg-transparent">
                                {{ Number::currency((float) $consumer->current_balance) }}
                            </div>
                        </label>
                    </div>
                    <div>
                        <label class="block">
                            <span class="font-semibold text-black tracking-wide">
                                {{ __('Settlement') }}
                            </span>
                            <div class="mt-2 bg-transparent">
                                {{ $consumer->pif_discount_percent ? Number::percentage($consumer->pif_discount_percent) : 'N/A' }}
                            </div>
                        </label>
                    </div>
                    <div>
                        <label class="block">
                            <span class="font-semibold text-black tracking-wide">
                                {{ __('Plan Discounted Balance') }}
                            </span>
                            <div class="mt-2 bg-transparent">
                                {{$consumer->pay_setup_discount_percent ? Number::percentage($consumer->pay_setup_discount_percent) : 'N/A'}}
                            </div>
                        </label>
                    </div>
                    <div>
                        <label class="block">
                            <span class="font-semibold text-black tracking-wide">
                                {{ __('Min. Monthly Payment') }}
                            </span>
                            <div class="mt-2 bg-transparent">
                                {{ $consumer->min_monthly_pay_percent ? Number::percentage($consumer->min_monthly_pay_percent) : 'N/A' }}
                            </div>
                        </label>
                    </div>
                    <div>
                        <label class="block">
                            <span class="font-semibold text-black tracking-wide">
                                {{ __('Days to 1st Payment') }}
                            </span>
                            <div class="mt-2 bg-transparent">
                                {{ $consumer->max_days_first_pay ?? 'N/A' }}
                            </div>
                        </label>
                    </div>
                    <div>
                        <label class="block">
                            <span class="font-semibold text-black tracking-wide">
                                {{ __('Min. Settlement Percentage') }}
                            </span>
                            <div class="mt-2 bg-transparent">
                                {{ $consumer->minimum_settlement_percentage ?? 'N/A' }}
                            </div>
                        </label>
                    </div>
                    <div>
                        <label class="block">
                            <span class="font-semibold text-black tracking-wide">
                                {{ __('Min. Payment Plan Percentage') }}
                            </span>
                            <div class="mt-2 bg-transparent">
                                {{ $consumer->minimum_payment_plan_percentage ?? 'N/A' }}
                            </div>
                        </label>
                    </div>
                    <div>
                        <label class="block">
                            <span class="font-semibold text-black tracking-wide">
                                {{ __('First Payment Date') }}
                            </span>
                            <div class="mt-2 bg-transparent">
                                {{ $consumer->max_first_pay_days ?? 'N/A' }}
                            </div>
                        </label>
                    </div>
                    @if ($isSuperAdmin)
                        <label class="block">
                            <span class="font-semibold text-black tracking-wide">
                                {{ __('Company Name') }}
                            </span>
                            <div class="mt-2 bg-transparent">
                                {{ $consumer->company->company_name ?? 'N/A' }}
                            </div>
                        </label>
                    @endif
                </div>
            </div>
        </div>
        <div class="flex flex-col py-2">
            <div class="flex items-center justify-between py-2 text-base font-medium text-slate-700">
                <p class="justify-start font-semibold uppercase tracking-wide text-black">
                    {{ __('Contact Information') }}
                </p>
            </div>
            <hr class="h-px bg-slate-200">
            <div class="mt-2">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <label class="block">
                        <span class="font-semibold text-black tracking-wide">
                            {{ __('Last Four SSN') }}
                        </span>
                        <div class="mt-2 bg-transparent">
                            {{ $consumer->last4ssn }}
                        </div>
                    </label>
                    <label class="block">
                        <span class="font-semibold text-black tracking-wide">
                            {{ __('Date Of Birth') }}
                        </span>
                        <div class="mt-2 bg-transparent">
                            {{ $consumer->dob->format('M d, Y') }}
                        </div>
                    </label>
                    <label class="block">
                        <span class="font-semibold text-black tracking-wide">
                            {{ __('Consumer Link') }}
                        </span>
                        <div class="mt-2 bg-transparent">
                            <a
                                href="{{ $consumer->invitation_link }}"
                                target="_blank"
                            >
                                {{ $consumer->invitation_link }}
                            </a>
                        </div>
                    </label>
                </div>
                <div class="grid grid-cols-1 mt-2 sm:grid-cols-4 gap-3">
                    <div>
                        <x-form.input-field
                            wire:model="form.email"
                            :label="__('Email')"
                            type="text"
                            name="form.email"
                            :placeholder="__('Enter Email')"
                            @class([
                                'w-full',
                                'bg-transparent text-slate-400' => $consumer->status === ConsumerStatus::SETTLED
                            ])
                            :disabled="$consumer->status === ConsumerStatus::SETTLED"
                            required
                        />
                    </div>
                    <div>
                        <x-form.input-field
                            x-model="mobile"
                            x-mask="(999) 999-9999"
                            x-on:input="removeMaskingInMobileNumber"
                            :label="__('Mobile Number')"
                            type="text"
                            name="form.mobile"
                            :placeholder="__('Enter Mobile Number')"
                            @class([
                                'w-full',
                                'bg-transparent text-slate-400' => $consumer->status === ConsumerStatus::SETTLED
                            ])
                            :disabled="$consumer->status === ConsumerStatus::SETTLED"
                            required
                        />
                    </div>
                </div>
            </div>
        </div>
        <div class="flex flex-col py-2">
            <div class="flex items-center justify-between py-2 text-base font-medium text-slate-700">
                <p class="justify-start font-semibold uppercase tracking-wide text-black">
                    {{ __('Negotiations') }}
                </p>
            </div>
            <hr class="h-px bg-slate-200">
            <div class="mt-2">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <label class="block">
                        <span class="font-semibold text-black tracking-wide">
                            {{ __('Negotiation Agreed Date') }}
                        </span>
                        <div class="mt-2 bg-transparent">
                            {{ $consumerNegotiation ? $consumerNegotiation->updated_at->formatWithTimezone() : 'N/A' }}
                        </div>
                    </label>
                    <label class="block">
                        <span class="font-semibold text-black tracking-wide">
                            {{ __('Negotiation Term') }}
                        </span>
                        <div class="mt-2 bg-transparent">
                            @if ($consumerNegotiation)
                                {{ $consumerNegotiation->negotiation_type === NegotiationType::PIF ? __('Settlement') : __('Payment Plan')}}
                            @else
                                {{ 'N/A' }}
                            @endif
                        </div>
                    </label>
                    <label class="block">
                        <span class="font-semibold text-black tracking-wide">
                            {{ __('Settled Payoff Amount') }}
                        </span>
                        <div class="mt-2 bg-transparent">
                            @if ($consumerNegotiation && $consumerNegotiation->negotiation_type === NegotiationType::PIF)
                                @if ($consumerNegotiation->offer_accepted)
                                    {{ Number::currency((float) $consumerNegotiation->one_time_settlement ?? 0) }}
                                @elseif ($consumerNegotiation->counter_offer_accepted)
                                    {{ Number::currency((float) $consumerNegotiation->counter_one_time_amount ?? 0) }}
                                @else
                                    {{ 'N/A' }}
                                @endif
                            @else
                                {{ 'N/A' }}
                            @endif
                        </div>
                    </label>
                    <label class="block">
                        <span class="font-semibold text-black tracking-wide">
                            {{ __('Discounted Payment Plan Beginning Balance') }}
                        </span>
                        <div class="mt-2 bg-transparent">
                            @if ($consumerNegotiation && $consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT)
                                @if ($consumerNegotiation->offer_accepted)
                                    {{ Number::currency((float) $consumerNegotiation->negotiate_amount ?? 0) }}
                                @elseif ($consumerNegotiation->counter_offer_accepted)
                                    {{ Number::currency((float) $consumerNegotiation->counter_negotiate_amount ?? 0) }}
                                @else
                                    {{ 'N/A' }}
                                @endif
                            @else
                                {{ 'N/A' }}
                            @endif
                        </div>
                    </label>
                    <label class="block">
                        <span class="font-semibold text-black tracking-wide">
                            {{ __('Monthly Payment') }}
                        </span>
                        <div class="mt-2 bg-transparent">
                            @if ($consumerNegotiation && $consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT)
                                @if ($consumerNegotiation->offer_accepted)
                                    {{ Number::currency((float) $consumerNegotiation->monthly_amount ?? 0) }}
                                @elseif ($consumerNegotiation->counter_offer_accepted)
                                    {{ Number::currency((float) $consumerNegotiation->counter_monthly_amount ?? 0) }}
                                @else
                                    {{ 'N/A' }}
                                @endif
                            @else
                                {{ 'N/A' }}
                            @endif
                        </div>
                    </label>
                    <label class="block">
                        <div class="font-semibold text-black tracking-wide">
                            <span>{{ __('First Payment Due Date') }}</span>
                        </div>
                        <div class="mt-2 bg-transparent">
                            @if ($consumerNegotiation && $consumerNegotiation->offer_accepted)
                                {{ $consumerNegotiation->first_pay_date?->format('M d, Y') ?? 'N/A' }}
                            @elseif ($consumerNegotiation && $consumerNegotiation->counter_offer_accepted)
                                {{ $consumerNegotiation->counter_first_pay_date?->format('M d, Y') ?? 'N/A' }}
                            @else
                                {{ 'N/A' }}
                            @endif
                        </div>
                    </label>
                    <label class="block">
                        <span class="font-semibold text-black tracking-wide">
                            {{ __('Negotiation Status') }}
                        </span>
                        <div class="mt-2 bg-transparent">
                            @php
                                $status = match(true) {
                                    $consumer->status !== ConsumerStatus::PAYMENT_ACCEPTED =>
                                        match ($consumer->status) {
                                            ConsumerStatus::UPLOADED => __('Offer Delivered'),
                                            ConsumerStatus::JOINED => __('Offer Viewed'),
                                            ConsumerStatus::PAYMENT_SETUP => __('In Negotiations'),
                                            ConsumerStatus::SETTLED => __('Settled/Paid'),
                                            ConsumerStatus::DISPUTE => __('Disputed'),
                                            ConsumerStatus::NOT_PAYING => __('Reported Not Paying'),
                                            ConsumerStatus::PAYMENT_DECLINED => __('Negotiations Closed'),
                                            ConsumerStatus::DEACTIVATED => __('Deactivated'),
                                            ConsumerStatus::HOLD => __('Account in Hold'),
                                            default => __('N/A'),
                                        },
                                    $consumer->payment_setup => __('Active Payment Plan'),
                                    $consumer->consumerNegotiation->negotiation_type === NegotiationType::PIF => __('Agreed Settlement/Pending Payment'),
                                    $consumer->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT => __('Agreed Payment Plan/Pending Payment'),
                                }
                            @endphp
                            {{ $status }}
                        </div>
                    </label>
                </div>
            </div>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap 2xl:flex-nowrap lg:justify-end items-stretch sm:items-center my-4">
            <a
                wire:navigate
                href="{{ route('manage-consumers') }}"
                class="btn border focus:border-slate-400 bg-slate-150 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80"
            >
                {{ __('Cancel') }}
            </a>

            @if (! in_array($consumer->status, [ConsumerStatus::DEACTIVATED, ConsumerStatus::DISPUTE, ConsumerStatus::NOT_PAYING]))
                <x-form.button
                    type="submit"
                    variant="primary"
                    :disabled="$consumer->status === ConsumerStatus::SETTLED"
                    class="whitespace-nowrap border focus:border-primary-focus disabled:opacity-50"
                    wire:click="updateConsumer"
                    wire:loading.attr="disabled"
                >
                    <x-lucide-loader-2
                        wire:loading
                        wire:target="updateConsumer"
                        class="size-5 animate-spin mr-2"
                    />
                    <x-heroicon-o-pencil-square
                        wire:loading.remove
                        class="size-5 mr-2"
                    />
                    {{ __('Update Profile') }}
                </x-form.button>

                @if (
                    $consumer->email1 === $consumer->consumerProfile->email
                    || $consumer->mobile1 === $consumer->consumerProfile->mobile
                )
                    <x-dialog>
                        <x-dialog.open>
                            <x-form.button
                                type="button"
                                variant="info"
                                class="whitespace-nowrap border focus:border-info-focus w-full"
                            >
                                <x-heroicon-o-pencil-square class="size-5 mr-2"/>
                                <span>{{ __('Update Permission') }}</span>
                            </x-form.button>
                        </x-dialog.open>
                        <x-dialog.panel size="xl">
                            <x-slot name="heading">{{ __('Communication Preferences') }}</x-slot>
                            @if ($consumer->mobile1 === $consumer->consumerProfile->mobile)
                                <div class="flex justify-between px-4 sm:px-5 py-4">
                                    <span class="font-semibold select-none tracking-wide text-slate-700 line-clamp-1 lg:text-lg">
                                        {{ __('Text Communication') }}
                                        <span class="text-xs text-primary mt-1 lg:text-md">
                                            {{ preg_replace('/^(\d{3})(\d{3})(\d{4})$/', '($1) $2-$3', $consumer->consumerProfile->mobile) }}
                                        </span>
                                    </span>
                                    <div class="flex items-center gap-x-2">
                                        <div
                                            x-data="{ displayTextPermissionMessage: false }"
                                            x-on:update-text-permission.window="displayTextPermissionMessage = true"
                                        >
                                            <span
                                                x-show="displayTextPermissionMessage"
                                                x-effect="if (displayTextPermissionMessage) setTimeout(() => displayTextPermissionMessage = false, 2500)"
                                                x-transition:enter="transition ease-out duration-300"
                                                x-transition:enter-start="opacity-0 scale-90"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-300"
                                                x-transition:leave-start="opacity-100 scale-100"
                                                x-transition:leave-end="opacity-0 scale-90"
                                                class="text-success"
                                            >
                                                {{ __('Updated!') }}
                                            </span>
                                        </div>
                                        <input
                                            type="checkbox"
                                            wire:model.boolean="text_permission"
                                            wire:click="updateTextPermission"
                                            class="form-switch h-5 w-10 rounded-full bg-slate-300 before:rounded-full before:bg-slate-50 checked:bg-primary checked:before:bg-white"
                                        >
                                    </div>
                                </div>
                            @endif
                            @if (
                                $consumer->email1 === $consumer->consumerProfile->email
                                && $consumer->mobile1 === $consumer->consumerProfile->mobile
                            )
                                <hr class="my-4 h-px bg-slate-200">
                            @endif
                            @if ($consumer->email1 === $consumer->consumerProfile->email)
                                <div class="flex justify-between px-4 sm:px-5 py-4">
                                <span class="font-semibold select-none tracking-wide text-slate-700 line-clamp-1 lg:text-lg">
                                    {{ __('Email Communication') }}
                                    <span class="text-xs text-primary mt-1 lg:text-md">
                                        {{ $consumer->consumerProfile->email }}
                                    </span>
                                </span>
                                    <div class="flex items-center gap-x-2">
                                        <div
                                            x-data="{ displayEmailPermissionMessage: false }"
                                            x-on:update-email-permission.window="displayEmailPermissionMessage = true"
                                        >
                                            <span
                                                x-show="displayEmailPermissionMessage"
                                                x-effect="if (displayEmailPermissionMessage) setTimeout(() => displayEmailPermissionMessage = false, 2500)"
                                                x-transition:enter="transition ease-out duration-300"
                                                x-transition:enter-start="opacity-0 scale-90"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-300"
                                                x-transition:leave-start="opacity-100 scale-100"
                                                x-transition:leave-end="opacity-0 scale-90"
                                                class="text-success"
                                            >
                                                {{ __('Updated!') }}
                                            </span>
                                        </div>
                                        <input
                                            type="checkbox"
                                            wire:model.boolean="email_permission"
                                            wire:click="updateEmailPermission"
                                            class="form-switch h-5 w-10 rounded-full bg-slate-300 before:rounded-full before:bg-slate-50 checked:bg-primary checked:before:bg-white"
                                        >
                                    </div>
                                </div>
                            @endif
                        </x-dialog.panel>
                    </x-dialog>
                @endif

                @if ($consumer->consumerProfile->email_permission)
                    <x-dialog>
                        <x-dialog.open>
                            <x-form.button
                                type="button"
                                variant="success"
                                class="whitespace-nowrap border focus:border-success-focus w-full"
                            >
                                <x-lucide-mail class="size-5 mr-2"/>
                                <span>{{ __('Send Email') }}</span>
                            </x-form.button>
                        </x-dialog.open>

                        <div x-on:close-dialog.window="dialogOpen = false">
                            <x-dialog.panel size="2xl">
                                <x-slot name="heading">{{ __('Send Email') }}</x-slot>
                                <form
                                    wire:submit="sendEmail"
                                    autocomplete="off"
                                >
                                    <x-form.input-field
                                        :label="__('Subject')"
                                        wire:model="emailSubject"
                                        name="emailSubject"
                                        type="text"
                                        class="w-full mb-2"
                                        :placeholder="__('Enter Subject')"
                                    />

                                    <span class="font-semibold tracking-wide text-black lg:text-md mt-2">
                                        {{ __('Content') }}
                                    </span>
                                    <x-form.quill-editor
                                        wire:model="emailContent"
                                        class="h-48"
                                        :name="$emailContent"
                                        alpine-variable-name="$wire.emailContent"
                                        form-input-name="emailContent"
                                        :placeHolder="__('Enter Email Content')"
                                    />
                                    <div class="mt-4 space-x-2 text-right">
                                        <x-dialog.close>
                                            <x-form.default-button type="button">
                                                {{ __('Cancel') }}
                                            </x-form.default-button>
                                        </x-dialog.close>
                                        <x-form.button
                                            type="submit"
                                            variant="primary"
                                            class="border focus:border-primary-focus"
                                        >
                                            {{ __('Submit') }}
                                        </x-form.button>
                                    </div>
                                </form>
                            </x-dialog.panel>
                        </div>
                    </x-dialog>
                @endif

                @if ($consumer->consumerProfile->text_permission)
                    <x-dialog>
                        <x-dialog.open>
                            <x-form.button
                                type="button"
                                variant="secondary"
                                class="whitespace-nowrap border focus:border-secondary-focus w-full"
                            >
                                <x-lucide-message-circle class="size-5 mr-2"/>
                                <span>{{ __('Send SMS') }}</span>
                            </x-form.button>
                        </x-dialog.open>

                        <div @close-dialog.window="dialogOpen = false">
                            <x-dialog.panel size="2xl">
                                <x-slot name="heading">{{ __('Write message') }}</x-slot>
                                <form wire:submit="sendSms" autocomplete="off">
                                    <x-form.text-area
                                        :label="__('Enter Message')"
                                        name="smsContent"
                                        wire:model="smsContent"
                                        required
                                    />
                                    <div class="space-x-2 text-right">
                                        <x-dialog.close>
                                            <x-form.default-button type="button">
                                                {{ __('Cancel') }}
                                            </x-form.default-button>
                                        </x-dialog.close>
                                        <x-form.button
                                            type="submit"
                                            variant="primary"
                                            class="mt-4 border focus:border-primary-focus"
                                        >
                                            {{ __('Submit') }}
                                        </x-form.button>
                                    </div>
                                </form>
                            </x-dialog.panel>
                        </div>
                    </x-dialog>
                @endif

                @if ($isSuperAdmin)
                    <x-dialog>
                        <x-dialog.open>
                            <x-form.button
                                type="button"
                                variant="warning"
                                class="whitespace-nowrap border focus:border-warning-focus w-full"
                            >
                                <x-lucide-send class="size-5 mr-2"/>
                                <span>{{ __('Send E-Letter') }}</span>
                            </x-form.button>
                        </x-dialog.open>

                        <div @close-dialog.window="dialogOpen = false">
                            <x-dialog.panel size="2xl">
                                <x-slot name="heading">{{ __('Send E-Letter') }}</x-slot>
                                <form wire:submit="sendELetter" autocomplete="off">
                                    <span
                                        class="font-medium tracking-wide text-black lg:text-base">
                                        {{ __('Content') }}
                                    </span>
                                    <x-form.quill-editor
                                        class="h-48"
                                        :name="$eLetterContent"
                                        alpine-variable-name="$wire.eLetterContent"
                                        form-input-name="eLetterContent"
                                        :placeHolder="__('Enter E-letter Content')"
                                        wire:model="eLetterContent"
                                    />
                                    <div class="space-x-2 text-right">
                                        <x-dialog.close>
                                            <x-form.default-button type="button">
                                                {{ __('Cancel') }}
                                            </x-form.default-button>
                                        </x-dialog.close>
                                        <x-form.button
                                            type="submit"
                                            variant="primary"
                                            wire:loading.attr="disabled"
                                            wire:target="sendELetter"
                                            class="mt-4 border focus:border-primary-focus disabled:opacity-50"
                                        >
                                            <x-lucide-loader-2
                                                wire:loading
                                                wire:target="sendELetter"
                                                class="size-5 animate-spin mr-2"
                                            />
                                            {{ __('Submit') }}
                                        </x-form.button>
                                    </div>
                                </form>
                            </x-dialog.panel>
                        </div>
                    </x-dialog>
                @endif
                <x-confirm-box
                    :ok-button-label="__('Deactivate')"
                    action="delete"
                >
                    <x-slot name="message">
                        {{ __('By deactivating this consumer, will it ensure that no negotiations will take place, and will the consumer be prevented from logging into the portal?') }}
                    </x-slot>
                    <x-form.button
                        type="button"
                        variant="error"
                        class="whitespace-nowrap border focus:border-error-focus w-full"
                    >
                        <x-heroicon-o-trash class="size-5 mr-1"/>
                        <span>{{ __('Deactivate') }}</span>
                    </x-form.button>
                </x-confirm-box>
                @if (
                    in_array($consumer->status, [
                        ConsumerStatus::JOINED,
                        ConsumerStatus::UPLOADED,
                        ConsumerStatus::VISITED,
                        ConsumerStatus::NOT_VERIFIED,
                        ConsumerStatus::RENEGOTIATE,
                        ConsumerStatus::PAYMENT_DECLINED,
                    ])
                )
                    <livewire:creditor.consumer-pay-terms.update-page :record="$consumer" />
                @endif
            @endif
            <x-form.button
                type="button"
                variant="info"
                class="border focus:border-info-focus"
                wire:click="downloadAgreement({{ $consumer->id }})"
                wire:loading.attr="disabled"
            >
                <div
                    wire:loading.flex
                    wire:target="downloadAgreement({{ $consumer->id }})"
                    class="flex space-x-2"
                >
                    <img src="https://api.iconify.design/svg-spinners:wind-toy.svg" class="size-5">
                    <span class="whitespace-nowrap">{{ __('Downloading') }}</span>
                </div>
                <div
                    wire:loading.remove
                    wire:target="downloadAgreement({{ $consumer->id }})"
                    class="flex space-x-2 whitespace-nowrap"
                >
                    <x-heroicon-o-arrow-down-tray class="size-5"/>
                    <span class="whitespace-nowrap">{{ __('Download Agreement') }}</span>
                </div>
            </x-form.button>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="py-4 px-6 xl:px-4 border-b border-slate-200 relative">
                <div
                    x-ref="tabs"
                    x-on:scroll="checkScrollPosition"
                    class="flex p-2 space-x-2 items-center is-scrollbar-hidden min-w-full overflow-x-auto"
                >
                    <template x-if="isOverflowing && !isAtStart">
                        <x-lucide-chevron-left
                            x-on:click="scrollLeft"
                            class="absolute -left-2 top-8 mx-4 size-6 cursor-pointer will-change-transform hover:animate-wiggle"
                        />
                    </template>
                    <button
                        class="badge text-base text-nowrap font-medium hover:bg-success/30 bg-success/10 text-success"
                        :class="{'!bg-success outline outline-1 outline-success outline-offset-4 text-white': activeTab === 'paymentPlan'}"
                        x-on:click="activeTab = 'paymentPlan'"
                    >
                        {{ __('Payment Plan') }}
                    </button>
                    <button
                        class="badge text-base text-nowrap font-medium hover:bg-accent/30 bg-accent/10 text-accent"
                        :class="{'!bg-accent outline outline-1 outline-accent outline-offset-4 text-white': activeTab === 'transactionHistory'}"
                        x-on:click="activeTab = 'transactionHistory'"
                    >
                        {{ __('Transaction History') }}
                    </button>
                    <button
                        class="badge text-base text-nowrap font-medium hover:bg-primary/30 bg-primary/10 text-primary"
                        :class="{'!bg-primary outline outline-1 outline-primary outline-offset-4 text-white': activeTab === 'cancelPaymentDetails'}"
                        x-on:click="activeTab = 'cancelPaymentDetails'"
                    >
                        {{ __('Canceled Payments') }}
                    </button>
                    <button
                        class="badge text-base text-nowrap font-medium hover:bg-secondary-green/30 bg-secondary-green/10 text-secondary-green"
                        :class="{'!bg-secondary-green outline outline-1 outline-secondary-green outline-offset-4 !text-white': activeTab === 'eLettersHistory'}"
                        x-on:click="activeTab = 'eLettersHistory'"
                    >
                        {{ __('Eco Letters History') }}
                    </button>
                    <template x-if="isOverflowing && !isAtEnd">
                        <x-lucide-chevron-right
                            x-on:click="scrollRight"
                            class="absolute -right-0 top-8 size-6 cursor-pointer will-change-transform hover:animate-wiggle"
                        />
                    </template>
                </div>
            </div>

            <div x-show="activeTab === 'paymentPlan'">
                <livewire:creditor.manage-consumers.consumer-profile.scheduled-transactions :$consumer />
            </div>
            <div x-show="activeTab === 'transactionHistory'">
                <livewire:creditor.manage-consumers.consumer-profile.transactions :$consumer />
            </div>
            <div x-show="activeTab === 'cancelPaymentDetails'">
                <livewire:creditor.manage-consumers.consumer-profile.cancelled-schedule-transactions :$consumer />
            </div>
            <div x-show="activeTab === 'eLettersHistory'">
                <livewire:creditor.manage-consumers.consumer-profile.e-letter-histories :$consumer />
            </div>
        </div>
    </div>

    @script
        <script>
            Alpine.data('scrollTab', () => {
                return {
                    activeTab: 'paymentPlan',
                    isOverflowing: false,
                    isAtStart: true,
                    isAtEnd: false,
                    mobile: '',

                    init() {
                        this.mobile = this.$wire.form.mobile

                        window.addEventListener('resize', () => {
                            this.isOverflowing = this.$refs.tabs.scrollWidth > this.$refs.tabs.clientWidth
                            this.checkScrollPosition()
                        })

                        this.$nextTick(() => {
                            this.isOverflowing = this.$refs.tabs.scrollWidth > this.$refs.tabs.clientWidth
                            this.checkScrollPosition()
                        })
                    },
                    scrollRight() {
                        this.$refs.tabs.scrollBy({
                            left: this.$refs.tabs.clientWidth * 0.8,
                            behavior: 'smooth'
                        })
                    },
                    scrollLeft() {
                        this.$refs.tabs.scrollBy({
                            left: -this.$refs.tabs.clientWidth * 0.8,
                            behavior: 'smooth'
                        })
                    },
                    checkScrollPosition() {
                        this.isAtStart = this.$refs.tabs.scrollLeft === 0
                        this.isAtEnd = this.$refs.tabs.scrollLeft + this.$refs.tabs.clientWidth + 1 >= this.$refs.tabs.scrollWidth
                    },
                    removeMaskingInMobileNumber() {
                        this.$wire.form.mobile = this.mobile.replace(/[^0-9]/g, '')
                    }
                }
            })
        </script>
    @endscript
</div>

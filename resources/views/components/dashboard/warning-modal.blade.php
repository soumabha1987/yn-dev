@use('App\Enums\CompanyStatus')
@props([
    'step1IsCompleted' => false,
    'step2IsCompleted' => false,
    'step3IsCompleted' => false,
    'step4IsCompleted' => false,
    'step5IsCompleted' => false,
    'step6IsCompleted' => false,
    'step7IsCompleted' => false,
    'step8IsCompleted' => false,
])

<div
    x-data="{ dialogOpen: false }"
    x-modelable="dialogOpen"
    {{ $attributes }}
>
    <style>
        .steps {
            --line: 0rem
        }
    </style>

    <template x-teleport="body">
        <div
            x-dialog
            x-model="dialogOpen"
            class="fixed inset-0 z-[100] flex flex-col items-center justify-center px-4 py-6 sm:px-5"
        >
            <div
                x-dialog:overlay
                class="absolute inset-0 bg-slate-900/40 transition-opacity duration-300"
                x-transition:enter="ease-out"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
            ></div>

            <div
                x-dialog:panel
                class="relative w-full origin-top rounded-lg bg-white transition-all duration-300 max-w-xl overflow-y-auto"
                x-transition:enter="easy-out"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="easy-in"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
            >
                <x-emoji-hey class="size-14 md:size-16 mx-auto mt-8 mb-4" />

                <h3 class="text-lg md:text-xl py-2 font-bold flex justify-center text-black">
                    <span>{{ __('Set Up Wizard') }}</span>
                </h3>
                <p class="text-xs md:text-xl text-center text-black pb-2 px-4 sm:px-0">
                    {{ __('Complete the required steps to upload your accounts 😊') }}
                </p>
                <div class="flex justify-center px-4 sm:px-0">
                    <div class="sticky top-24 mt-5">
                        <ol class="steps is-vertical line-space md:text-lg">
                            <li @class([
                                'step w-full items-center pb-3',
                                'before:bg-success' => $step1IsCompleted,
                                'before:bg-slate-200' => ! $step1IsCompleted,
                            ])>
                                <div @class([
                                    'step-header rounded-full font-semibold',
                                    'bg-success' => $step1IsCompleted,
                                    'bg-primary' => ! $step1IsCompleted,
                                ])>
                                    @if ($step1IsCompleted)
                                        <x-heroicon-c-check class="size-6 text-white" />
                                    @else
                                        <span class="text-lg text-white font-semibold">1</span>
                                    @endif
                                </div>
                                <a
                                    wire:navigate
                                    href="{{ route('creditor.merchant-settings') }}"
                                    class='group flex w-fit items-center text-primary justify-between space-x-2 ml-4 font-semibold tracking-wide'
                                    x-on:focus="$el.blur()"
                                >
                                    <div>
                                        <span class="group-hover:underline group-hover:underline-offset-4 tracking-wide">
                                            {{ __('Merchant Payment Processor') }}
                                        </span>
                                        <span class="text-error text-xs">
                                            {{ __('(Required)') }}
                                        </span>
                                    </div>

                                    <div>
                                        @if ($step1IsCompleted && auth()->user()->company->status === CompanyStatus::SUBMITTED)
                                            <span class="badge bg-yellow-500 text-xs text-white">
                                                {{ __('Pending') }}
                                            </span>
                                        @endif
                                    </div>
                                </a>
                            </li>

                            <li @class([
                                'step w-full items-center pb-3',
                                'before:bg-success' => $step2IsCompleted,
                                'before:bg-slate-200' => ! $step2IsCompleted,
                            ])>
                                <div @class([
                                    'step-header font-semibold rounded-full',
                                    'bg-success' => $step2IsCompleted,
                                    'bg-primary' => ! $step2IsCompleted,
                                ])>
                                    @if ($step2IsCompleted)
                                        <x-heroicon-c-check class="size-6 text-white" />
                                    @else
                                        <span class="text-lg text-white font-semibold">2</span>
                                    @endif
                                </div>
                                <a
                                    wire:navigate
                                    href="{{ route('manage-subclients') }}"
                                    class="group flex w-fit items-center text-primary justify-between space-x-2 ml-4 font-semibold tracking-wide"
                                >
                                    <div>
                                        <span class="group-hover:underline group-hover:underline-offset-4 tracking-wide">
                                            {{ __('Create Sub Accounts') }}
                                        </span>
                                        <span class="text-black text-xs">
                                            {{ __('(Optional)') }}
                                        </span>
                                    </div>
                                </a>
                            </li>

                            <li @class([
                                'step w-full items-center pb-3',
                                'before:bg-success' => $step3IsCompleted,
                                'before:bg-slate-200' => ! $step3IsCompleted,
                            ])>
                                <div @class([
                                    'step-header rounded-full font-semibold',
                                    'bg-success' => $step3IsCompleted,
                                    'bg-primary' => ! $step3IsCompleted,
                                ])>
                                    @if ($step3IsCompleted)
                                        <x-heroicon-c-check class="size-6 text-white" />
                                    @else
                                        <span class="text-lg text-white font-semibold">3</span>
                                    @endif
                                </div>
                                <a
                                    wire:navigate
                                    href="{{ route('creditor.pay-terms.create', ['selectMasterTerms' => true]) }}"
                                    class="group flex w-fit items-center text-primary justify-between space-x-2 ml-4 font-semibold tracking-wide"
                                >
                                    <div>
                                        <span class="group-hover:underline group-hover:underline-offset-4 tracking-wide">
                                            {{ __('Pay Term Offers') }}
                                        </span>
                                        <span class="text-error text-xs">
                                            {{ __('(Required)') }}
                                        </span>
                                    </div>
                                </a>
                            </li>

                            <li @class([
                                'step w-full items-center pb-3',
                                'before:bg-success' => $step4IsCompleted,
                                'before:bg-slate-200' => ! $step4IsCompleted,
                            ])>
                                <div @class([
                                    'step-header rounded-full font-semibold',
                                    'bg-success' => $step4IsCompleted,
                                    'bg-primary' => ! $step4IsCompleted,
                                ])>
                                    @if ($step4IsCompleted)
                                        <x-heroicon-c-check class="size-6 text-white" />
                                    @else
                                        <span class="text-lg text-white font-semibold">4</span>
                                    @endif
                                </div>
                                <a
                                    wire:navigate
                                    href="{{ route('creditor.terms-conditions') }}"
                                    class="group flex w-fit items-center text-primary justify-between space-x-2 ml-4 font-semibold tracking-wide"
                                >
                                    <div>
                                        <span class="group-hover:underline group-hover:underline-offset-4 tracking-wide">
                                            {{ __('Terms & Conditions') }}
                                        </span>
                                        <span class="text-error text-xs">
                                            {{ __('(Required)') }}
                                        </span>
                                    </div>
                                </a>
                            </li>

                            <li @class([
                                'step w-full items-center pb-3',
                                'before:bg-success' => $step5IsCompleted,
                                'before:bg-slate-200' => ! $step5IsCompleted,
                            ])>
                                <div @class([
                                    'step-header rounded-full font-semibold',
                                    'bg-success' => $step5IsCompleted,
                                    'bg-primary' => ! $step5IsCompleted,
                                ])>
                                    @if ($step5IsCompleted)
                                        <x-heroicon-c-check class="size-6 text-white" />
                                    @else
                                        <span class="text-lg text-white font-semibold">5</span>
                                    @endif
                                </div>
                                <a
                                    wire:navigate
                                    href="{{ route('creditor.import-consumers.upload-file') }}"
                                    class="group flex w-fit items-center text-primary justify-between space-x-2 ml-4 font-semibold tracking-wide"
                                >
                                    <div>
                                        <span class="group-hover:underline group-hover:underline-offset-4 tracking-wide">
                                            {{ __('Import Header Profiles') }}
                                        </span>
                                        <span class="text-error text-xs">
                                            {{ __('(Required)') }}
                                        </span>
                                    </div>
                                </a>
                            </li>

                            <li @class([
                                'step w-full items-center pb-3',
                                'before:bg-success' => $step6IsCompleted,
                                'before:bg-slate-200' => ! $step6IsCompleted,
                            ])>
                                <div @class([
                                    'step-header rounded-full font-semibold',
                                    'bg-success' => $step6IsCompleted,
                                    'bg-primary' => ! $step6IsCompleted,
                                ])>
                                    @if ($step6IsCompleted)
                                        <x-heroicon-c-check class="size-6 text-white" />
                                    @else
                                        <span class="text-lg text-white font-semibold">6</span>
                                    @endif
                                </div>
                                <a
                                    wire:navigate
                                    href="{{ route('creditor.sftp') }}"
                                    class="group flex w-fit items-center text-primary justify-between space-x-2 ml-4 font-semibold tracking-wide"
                                >
                                    <div>
                                        <span class="group-hover:underline group-hover:underline-offset-4 tracking-wide">
                                            {{ __('Configure SFTP') }}
                                        </span>
                                        <span class="text-black text-xs">
                                            {{ __('(Optional)') }}
                                        </span>
                                    </div>
                                </a>
                            </li>

                            <li @class([
                                'step w-full items-center pb-3',
                                'before:bg-success' => $step7IsCompleted,
                                'before:bg-slate-200' => ! $step7IsCompleted,
                            ])>
                                <div @class([
                                    'step-header rounded-full font-semibold',
                                    'bg-success' => $step7IsCompleted,
                                    'bg-primary' => ! $step7IsCompleted,
                                ])>
                                    @if ($step7IsCompleted)
                                        <x-heroicon-c-check class="size-6 text-white" />
                                    @else
                                        <span class="text-lg text-white font-semibold">7</span>
                                    @endif
                                </div>
                                <a
                                    wire:navigate
                                    href="{{ route('creditor.personalized-logo-and-link') }}"
                                    class="group flex w-fit items-center text-primary justify-between space-x-2 ml-4 font-semibold tracking-wide"
                                >
                                    <div>
                                        <span class="group-hover:underline group-hover:underline-offset-4 tracking-wide">
                                            {{ __('Logo Profile & Embed Code') }}
                                        </span>
                                        <span class="text-black text-xs">
                                            {{ __('(Optional)') }}
                                        </span>
                                    </div>
                                </a>
                            </li>

                            <li @class([
                                'step w-full items-center pb-3',
                                'before:bg-success' => $step8IsCompleted,
                                'before:bg-slate-200' => ! $step8IsCompleted,
                            ])>
                                <div @class([
                                    'step-header rounded-full font-semibold',
                                    'bg-success' => $step8IsCompleted,
                                    'bg-primary' => ! $step8IsCompleted,
                                ])>
                                    @if ($step8IsCompleted)
                                        <x-heroicon-c-check class="size-6 text-white" />
                                    @else
                                        <span class="text-lg text-white font-semibold">8</span>
                                    @endif
                                </div>
                                <a
                                    wire:navigate
                                    href="{{ route('creditor.about-us.create-or-update') }}"
                                    class="group flex w-fit items-center text-primary justify-between space-x-2 ml-4 font-semibold tracking-wide"
                                >
                                    <div>
                                        <span class="group-hover:underline group-hover:underline-offset-4 tracking-wide">
                                            {{ __('About Us Profile') }}
                                        </span>
                                        <span class="text-error text-xs">
                                            {{ __('(Required)') }}
                                        </span>
                                    </div>
                                </a>
                            </li>
                        </ol>
                    </div>
                </div>

                <div class="p-6 pt-0">
                    <div class="space-x-2 text-center">
                        <x-dialog.close>
                            <x-form.button
                                type="button"
                                variant="error"
                                class="w-40 font-semibold"
                            >
                                {{ __('Close') }}
                            </x-form.button>
                        </x-dialog.close>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

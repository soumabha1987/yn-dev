@use('App\Services\ConsumerELetterService')
@use('Illuminate\Support\Facades\Storage')

@php
    $unreadMailCount = 0;

    if (auth()->check()) {
        $unreadMailCount = cache()->pull(
            'unread-mail-count-' . auth()->id(),
            fn () => app(ConsumerELetterService::class)->unreadCount(auth()->user())
        );
    }
@endphp

<div x-data="{ aboutusDialog: false }">
    <div class="bg-white">
        <nav class="before:bg-white print:hidden">
            <div class="relative flex w-full p-4 bg-white print:hidden">
                <div class="container mx-auto">
                    <div class="flex w-full items-center justify-between">
                        <div class="size-7 lg:hidden">
                            <button
                                class="flex lg:hidden menu-toggle ml-0.5 size-7 flex-col justify-center space-y-1.5 text-primary outline-none focus:outline-none"
                                :class="$store.global.isSidebarExpanded && 'active'"
                                @click="$store.global.isSidebarExpanded = !$store.global.isSidebarExpanded"
                            >
                                <span></span>
                                <span></span>
                                <span></span>
                            </button>
                        </div>
                        <div class="ml-2 items-center lg:w-full lg:flex">
                            <livewire:consumer.logo />
                            @auth('consumer')
                                <div class="w-full mx-10">
                                    <div class="w-full lg:space-x-1 lg:justify-end items-center mx-auto h-12 hidden lg:flex">
                                        @if (! request()->routeIs('consumer.verify_ssn'))
                                            <div class="inline-flex group">
                                                <a
                                                    wire:navigate
                                                    href="{{ route('consumer.account') }}"
                                                    @class([
                                                        'btn text-zs+ space-x-2 font-medium leading-none text-[#2563eb] group-hover:bg-slate-300/20 group-active:bg-slate-300/25',
                                                        'text-primary/70 bg-primary/10' => request()->routeIs('consumer.account'),
                                                    ])
                                                    x-bind:class="aboutusDialog && '!bg-white'"
                                                >
                                                    <x-lucide-notebook-text
                                                        @class([
                                                            'size-6',
                                                            'fill-primary/20' => request()->routeIs('consumer.account'),
                                                        ])
                                                        x-bind:class="aboutusDialog && 'fill-none'"
                                                    />
                                                    <span class="p-1">{{ __('My Accounts') }}</span>
                                                </a>
                                            </div>
                                            <div class="inline-flex group relative">
                                                <a
                                                    wire:navigate
                                                    href="{{ route('consumer.e-letters') }}"
                                                    @class([
                                                        'btn text-zs+ space-x-2 font-medium leading-none text-violet-500 group-hover:bg-slate-300/20 group-active:bg-slate-300/25',
                                                        'bg-violet-500/20' => request()->routeIs('consumer.e-letters'),
                                                        'text-violet-500/70' => ! request()->routeIs('consumer.e-letters'),
                                                    ])
                                                    x-bind:class="aboutusDialog && '!bg-white'"
                                                >
                                                    <x-lucide-mails
                                                        @class([
                                                            'size-6',
                                                            'fill-violet-500/20' => request()->routeIs('consumer.e-letters'),
                                                        ])
                                                        x-bind:class="aboutusDialog && 'fill-none'"
                                                    />
                                                    <span class="p-1">{{ __('Eco Mailbox') }}</span>
                                                    <div
                                                        class="absolute -top-2 -right-2"
                                                        x-data="{ unreadEmailCount:  @js($unreadMailCount) }"
                                                        x-on:update-unread-email-count.window="() => unreadEmailCount = $event.detail[0]"
                                                    >
                                                        <span class="animate-ping absolute top-0 right-0 inline-flex size-full rounded-full bg-violet-500 opacity-75"></span>
                                                        <template x-if="unreadEmailCount > 0">
                                                            <div
                                                                class="flex h-6 min-w-6 items-center justify-center rounded-full bg-violet-500 px-1 text-xs text-white"
                                                                x-text="unreadEmailCount > 99 ? '99+' : unreadEmailCount"
                                                            ></div>
                                                        </template>
                                                    </div>
                                                </a>
                                            </div>
                                            <div class="inline-flex group">
                                                <a
                                                    wire:navigate
                                                    href="{{ route('consumer.communication_controls') }}"
                                                    @class([
                                                        'btn text-zs+ space-x-2 font-medium leading-none text-[#10b981] group-hover:bg-slate-300/20 group-active:bg-slate-300/25',
                                                        'text-[#10b981] bg-success/10' => request()->routeIs('consumer.communication_controls'),
                                                    ])
                                                    x-bind:class="aboutusDialog && '!bg-white'"
                                                >
                                                    <x-lucide-sliders-horizontal
                                                        @class([
                                                            'size-5',
                                                            'fill-success/20' => request()->routeIs('consumer.communication_controls'),
                                                        ])
                                                        x-bind:class="aboutusDialog && 'fill-none'"
                                                    />
                                                    <span class="p-1">{{ __('Communication Controls') }}</span>
                                                </a>
                                            </div>

                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="!font-[600] text-black w-full justify-end is-scrollbar-hidden hidden h-12 sm:flex">
                                    <div class="inline-flex group">
                                        <a
                                            href="https://younegotiate.com/"
                                            class="btn px-4 text-base leading-none group-hover:text-slate-800 group-hover:bg-slate-300/20 group-active:bg-slate-300/25"
                                        >
                                            <span>{{ __('Home') }}</span>
                                        </a>
                                    </div>
                                    <div class="inline-flex group">
                                        <a
                                            href="https://younegotiate.com/consumer/"
                                            class="btn px-4 text-base leading-none group-hover:text-slate-800 group-hover:bg-slate-300/20 group-active:bg-slate-300/25"
                                        >
                                            <span>{{ __('Consumer Experience') }}</span>
                                        </a>
                                    </div>
                                    <div class="inline-flex group">
                                        <a
                                            href="https://younegotiate.com/creditors/"
                                            class="btn px-4 text-base leading-none group-hover:text-slate-800 group-hover:bg-slate-300/20 group-active:bg-slate-300/25"
                                        >
                                            <span>{{ __('Creditor Network') }}</span>
                                        </a>
                                    </div>
                                    <div class="inline-flex group border-2 lg:ml-4 border-primary rounded-lg">
                                        <a
                                            href="https://creditor.younegotiate.com/login"
                                            class="btn px-4 text-base text-primary space-x-2 font-medium leading-none group-hover:text-primary-800 group-hover:bg-slate-300/20 group-active:bg-slate-300/25"
                                        >
                                            <x-heroicon-m-lock-closed class="size-6" />
                                            <span>{{ __('Creditor Login') }}</span>
                                        </a>
                                    </div>
                                </div>
                            @endauth
                        </div>
                        <div class="-mr-1.5 flex items-center space-x-2">
                            @auth('consumer')
                                <div x-data="{ profilePhoto: '{{ auth()->guard('consumer')->user()->consumerProfile?->image ? Storage::url('profile-images/' . auth()->user()->consumerProfile->image) : null  }}' }">
                                    <x-consumer.popover>
                                        <x-consumer.popover.button>
                                            <div
                                                x-on:update-profile-photo.window="profilePhoto = $event.detail"
                                                class="avatar p-px size-10 hover:bg-slate-400/20 focus:bg-slate-400/20 active:bg-slate-400/25 rounded-full"
                                            >
                                                <template x-if="profilePhoto !== ''">
                                                    <img
                                                        x-bind:src="profilePhoto"
                                                        class="rounded-full object-cover object-fit size-10"
                                                        alt="{{ __('Avatar') }}"
                                                    >
                                                </template>
                                                <template x-if="profilePhoto === ''">
                                                    <div class="is-initial rounded-full border border-primary/30 bg-primary/10 text-base uppercase text-primary">
                                                        <span>{{ auth()->guard('consumer')->user()->pluckUsernameFirstTwoDigits }}</span>
                                                    </div>
                                                </template>
                                            </div>
                                        </x-consumer.popover.button>

                                        <x-consumer.popover.panel
                                            position="bottom-start"
                                            class="z-50"
                                        >
                                            <div class="w-64 rounded-lg border border-slate-150 bg-white shadow-soft">
                                                <div class="flex items-center space-x-4 rounded-t-lg bg-slate-100 px-4 py-5">
                                                    <div class="avatar size-12">
                                                        <template x-if="profilePhoto !== ''">
                                                            <img
                                                                x-bind:src="profilePhoto"
                                                                class="rounded-full object-cover object-fit size-10"
                                                                alt="{{ __('Avatar') }}"
                                                            >
                                                        </template>
                                                        <template x-if="profilePhoto === ''">
                                                            <div class="is-initial rounded-full border border-primary/30 bg-primary/10 text-sm uppercase text-primary">
                                                                <span>{{ auth()->guard('consumer')->user()->pluckUsernameFirstTwoDigits }}</span>
                                                            </div>
                                                        </template>
                                                    </div>
                                                    <div>
                                                        <span class="text-base font-medium text-slate-700">
                                                            {{ auth()->guard('consumer')->user()->first_name . ' ' . auth()->guard('consumer')->user()->last_name }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="flex flex-col pb-5 pt-2">
                                                    <div class="flex flex-col h-52 overflow-y-auto">
                                                        <x-consumer.menu.link
                                                            :title="__('My Profile')"
                                                            :sub-title="__('Your Profile Settings')"
                                                            class="bg-warning"
                                                            :href="route('consumer.profile')"
                                                        >
                                                            <x-slot name="icon">
                                                                <x-lucide-user-round class="size-5" />
                                                            </x-slot>
                                                        </x-consumer.menu.link>

                                                        <x-consumer.menu.coming-soon
                                                            :title="__('My Donation Profile')"
                                                        >
                                                            <x-slot name="icon">
                                                                <x-lucide-hand-heart class="size-5" />
                                                            </x-slot>
                                                        </x-consumer.menu.coming-soon>

                                                        <x-consumer.menu.coming-soon
                                                            :title="__('Add Bill Pay to your Calendar')"
                                                        >
                                                            <x-slot name="icon">
                                                                <x-lucide-calendar class="size-5" />
                                                            </x-slot>
                                                        </x-consumer.menu.coming-soon>

                                                        <x-consumer.menu.coming-soon
                                                            :title="__('Credit Score & Boost')"
                                                        >
                                                            <x-slot name="icon">
                                                                <x-lucide-gauge class="size-5" />
                                                            </x-slot>
                                                        </x-consumer.menu.coming-soon>

                                                        <x-consumer.menu.coming-soon
                                                            :title="__('Bill Pay Rewards')"
                                                        >
                                                            <x-slot name="icon">
                                                                <x-lucide-gift class="size-5" />
                                                            </x-slot>
                                                        </x-consumer.menu.coming-soon>

                                                        <x-consumer.menu.coming-soon
                                                            :title="__('Invite & Earn')"
                                                        >
                                                            <x-slot name="icon">
                                                                <x-lucide-dollar-sign class="size-5" />
                                                            </x-slot>
                                                        </x-consumer.menu.coming-soon>

                                                        <x-consumer.menu.coming-soon
                                                            :title="__('YN Business Card')"
                                                        >
                                                            <x-slot name="icon">
                                                                <x-lucide-credit-card class="size-5" />
                                                            </x-slot>
                                                        </x-consumer.menu.coming-soon>

                                                        <x-consumer.menu.coming-soon
                                                            :title="__('Respond to Written Notices')"
                                                        >
                                                            <x-slot name="icon">
                                                                <x-lucide-file-warning class="size-5" />
                                                            </x-slot>
                                                        </x-consumer.menu.coming-soon>
                                                    </div>
                                                    <div class="mt-3 px-4">
                                                        <livewire:consumer.logout />
                                                    </div>
                                                </div>
                                            </div>
                                        </x-consumer.popover.panel>
                                    </x-consumer.popover>
                                </div>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </div>
</div>

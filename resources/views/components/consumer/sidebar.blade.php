@use('App\Services\ConsumerELetterService')

@php
    $unreadMailCount = 0;

    if (auth()->check()) {
        $unreadMailCount = app(ConsumerELetterService::class)->unreadCount(auth()->user());
        cache()->put('unread-mail-count-' . auth()->id(), $unreadMailCount);
    }
@endphp

<div x-data="{ aboutusDialog: false }">
    <div class="sidebar sidebar-panel print:hidden">
        <div
            x-data="{ count: 0 }"
            class="flex lg:hidden h-full grow flex-col border-r border-slate-150 bg-white"
            x-init="$store.global.isSidebarExpanded = false"
            :class="$store.global.isSidebarExpanded ? 'z-50 shadow-md' : ''"
            @keyup.escape.window="$store.global.isSidebarExpanded = false"
            @click.outside="() => {
                count++
                if (count !== 1 && $store.global.isSidebarExpanded) {
                    $store.global.isSidebarExpanded = false
                    count = 0
                }
            }"
        >
            <div class="flex items-center justify-between px-3 pt-4">
                <div class="flex">
                    <livewire:consumer.logo />
                </div>

                <button
                    @click="$store.global.isSidebarExpanded = false"
                    class="ml-3 btn size-7 rounded-full p-0 text-primary hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 xl:hidden"
                >
                    <x-lucide-chevron-left class="size-6" />
                </button>
            </div>

            @auth
                <div
                    class="h-[calc(100%-4.5rem)] overflow-x-hidden pb-6 mt-5 text-base"
                    x-data="{ expandedItem: null }"
                    x-init="$el._x_simplebar = new SimpleBar($el)"
                >
                    @if(! request()->routeIs('consumer.verify_ssn'))
                        <a
                            wire:navigate
                            href="{{ route('consumer.account') }}"
                            @class([
                                'mt-1 flex items-center space-x-3 p-3 text-[#2563eb] hover:bg-slate-300/20 active:bg-slate-300/25 cursor-pointer',
                                'text-primary/70 bg-primary/10' => request()->routeIs('consumer.account'),
                            ])
                            x-bind:class="aboutusDialog && '!bg-white'"
                        >
                            <x-lucide-notebook-text
                                @class(['size-6', 'fill-primary/20' => request()->routeIs('consumer.account')])
                                x-bind:class="aboutusDialog && 'fill-none'"
                            />
                            <span class="text-center">{{ __('My Accounts') }}</span>
                        </a>

                        <a
                            wire:navigate
                            href="{{ route('consumer.e-letters') }}"
                            @class([
                                'mt-1 flex items-center space-x-3 p-3 text-violet-500 hover:bg-slate-300/20 active:bg-slate-300/25 cursor-pointer',
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
                            <span class="text-center">{{ __('Eco Mailbox') }}</span>
                            <div
                                x-data="{ unreadEmailCount:  @js($unreadMailCount) }"
                                x-on:update-unread-email-count.window="() => unreadEmailCount = $event.detail[0]"
                                class="relative"
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

                        <a
                            wire:navigate
                            href="{{ route('consumer.communication_controls') }}"
                            @class([
                                'mt-1 flex items-center space-x-3 p-3 text-[#10b981] hover:bg-slate-300/20 active:bg-slate-300/25 cursor-pointer',
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
                            <span class="text-center text-nowrap">{{ __('Communication Controls') }}</span>
                        </a>
                    @endif
                </div>
            @endauth

            @guest
                <div
                    class="font-bold m-2 text-slate-700 h-[calc(100%-4.5rem)] overflow-x-hidden pb-6 mt-5"
                    x-data="{ expandedItem: null }"
                    x-init="$el._x_simplebar = new SimpleBar($el)"
                >
                    <a
                        href="https://younegotiate.com/"
                        class="mt-1 flex items-center space-x-3 item-center p-3 text-slate-600 hover:text-slate-800 hover:bg-slate-300/20 active:bg-slate-300/25"
                    >
                        <span class="text-center">{{ __('Home') }}</span>
                    </a>
                    <a
                        href="https://younegotiate.com/consumer/"
                        class="mt-1 flex items-center space-x-3 item-center p-3 text-slate-600 hover:text-slate-800 hover:bg-slate-300/20 active:bg-slate-300/25"
                    >
                        <span class="text-center">{{ __('Consumer Experience') }}</span>
                    </a>
                    <a
                        href="https://younegotiate.com/creditors/"
                        class="mt-1 flex items-center space-x-3 item-center p-3 text-slate-600 hover:text-slate-800 hover:bg-slate-300/20 active:bg-slate-300/25"
                    >
                        <span class="text-center">{{ __('Creditor Network') }}</span>
                    </a>
                    <a
                        href="{{ route('login') }}"
                        class="mt-1 flex items-center space-x-3 item-center p-3 text-primary border-primary border-2 rounded hover:text-slate-800 hover:bg-slate-300/20 active:bg-slate-300/25"
                    >
                        <x-heroicon-m-lock-closed class="size-6" />
                        <span class="text-center">{{ __('Creditor Login') }}</span>
                    </a>
                </div>
            @endguest
        </div>
    </div>
</div>

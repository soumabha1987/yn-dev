<div>
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
                        <div class="ml-2 items-center w-full hidden lg:flex">
                            <div class="flex items-center mx-auto">
                                <a
                                    wire:navigate
                                    href="{{ route('login') }}"
                                >
                                    <livewire:creditor.logo />
                                </a>
                            </div>
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
                                    @if (request()->routeIs('consumer.login'))
                                        <a
                                            href="{{ route('login') }}"
                                            class="btn px-4 text-base text-primary space-x-2 font-medium leading-none group-hover:text-primary-800 group-hover:bg-slate-300/20 group-active:bg-slate-300/25"
                                        >
                                            <x-heroicon-m-lock-closed class="size-6" />
                                            <span>{{ __('Creditor Login') }}</span>
                                        </a>
                                    @else
                                        <a
                                            href="{{ route('consumer.login') }}"
                                            class="btn px-4 text-base text-primary space-x-2 font-medium leading-none group-hover:text-primary-800 group-hover:bg-slate-300/20 group-active:bg-slate-300/25"
                                        >
                                            <x-heroicon-m-lock-closed class="w-6" />
                                            <span>{{ __('Consumer Login') }}</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </div>
</div>

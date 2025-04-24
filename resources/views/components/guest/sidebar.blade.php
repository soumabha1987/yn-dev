<div>
    <div class="sidebar sidebar-panel lg:hidden">
        <div
            class="flex lg:hidden h-full grow flex-col border-r border-slate-150 bg-white"
            x-init="$store.global.isSidebarExpanded = false"
            x-bind:class="$store.global.isSidebarExpanded ? 'z-50 shadow-md' : ''"
        >
            <div class="flex items-center justify-between px-3 pt-4">
                <div class="flex">
                    <div class="flex items-center mx-auto">
                        <x-logo-svg width="200px" />
                    </div>
                    <button
                        @click="$store.global.isSidebarExpanded = false"
                        class="btn size-7 rounded-full p-0 text-primary hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 xl:hidden"
                    >
                        <x-heroicon-s-chevron-left  class="size-6 font-bold"/>
                    </button>
                </div>
            </div>
            <div
                class="font-bold m-2 text-slate-700 h-[calc(100%-4.5rem)] overflow-x-hidden pb-6 mt-5"
                x-init="$el._x_simplebar = new SimpleBar($el);"
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

                @if (request()->routeIs('consumer.login'))
                    <a
                        href="{{ route('login') }}"
                        class="mt-1 flex items-center space-x-3 item-center p-3 text-primary border-primary border-2 rounded hover:text-slate-800 hover:bg-slate-300/20 active:bg-slate-300/25"
                    >
                        <x-heroicon-m-lock-closed class="size-6" />
                        <span class="text-center">{{ __('Creditor Login') }}</span>
                    </a>
                @else
                    <a
                        href="{{ route('consumer.login') }}"
                        class="mt-1 flex items-center space-x-3 item-center p-3 text-primary border-primary border-2 rounded hover:text-slate-800 hover:bg-slate-300/20 active:bg-slate-300/25"
                    >
                        <x-heroicon-m-lock-closed class="w-6" />
                        <span class="text-center">{{ __('Consumer Login') }}</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

@props([
    'title' => '',
])

<div>
    <div class="sidebar print:hidden">
        <div x-bind:class="$store.global.isSidebarExpanded && 'z-50 shadow-md'">
            <div
                x-on:click.outside="if ($store.breakpoints.mdAndDown) $store.global.isSidebarExpanded = false"
                class="sidebar-panel border-x border-transparent border-r-slate-200 shadow-none"
            >
                <div class="flex h-screen grow flex-col bg-slate-50">
                    <div class="flex h-18 w-full items-center justify-between">
                        <div class="flex items-center mx-auto">
                            <a
                                wire:navigate
                                href="{{ route('home') }}"
                            >
                                <livewire:creditor.logo />
                            </a>
                        </div>
                        <button
                            x-on:click="$store.global.isSidebarExpanded = false"
                            :class="! $store.global.isSidebarExpanded ? 'hidden' : ''"
                            class="btn size-7 rounded-full p-0 text-primary hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 xl:hidden"
                        >
                            <x-heroicon-s-chevron-left class="size-6 font-bold" />
                        </button>
                    </div>
                    <div class="flex h-[calc(100%-4.5rem)] grow flex-col">
                        <div class="grow overflow-y-auto">
                            <ul
                                x-init="$store.sidebar.collapsedGroups = []"
                                class="mt-4 mb-16 space-y-1.5 px-1"
                            >
                                <x-sidebar.sidebar-composer :$sidebarMenu />
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <x-header :$title />
</div>

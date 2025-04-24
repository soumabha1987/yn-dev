@use('App\Enums\Role')
@use('App\Enums\CompanyStatus')
@use('Illuminate\Support\Facades\Storage')

@props([
    'title' => '',
])

<div>
    <nav class="header print:hidden">
        <div class="relative flex w-full bg-slate-50 header-container print:hidden">
            <div class="flex items-center justify-between w-full">
                <div class="hidden xl:block">
                    <h2
                        x-data="{ title: @js($title) }"
                        x-on:update-title.window="title = $event.detail[0]"
                        class="text-md text-black font-semibold lg:text-lg"
                        x-html="title"
                    >
                    </h2>
                </div>
                <div class="flex size-7">
                    <button
                        class="menu-toggle flex xl:hidden ml-0.5 size-7 flex-col justify-center space-y-1.5 text-primary outline-none focus:outline-none"
                        x-bind:class="$store.global.isSidebarExpanded && 'active'"
                        x-on:click.stop="$store.global.isSidebarExpanded = !$store.global.isSidebarExpanded"
                    >
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>

                <div
                    class="-mr-1.5 flex items-center space-x-2"
                    x-data="{ profilePhoto: '{{ auth()->user()->image ? Storage::url('profile-images/' . auth()->user()->image) : null }}' }"
                >
                    <livewire:creditor.global-search />

                    <x-popover>
                        <x-popover.button class="p-0 rounded-full hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 inline-flex items-center space-x-2">
                            <div
                                x-on:update-profile-photo.window="profilePhoto = $event.detail"
                                class="size-10 avatar"
                            >
                                <div x-show="profilePhoto !== ''">
                                    <img
                                        x-bind:src="profilePhoto"
                                        class="rounded-full object-fit object-cover size-10"
                                        alt="{{ __('avatar') }}"
                                    >
                                </div>
                                <div
                                    x-show="profilePhoto === ''"
                                    class="text-base uppercase border rounded-full is-initial border-primary/30 bg-primary/10 text-primary"
                                >
                                    {{ $firstLetterOfName = substr(auth()->user()->name, 0, 1) }}
                                </div>
                            </div>
                            <span class="text-base font-medium text-primary hidden sm:flex items-center gap-1">
                                {{ str(auth()->user()->name)->title() }}
                                <x-lucide-chevron-down class="size-5" />
                            </span>
                        </x-popover.button>

                        <x-popover.panel class="w-64 bg-white border rounded-lg border-slate-150 shadow-soft">
                            <div class="flex items-center px-4 py-5 space-x-4 rounded-t-lg bg-slate-100">
                                <div class="avatar">
                                    <div x-show="profilePhoto !== ''">
                                        <img
                                            x-bind:src="profilePhoto"
                                            class="rounded-full object-fit object-cover size-10"
                                            alt="{{ __('avatar') }}"
                                        >
                                    </div>
                                    <div
                                        x-show="profilePhoto === ''"
                                        class="text-sm uppercase border rounded-full is-initial border-primary/30 bg-primary/10 text-primary"
                                    >
                                        {{ $firstLetterOfName }}
                                    </div>
                                </div>

                                <div>
                                    <span class="text-base font-medium text-slate-700">
                                        {{ str(auth()->user()->name)->title() }}
                                    </span>
                                    <p class="text-xs text-slate-400">
                                        {{ str(auth()->user()->company->company_name ?? auth()->user()->roles->first()->name ?? 'N/A')->title() }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-col pt-2 pb-5">
                                @role(Role::SUPERADMIN)
                                    <a
                                        wire:navigate
                                        href="{{ route('super-admin.configurations') }}"
                                        class="flex items-center px-4 py-2 space-x-3 tracking-wide transition-all outline-none group hover:bg-slate-100 focus:bg-slate-100"
                                    >
                                        <div class="flex items-center justify-center size-8 text-white rounded-lg bg-warning">
                                            <x-lucide-user-round-cog class="size-5" />
                                        </div>
                                        <h2 class="font-medium transition-colors text-slate-700 group-hover:text-primary group-focus:text-primary">
                                            {{ __('Admin Configuration') }}
                                        </h2>
                                    </a>
                                @endrole

                                @unlessrole(Role::SUPERADMIN)
                                    @if (auth()->user()->subclient_id === null)
                                        <a
                                            wire:navigate
                                            href="{{ route('creditor.settings') }}"
                                            class="flex items-center px-4 py-2 space-x-3 tracking-wide transition-all outline-none group hover:bg-slate-100 focus:bg-slate-100"
                                        >
                                            <div class="flex items-center justify-center size-8 text-white rounded-lg bg-success">
                                                <x-heroicon-o-cog-6-tooth class="size-5" />
                                            </div>
                                            <h2 class="font-medium transition-colors text-slate-700 group-hover:text-primary group-focus:text-primary">
                                                {{ __('Account Profile') }}
                                            </h2>
                                        </a>
                                        <a
                                            wire:navigate
                                            href="{{ route('creditor.membership-settings') }}"
                                            class="flex items-center px-4 py-2 space-x-3 tracking-wide transition-all outline-none group hover:bg-slate-100 focus:bg-slate-100"
                                        >
                                            <div class="flex items-center justify-center size-8 text-white rounded-lg bg-sky-500">
                                                <x-heroicon-o-heart class="size-5" />
                                            </div>
                                            <h2 class="font-medium transition-colors text-slate-700 group-hover:text-primary group-focus:text-primary">
                                                {{ __('Manage Membership') }}
                                            </h2>
                                        </a>
                                        <a
                                            wire:navigate
                                            href="{{ route('creditor.billing-history') }}"
                                            class="flex items-center px-4 py-2 space-x-3 tracking-wide transition-all outline-none group hover:bg-slate-100 focus:bg-slate-100"
                                        >
                                            <div class="flex items-center justify-center size-8 text-white rounded-lg bg-secondary">
                                                <x-heroicon-o-receipt-percent class="size-5" />
                                            </div>
                                            <h2 class="font-medium transition-colors text-slate-700 group-hover:text-primary group-focus:text-primary">
                                                {{ __('Billing History') }}
                                            </h2>
                                        </a>
                                    @endif
                                @endunlessrole

                                <a
                                    wire:navigate
                                    href="{{ route('change-password') }}"
                                    class="flex items-center px-4 py-2 space-x-3 tracking-wide transition-all outline-none group hover:bg-slate-100 focus:bg-slate-100"
                                >
                                    <div class="flex items-center justify-center size-8 text-white rounded-lg bg-accent">
                                        <x-heroicon-o-key class="size-5" />
                                    </div>
                                    <h2 class="font-medium transition-colors text-slate-700 group-hover:text-primary group-focus:text-primary">
                                        {{ __('Change Password') }}
                                    </h2>
                                </a>

                                <div class="px-4 mt-3">
                                    <livewire:creditor.auth.logout />
                                </div>
                            </div>
                        </x-popover.panel>
                    </x-popover>
                </div>
            </div>
        </div>
    </nav>
</div>

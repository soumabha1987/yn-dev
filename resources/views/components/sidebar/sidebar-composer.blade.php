@props([
    'sidebarMenu' => [],
    'parentMenuTitle' => null,
])

@foreach ($sidebarMenu as $menu)
    @php
        $routeName = data_get($menu, 'route_name', false);
        $isCurrentRoute = $routeName && str(request()->route()?->getName())->contains($routeName);
        $isSetupWizard = $routeName === 'creditor.setup-wizard';
    @endphp
    @if ($routeName)
        <li>
            <a
                wire:navigate
                href="{{ route($routeName) }}"
                @class([
                    'group flex justify-between space-x-2 rounded-lg p-2 tracking-wide outline-none transition-all',
                    'text-primary bg-primary/10 font-medium' => $isCurrentRoute,
                    'text-black hover:bg-slate-100 focus:bg-slate-100 hover:font-semibold' => ! $isCurrentRoute,
                    '!bg-error/10' => $isSetupWizard && $isCurrentRoute,
                ])
            >
                <div
                     @class([
                        'flex items-center space-x-2',
                        'blink !text-error' => $isSetupWizard
                     ])
                >
                    @php
                        $baseClasses = 'transition-colors group-hover:font-medium group-focus:font-medium';
                        $classForSvg = '';

                        if (! $isCurrentRoute) {
                            $classForSvg = $isSetupWizard ? '!text-error' : 'text-black';
                            $classForSvg .= " {$baseClasses}";
                        }
                    @endphp
                    @svg($menu['icon'], ['class' => "size-4 $classForSvg"])
                    <span 
                        @class([
                            '!text-error text-base font-bold' => $isSetupWizard,
                            'text-slate-800' => ! $isCurrentRoute
                        ])
                    >
                        {{ $menu['title'] }}
                    </span>
                    @if ($menu['badge'] ?? false)
                        @if($menu['new_membership_inquiry_count'] ?? false)
                            <div x-data="{ newMembershipInquiryCount: @js($menu['new_membership_inquiry_count']) }">
                                <template x-if="parseInt(newMembershipInquiryCount) > 0">
                                    <span class="relative flex">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-error opacity-75"></span>
                                        <span
                                            class="relative badge font-semibold text-xs+ rounded-full text-white bg-error/80 min-w-6 px-1 h-6"
                                            x-on:membership-inquiry-count-updated.window="newMembershipInquiryCount = $event.detail[0]"
                                            x-text="newMembershipInquiryCount > 99 ? '99+' : newMembershipInquiryCount"
                                        ></span>
                                    </span>
                                </template>
                            </div>
                        @endif
                        @if($menu['new_offer_count'] ?? false)
                            <div x-data="{ newOfferCount: @js($menu['new_offer_count']) }">
                                <template x-if="parseInt(newOfferCount) > 0">
                                    <span class="relative flex">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-error opacity-75"></span>
                                        <span
                                            class="relative badge font-semibold text-xs+ rounded-full text-white bg-error/80 min-w-6 px-1 h-6"
                                            x-on:new-offer-count-updated.window="newOfferCount = $event.detail[0]"
                                            x-text="newOfferCount > 99 ? '99+' : newOfferCount"
                                        ></span>
                                    </span>
                                </template>
                            </div>
                        @endif
                    @endif
                </div>
            </a>
        </li>
    @else
        <li
            x-data="{ isCurrentRoute: @js(collect($menu['items'])->pluck('route_name')->contains(fn ($routeName) => str(request()->route()?->getName())->contains($routeName))) }"
            x-init="() => {
                if (isCurrentRoute) {
                    $store.sidebar.collapsedGroups = ['{{ $parentMenuTitle }}', '{{ $menu['title'] }}']
                }
            }"
        >
            <div
                class="group flex justify-between items-center gap-x-3 rounded-lg p-2 tracking-wide outline-none transition-all cursor-pointer text-black hover:bg-slate-100 focus:bg-slate-100"
                @click="$store.sidebar.toggleCollapsedGroup('{{ $menu['title'] }}')"
            >
                <div class="group flex space-x-2 items-center">
                    <div
                        class="size-4 transition-colors group-hover:font-medium group-focus:font-medium"
                        :class="{
                            'text-black': $store.sidebar.groupIsCollapsed('{{ $menu['title'] }}'),
                            'text-black': !$store.sidebar.groupIsCollapsed('{{ $menu['title'] }}'),
                        }"
                    >
                        {{ svg($menu['icon']) }}
                    </div>
                    <span
                        class="text-slate-800 leading-6"
                        x-bind:class="{
                            'font-semibold': $store.sidebar.groupIsCollapsed('{{ $menu['title'] }}'),
                            'font-normal hover:font-semibold': !$store.sidebar.groupIsCollapsed('{{ $menu['title'] }}')
                        }"
                    >
                        {{ $menu['title'] }}
                    </span>
                </div>
                <button>
                    <x-heroicon-o-chevron-down
                        class="size-4 text-slate-950 transition-transform ease-in-out"
                        x-bind:class="{ '-rotate-180': $store.sidebar.groupIsCollapsed('{{ $menu['title'] }}') }"
                    />
                </button>
            </div>
            <ul
                x-show="$store.sidebar.groupIsCollapsed('{{ $menu['title'] }}')"
                x-collapse.duration.200ms
                class="space-y-1.5 pl-5"
            >
                <x-sidebar.sidebar-composer
                    :sidebarMenu="$menu['items']"
                    :parentMenuTitle="$menu['title']"
                />
            </ul>
        </li>
    @endif
@endforeach

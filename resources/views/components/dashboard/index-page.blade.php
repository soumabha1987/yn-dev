@props([
    'routeName' => 'creditor.dashboard',
])

<div>
    @if (session()->pull('show-wizard-completed-modal'))
        <x-wizard-setup-completed-modal />
    @endif

    <livewire:creditor.dashboard.stats.index lazy />

    <div
        class="card mt-8"
        x-data="scrollTab"
    >
        <div class="py-4 px-6 xl:px-4 border border-slate-200 border-l-0 border-t-0 border-r-0 relative">
            <div
                x-ref="tabs"
                x-on:scroll="checkScrollPosition"
                class="flex p-2 space-x-2 whitespace-nowrap items-center is-scrollbar-hidden sm:w-full overflow-x-auto"
            >
                <template x-if="isOverflowing && !isAtStart">
                    <x-lucide-chevron-left
                        x-on:click="scrollLeft"
                        class="absolute -left-2 xl:-left-3 top-8 size-6 cursor-pointer will-change-transform hover:animate-wiggle"
                    />
                </template>
                <a
                    wire:navigate
                    href="{{ route('creditor.dashboard') }}"
                    @class([
                        'badge text-base font-medium hover:bg-accent/30 bg-accent/10 text-accent',
                       '!bg-accent outline outline-1 outline-accent outline-offset-4 !text-white' => $routeName === 'creditor.dashboard',
                    ])
                >
                    {{ __('Open Negotiations') }}
                </a>
                <a
                    wire:navigate
                    href="{{ route('creditor.dashboard.recent-transactions') }}"
                    @class([
                        'badge text-base font-medium hover:bg-success/30 bg-success/10 text-success',
                        '!bg-success outline outline-1 outline-success outline-offset-4 !text-white' => $routeName === 'creditor.dashboard.recent-transactions',
                    ])
                >
                    {{ __('Recent Payments') }}
                </a>
                <a
                    wire:navigate
                    href="{{ route('creditor.dashboard.failed-payments') }}"
                    @class([
                        'badge text-base font-medium hover:bg-error/30 bg-error/10 text-error',
                        '!bg-error outline outline-1 outline-error outline-offset-4 !text-white' => $routeName === 'creditor.dashboard.failed-payments',
                    ])
                >
                    {{ __('Failed Payments') }}
                </a>
                <a
                    wire:navigate
                    href="{{ route('creditor.dashboard.upcoming-transactions') }}"
                    @class([
                        'badge text-base font-medium hover:bg-primary/30 bg-primary/10 text-primary',
                        '!bg-primary outline outline-1 outline-primary outline-offset-4 !text-white' => $routeName === 'creditor.dashboard.upcoming-transactions',
                    ])
                >
                    {{ __('Upcoming Payments') }}
                </a>
                <a
                    wire:navigate
                    href="{{ route('creditor.dashboard.dispute-reports') }}"
                    @class([
                        'badge text-base font-medium hover:bg-secondary-orange/30 bg-secondary-orange/10 text-secondary-orange',
                        '!bg-secondary-orange outline outline-1 outline-secondary-orange outline-offset-4 !text-white' => $routeName === 'creditor.dashboard.dispute-reports',
                    ])
                >
                    {{ __('Disputes/No Pay') }}
                </a>
                <a
                    href="{{ route('creditor.dashboard.completed-negotiations') }}"
                    wire:navigate
                    @class([
                        'badge text-base font-medium hover:bg-info/30 bg-info/10 text-info',
                        '!bg-info outline outline-1 outline-info outline-offset-4 !text-white' => $routeName === 'creditor.dashboard.completed-negotiations'
                    ])
                >
                    {{ __('Negotiated/Pending Payment') }}
                </a>
                <a
                    href="{{ route('creditor.dashboard.recently-completed-negotiations') }}"
                    wire:navigate
                    @class([
                        'badge text-base font-medium hover:bg-secondary/30 bg-secondary/10 text-secondary',
                        '!bg-secondary outline outline-1 outline-secondary outline-offset-4 !text-white' => $routeName === 'creditor.dashboard.recently-completed-negotiations'
                    ])
                >
                    {{ __('Recently Completed Negotiations') }}
                </a>
                <template x-if="isOverflowing && !isAtEnd">
                    <x-lucide-chevron-right
                        x-on:click="scrollRight"
                        class="absolute -right-0.5 xl:-right-1.5 top-8 size-6 cursor-pointer will-change-transform hover:animate-wiggle"
                    />
                </template>
            </div>
        </div>
        {{ $slot }}
    </div>

    @script
        <script>
            Alpine.data('scrollTab', () => {
                return {
                    isOverflowing: false,
                    isAtStart: true,
                    isAtEnd: false,

                    init() {
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
                    }
                }
            })
        </script>
    @endscript
</div>

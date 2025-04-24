@use('App\Enums\ConsumerStatus')

<div x-on:refresh-page.window="$wire.$refresh">
    <main
        x-data="myAccount"
        class="w-full pb-8"
    >
    <div class="relative flex md:flex-row flex-col md:gap-8 sm:items-end md:items-center md:ml-8">
        <div
            x-ref="statuses"
            x-on:scroll="checkScrollPosition"
            {{-- Changing the `w-10/12 sm:w-11/12 md:w-full` class here will break the functionality of the left and right arrows. --}}
            class="flex items-center is-scrollbar-hidden overflow-x-auto mt-2 sm:mt-0 w-10/12 sm:w-11/12 md:w-full mx-auto md:mx-0 py-1 px-1"
        >
            <template x-if="isOverflowing && !isAtStart">
                <x-lucide-chevron-left
                    x-on:click="scrollLeft"
                    class="absolute left-0 sm:-left-0.5 md:-left-[1.75rem] top-[4.5rem] sm:top-16 md:top-2 size-6 cursor-pointer will-change-transform hover:animate-wiggle"
                />
            </template>
            @foreach ($consumerStatuses as $statusKey => $consumerStatus)
                <div class="mr-1">
                    <button
                        wire:click="updateStatus('{{ $statusKey }}')"
                        @class([
                            'tag p-1 rounded-full px-3 text-base whitespace-nowrap',
                            'bg-error/10 text-error hover:bg-error/20 focus:bg-error/20 active:bg-error/25' => $loop->first,
                            '!outline outline-2 outline-error bg-error/20' => $status === 'all' && $consumerStatus === 'All',
                            $consumerStatus['class'] ?? '',
                            $consumerStatus['active_class'] ?? '' => $status === $statusKey,
                        ])
                    >
                        {{ $loop->first ? $consumerStatus : $consumerStatus['tab'] }}
                    </button>
                </div>
            @endforeach
            <template x-if="isOverflowing && !isAtEnd">
                <x-lucide-chevron-right
                    x-on:click="scrollRight"
                    class="absolute right-0 sm:-right-0.5 md:right-[23rem] top-[4.5rem] sm:top-16 md:top-2 size-6 cursor-pointer will-change-transform hover:animate-wiggle"
                />
            </template>
        </div>

            <div class="flex flex-col sm:flex-row items-start sm:items-center mx-auto sm:mx-0 space-x-2 md:order-last order-first w-full md:w-auto">
                <label class="relative flex w-full mb-5 md:mb-0">
                    <input
                        wire:model.live.debounce.400="search"
                        class="form-input peer h-9 w-full md:w-44 rounded-full border border-slate-300 bg-transparent px-3 py-2 pl-9 text-xs+ placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary"
                        placeholder="{{ __('Search accounts') }}"
                        autocomplete="off"
                    />
                    <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary">
                        <x-lucide-search class="size-5" />
                    </span>
                    <span
                        class="absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 hover:cursor-pointer hover:text-error"
                        x-tooltip.placement.bottom="@js(__('Clear'))"
                        x-show="$wire.search"
                        x-on:click="$wire.set('search', '')"
                    >
                        <x-lucide-x class="size-3.5" />
                    </span>
                </label>
                <div class="hidden md:inline-flex bg-slate-200 text-sm text-slate-500 leading-none border-2 border-slate-200 rounded-full">
                    <button
                        x-on:click="switchToCardView"
                        class="inline-flex items-center transition-all ease-in focus:outline-none hover:text-primary focus:text-primary rounded-l-full px-4 py-2"
                        x-bind:class="activeView === 'card' && 'bg-white text-primary rounded-full'"
                    >
                        <x-lucide-layout-grid class="size-5 mr-2" />
                        <span>{{ __('Card') }}</span>
                    </button>
                    <button
                        x-on:click="switchToListView"
                        class="inline-flex items-center transition-all ease-in focus:outline-none hover:text-primary focus:text-primary rounded-r-full px-4 py-2"
                        x-bind:class="activeView === 'list' && 'bg-white text-primary rounded-full'"
                    >
                        <x-lucide-list class="size-5 mr-2" />
                        <span>{{ __('List') }}</span>
                    </button>
                </div>
            </div>
        </div>

        @if (session()->pull('complete-payment-setup'))
            <x-consumer.complete-payment-setup />
        @endif

        <div x-show="activeView === 'card'">
            <x-consumer.my-accounts.card-view :$accounts />
        </div>
        <div x-show="activeView === 'list'">
            <x-consumer.my-accounts.grid-view :$accounts />
        </div>

        <x-consumer.dialog wire:model="updateCommunicationModal">
            <x-consumer.dialog.panel :blank-panel="true" size="xl">
                <span class="flex items-center justify-center text-4xl sm:text-8xl mt-6 mb-4">🤓</span>

                <h3 class="text-lg md:text-xl py-2 font-bold flex justify-center text-black">
                    <span>{{ __('Welcome to Your Portal Account!') }}</span>
                </h3>
                <p class="text-xs md:text-sm text-center text-black pb-2 px-4">
                    {{ __('Get in control setting up your communication profile and then you’re ready to knock out your past due bills!!') }}
                </p>
                <div class="flex justify-center px-4 sm:px-0">
                    <div class="sticky top-24 mt-5">
                        <ol class="steps is-vertical line-space md:text-lg">
                            <li class="step items-center w-full pb-3 before:bg-slate-200">
                                <div class="step-header rounded-full font-semibold bg-primary">
                                    <span class=" md:text-lg text-white font-semibold">1</span>
                                </div>
                                <a
                                    wire:navigate
                                    href="{{ route('consumer.communication_controls') }}"
                                    class="group flex w-fit items-center text-primary justify-between space-x-2 ml-4 font-semibold tracking-wide"
                                    x-on:focus="$el.blur()"
                                >
                                    <div class="flex flex-col sm:flex-row items-baseline gap-1">
                                        <span class="group-hover:underline text-wrap group-hover:underline-offset-4 tracking-wide">
                                            {{ __('Communication Preferences') }}
                                        </span>
                                        <span class="text-error text-xs+ md:text-xs">
                                            {{ __('(Required)') }}
                                        </span>
                                    </div>
                                </a>
                            </li>

                            <li class="step items-center w-full pb-3 before:bg-slate-200">
                                <div class="step-header font-semibold rounded-full bg-primary">
                                    <span class="md:text-lg text-white font-semibold">2</span>
                                </div>
                                <a
                                    wire:navigate
                                    href="{{ route('consumer.profile') }}"
                                    class='group flex w-fit items-center text-primary justify-between space-x-2 ml-4 font-semibold tracking-wide'
                                    x-on:focus="$el.blur()"
                                >
                                    <div class="flex flex-row items-baseline gap-1">
                                        <span class="group-hover:underline group-hover:underline-offset-4 tracking-wide">
                                            {{ __('Profile') }}
                                        </span>
                                        <span class="text-black text-xs+ md:text-xs">
                                            {{ __('(Optional)') }}
                                        </span>
                                    </div>
                                </a>
                            </li>

                            <li class="step items-center w-full pb-3 before:bg-slate-200">
                                <div class="step-header rounded-full font-semibold bg-primary">
                                    <span class="md:text-lg text-white font-semibold">3</span>
                                </div>
                                <a
                                    wire:navigate
                                    href="#"
                                    class="group flex w-fit items-center text-slate-500 justify-between space-x-2 ml-4 font-semibold tracking-wide cursor-not-allowed"
                                >
                                    <div class="flex flex-col sm:flex-row items-baseline gap-1">
                                        <div class="group-hover:underline group-hover:underline-offset-4 tracking-wide">
                                            {{ __('Tour of Your Portal') }}
                                        </div>
                                        <div class="text-sm blink text-primary font-semibold">
                                            {{ __('Coming Soon') }}
                                        </div>
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
                                wire:click="ignoreCommunication"
                                wire:target="ignoreCommunication"
                                class="w-40 font-semibold"
                            >
                                {{ __('Close') }}
                            </x-form.button>
                        </x-dialog.close>
                    </div>
                </div>
            </x-consumer.dialog.panel>
        </x-consumer.dialog>
    </main>

    @script
        <script>
            Alpine.data('myAccount', function () {
                return {
                    isOverflowing: false,
                    isAtStart: true,
                    isAtEnd: false,
                    activeView: Alpine.$persist('card').as('current-view'),
                    init() {
                        window.addEventListener('resize', () => {
                            if (window.innerWidth < 768) {
                                this.activeView = 'card'
                            }

                            this.isOverflowing = this.$refs.statuses.scrollWidth > this.$refs.statuses.clientWidth;
                            this.checkScrollPosition()
                        })

                        this.$nextTick(() => {
                            this.isOverflowing = this.$refs.statuses.scrollWidth > this.$refs.statuses.clientWidth;
                            this.checkScrollPosition()
                        })
                    },
                    switchToCardView() {
                        this.activeView = 'card'
                    },
                    switchToListView() {
                        this.activeView = 'list'
                    },
                    scrollRight() {
                        this.$refs.statuses.scrollBy({
                            left: this.$refs.statuses.clientWidth * 0.8,
                            behavior: 'smooth'
                        })
                    },
                    scrollLeft() {
                        this.$refs.statuses.scrollBy({
                            left: -this.$refs.statuses.clientWidth * 0.8,
                            behavior: 'smooth'
                        })
                    },
                    checkScrollPosition() {
                        this.isAtStart = this.$refs.statuses.scrollLeft === 0
                        this.isAtEnd = this.$refs.statuses.scrollLeft + this.$refs.statuses.clientWidth + 1 >= this.$refs.statuses.scrollWidth
                    }
                }
            })
        </script>
    @endscript
</div>

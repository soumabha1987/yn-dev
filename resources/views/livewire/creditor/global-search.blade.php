<div>
    <div
        x-data="globalSearch"
        class="bg-white relative hidden mr-4 sm:flex h-8 justify-between items-center"
    >
        <label class="relative flex">
            <input
                name="search"
                placeholder="{{ __('Search Consumer') }}"
                wire:model="search"
                wire:keyup.enter="searchConsumer"
                class="sm:w-80 form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary"
                autocomplete="off"
            >
            <div class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary">
                <x-heroicon-o-magnifying-glass class="size-4.5 transition-colors duration-200" />
            </div>

            <div
                x-show="$wire.search"
                class="absolute right-0 flex h-full w-10 items-center justify-center hover:text-error hover:cursor-pointer"
                x-tooltip.placement.bottom="@js(__('Clear'))"
                wire:click="resetSearch"
            >
                <x-lucide-x class="size-3.5 transition-colors duration-200" />
            </div>
        </label>
    </div>

    @script
        <script>
            Alpine.data('globalSearch', () => ({
                init() {
                    this.$wire.$watch('search', async (newValue) => {
                        if (newValue === '') {
                            await this.$wire.$set('search', '')
                            this.$dispatch('refresh-global-search')
                        }
                    })
                }
            }))
        </script>
    @endscript
</div>

<div>
    @if ($paginator->hasPages())
        <div class="flex items-center space-x-1">
            <div class="flex items-center space-x-2">
                <span>{{ $paginator->firstItem() ?? 0 }} - {{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }}</span>
                <div class="flex">
                    @if ($paginator->onFirstPage())
                        <span class="btn cursor-auto opacity-50 size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25">
                            <x-lucide-chevron-left class="size-5" />
                        </span>
                    @else
                        <button
                            wire:click="previousPage"
                            class="btn size-8 disabled:opacity-50 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25"
                            wire:loading.attr="disabled"
                        >
                            <x-lucide-chevron-left class="size-5" />
                        </button>
                    @endif
                    @if ($paginator->onLastPage())
                        <span class="btn cursor-auto opacity-50 size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25">
                            <x-lucide-chevron-right class="size-5" />
                        </span>
                    @else
                        <button
                            wire:click="nextPage"
                            class="btn size-8 disabled:opacity-50 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25"
                            wire:loading.attr="disabled"
                        >
                           <x-lucide-chevron-right class="size-5" />
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

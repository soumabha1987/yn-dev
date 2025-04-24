<div class="text-sm+ hidden sm:block">
    {{ __('Showing') }} {{ $paginator->firstItem() }} {{ __('to') }} {{ $paginator->lastItem() }} {{ __('of') }} {{ $paginator->total() }} {{ __('entries') }}
</div>

@if($paginator->hasPages())
    <ol class="pagination flex justify-between space-x-2 items-center">
        <li class="rounded-full">
            @if ($paginator->onFirstPage())
                <span class="flex px-1 items-center justify-start rounded-full text-slate-400 transition-colors">
                    <div class="flex items-center space-x-2">
                        <span class="p-1.5">{{ __('Previous') }}</span>
                    </div>
                </span>
            @else
                <button
                    wire:click="previousPage"
                    wire:loading.attr="disabled"
                    class="flex px-1 items-center justify-start rounded-full text-slate-500 transition-colors hover:bg-slate-300 focus:bg-slate-300 active:bg-slate-300/80"
                >
                    <div class="flex items-center space-x-2">
                        <span class="p-1.5">{{ __('Previous') }}</span>
                    </div>
                </button>
            @endif
        </li>

        @foreach ($elements as $element)
            @if (is_string($element))
                <span aria-disabled="true">
                    <span class="hidden lg:flex h-8 min-w-[2rem] items-center justify-center rounded-full px-3 leading-tight transition-colors hover:bg-slate-300">
                        {{ $element }}
                    </span>
                </span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page === $paginator->currentPage())
                        <span aria-current="page">
                            <span class="hidden lg:flex h-8 min-w-[2rem] items-center justify-center rounded-full bg-primary px-3 leading-tight text-white transition-colors hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90">
                                {{ $page }}
                            </span>
                        </span>
                    @else
                        <button
                            type="button"
                            wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                            class="hidden lg:flex h-8 min-w-[2rem] items-center justify-center rounded-full px-3 leading-tight transition-colors hover:bg-slate-300"
                            aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                        >
                            {{ $page }}
                        </button>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <button
                wire:click="nextPage"
                wire:loading.attr="disabled"
                class="flex px-1 items-center justify-end rounded-full text-slate-500 transition-colors hover:bg-slate-300 focus:bg-slate-300 active:bg-slate-300/80"
                aria-label="{{ __('pagination.next') }}"
            >
                <div class="flex space-x-2 items-center">
                    <span class="p-1.5">{{ __('Next') }}</span>
                </div>
            </button>
        @else
            <span
                aria-disabled="true"
                aria-label="{{ __('pagination.next') }}"
            >
                <span
                    class="flex px-1 items-center justify-end rounded-full text-slate-400 transition-colors"
                    aria-hidden="true"
                >
                    <span class="p-1.5">{{ __('Next') }}</span>
                </span>
            </span>
        @endif
    </ol>
@endif

@props([
    'column' => '',
    'sortCol',
    'sortAsc',
])

<th {{ $attributes->merge(['class' => 'text-xs+ sm:text-xs text-balance border border-l-0 border-r border-slate-200 bg-slate-50 px-2 py-2 font-semibold uppercase text-slate-950']) }}>
    @if ($column !== '')
        <button
            type="button"
            wire:click="sortBy('{{ $column }}')"
            class="uppercase group"
        >
            <div class="flex items-center gap-2">
                <span>{{ $slot }}</span>
                <div class="text-gray-700">
                    @if ($sortCol === $column)
                        @if($sortAsc)
                            <x-lucide-chevron-up class="size-5" />
                        @else
                            <x-lucide-chevron-down class="size-5" />
                        @endif
                    @else
                        <x-lucide-chevrons-up-down class="size-5 opacity-40 group-hover:opacity-100" />
                    @endif
                </div>
            </div>
        </button>
    @else
        {{ $slot }}
    @endif
</th>

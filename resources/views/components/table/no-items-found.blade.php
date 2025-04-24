@props(['colspan'])

<x-table.tr class="!border-0">
    <x-table.td
        colspan="{{ $colspan }}"
        {{ $attributes->merge(['class' => 'text-center border-none']) }}
    >
        {{ __('No result found') }}
    </x-table.td>
</x-table.tr>

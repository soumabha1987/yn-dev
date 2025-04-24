@props([
    'disableLoader' => false,
])

<table {{ $attributes->merge(['class' => 'w-full text-left td-last-child']) }}>
    <thead {{ $tableHead->attributes }}>
        {{ $tableHead }}
        @unless ($disableLoader)
            <tr
                wire:loading
                wire:loading.class="!table-row"
                class="h-1 bg-gradient-to-r from-primary-200 to-primary loader-line"
            >
                <td colspan="20"></td>
            </tr>
        @endunless
    </thead>
    <tbody {{ $tableBody->attributes }}>
        {{ $tableBody }}
    </tbody>
</table>

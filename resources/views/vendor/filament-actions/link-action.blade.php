<x-filament-actions::action
    :action="$action"
    :badge="$getBadge()"
    :badge-color="$getBadgeColor()"
    dynamic-component="filament::link"
    :icon-position="$getIconPosition()"
    :size="$getSize()"
>
    <div class="text-sm font-medium text-white underline underline-offset-2">
        {{ $getLabel() }}
    </div>
</x-filament-actions::action>

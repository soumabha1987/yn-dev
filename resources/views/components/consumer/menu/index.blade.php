<div x-data="{ menuOpen: false }">
    <div x-menu x-model="menuOpen" class="flex items-center">
        {{ $slot }}
    </div>
</div>

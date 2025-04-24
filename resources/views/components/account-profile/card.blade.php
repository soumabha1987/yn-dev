@props(['cardTitle' => ''])

<div>
    <div class="w-full p-4 sm:p-5">
        <form
            method="POST"
            {{ $attributes }}
            autocomplete="off"
        >
            @if (filled($cardTitle))
                <hr class="mb-4 h-px bg-slate-200">

                <div class="flex justify-between items-center">
                    <p class="capitalize text-lg tracking-wide text-black font-semibold">
                        {{ $cardTitle }}
                    </p>

                    <div class="sm:flex justify-end space-x-1 hidden">
                        {{ $actionButtons }}
                    </div>
                </div>

                <hr class="mt-4 h-px bg-slate-200">
            @endif

            {{ $slot }}

            <div class="flex flex-col sm:hidden justify-end gap-2 mt-4">
                {{ $actionButtons }}
            </div>
        </form>
    </div>
</div>

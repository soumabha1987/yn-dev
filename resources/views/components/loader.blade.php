<div
   {{ $attributes }}
    class="fixed cursor-progress inset-0 z-max top-0 left-0 right-0 bottom-0 overflow-auto size-full bg-gray-700/50 flex flex-col justify-center"
>
    <div class="fixed flex inset-0 items-center justify-center">
        <div class="flex flex-col items-center">
            <x-lucide-loader-2 class="size-20 animate-spin text-white/80"></x-lucide-loader-2>
            <h2 class="text-center text-white/80 text-xl font-semibold select-none">
                {{ __('Please wait..') }}
            </h2>
        </div>
    </div>
</div>

@props([
    'from' => '(321) 123-4567',
    'content' => null,
])

<div class="relative size-full flex flex-col items-center text-sm">
    <div class="relative mx-auto border-gray-600 bg-gray-600 border-[6px] rounded-[2.5rem] shadow-xl">
        <div class="rounded-[2rem] overflow-hidden w-[240px] h-[380px] bg-white">
            <div class="flex mt-1 justify-center">
                <x-lucide-circle class="size-6 fill-black" />
            </div>
            <div class="p-4 border-b border-gray-200 text-center font-semibold text-gray-600">{{ $from ? $from : '(No phone number)' }}</div>
            <div class="flex flex-col items-end justify-start space-y-2 h-full p-4">
                <div class="flex space-x-2 flex-row-reverse space-x-reverse">
                    <div class="flex flex-col space-y-1">
                        <div class="flex flex-col w-full leading-1.5 p-4 py-2 border border-surface-100 bg-surface-50 rounded-xl min-w-[206px] shadow-sm">
                            <div class="prose text-xs font-normal text-gray-700 h-24 overflow-y-auto scroll-bar-visible">
                                {{ $content }}
                            </div>
                        </div>
                        <div class="flex flex-col space-y-0.5 rtl:space-x-reverse overflow-hidden line-clamp-2 pr-2 text-right">
                            <div class="text-xs font-normal text-gray-500">{{ __('Delivered') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@props([
    'groupSize' => '',
    'totalBalance' => '',
])

<x-dialog {{ $attributes }}>
    {{ $slot }}
    <x-dialog.panel 
        :heading="__('Preview Group Size')" 
        size="2xl"
    >
        <div class="p-8">
            <div class="my-6">
                <div class="flex flex-col sm:flex-row gap-5">
                    <div class="border rounded-lg w-full text-center p-4">
                        <p class="text-xl font-bold capitalize text-black">{{ __('Consumer Count') }}</p>
                        <p class="text-2xl font-semibold text-slate-700 mt-4">
                            {{ $groupSize }}
                        </p>
                    </div>

                    <div class="border rounded-lg w-full text-center p-4">
                        <p class="text-xl font-bold capitalize text-black">{{ __('Current Balances') }}</p>
                        <p class="mt-4 text-2xl font-semibold text-slate-700">
                            {{ $totalBalance }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </x-dialog.panel>
</x-dialog>

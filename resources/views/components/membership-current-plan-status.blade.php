@props([
    'name',
    'endDate',
    'button' => null,
    'note',
    'isAutoRenewPlan'
])
<div class="card bg-primary/10 border-primary p-4 mx-auto">
    <div class="flex items-center sm:justify-between flex-wrap">
        <h2 class="text-lg font-semibold text-gray-800 sm:mb-4">
            {{ $endDate->gt(today()) ? __('Current Plan') : __('Previous Plan') }}
        </h2>
        <div class="mt-2 sm:mt-0">
            {{ $button }}
        </div>
    </div>

    <div class="space-y-2 mt-4 sm:mt-0">
        <div class="flex flex-col sm:flex-row items-start flex-wrap">
            <span class="text-gray-700 w-40 font-bold shrink-0">{{ __('Plan') }}</span>
            <div class="flex items-center space-x-2">
                <x-lucide-trophy class="size-4" />
                <span class="text-gray-800">
                    {{ $name }}
                </span>
            </div>
        </div>

        <div class="flex items-start flex-wrap">
            <span class="text-gray-700 w-40 font-bold">
                {{ $endDate->gt(today()) ? __('Next Payment') : __('Expired At') }}
            </span>
            <div class="flex items-center space-x-2">
                <x-lucide-alarm-clock class="size-4" />
                <span class="text-gray-800">
                    {{ $isAutoRenewPlan ? $endDate->formatWithTimezone() : '----'}}
                </span>
            </div>
        </div>

        <div class="flex items-start flex-wrap sm:flex-nowrap">
            <span class="text-gray-700 font-bold w-14 block mb-1">{{ __('Note') }}</span>
            <span class="text-gray-700 text-sm">
                {{ $note }}
            </span>
        </div>
    </div>
</div>

@props([
    'minFirstPayDate' => today()->addDay()->toDateString(),
    'maxFirstPayDate' => today()->addMonthNoOverflow()->toDateString(),
    'modelable' => 'form.first_pay_date',
])

{{-- You might wonder why we're passing it here instead of accessing it directly in alpine.data. --}}
{{-- The issue arises when rendering two components in the same file—Alpine will use the props from the second component in both instances. --}}
{{-- To prevent this, we explicitly pass the props like this. --}}
<div x-data="firstPayDate(@js($minFirstPayDate), @js($maxFirstPayDate), @js($modelable))">
    <style>
        .flatpickr-calendar {
            margin-top: 1rem;
        }

        .flatpickr-calendar,
        .flatpickr-months,
        .flatpickr-innerContainer,
        .flatpickr-rContainer,
        .flatpickr-weekdays,
        .flatpickr-days,
        .dayContainer {
            width: 100% !important;
            max-width: 100% !important;
            min-width: auto;
        }

        .flatpickr-day {
            max-width: none;
        }
        .flatpickr-day{
            font-weight:600;
        }
        .flatpickr-day.nextMonthDay{
            color: rgba(72,72,72,0.7)
        }
    </style>
    <div
        wire:ignore
        class="block"
    >
        <input
            hidden
            wire:model="{{ $modelable }}"
            x-init="flatpickr"
            class="peer w-full"
        >
    </div>
    @error($modelable)
        <div class="mt-1">
            <span class="text-medium text-error">
                {{ $message }}
            </span>
        </div>
    @enderror

</div>


@script
    <script>
        Alpine.data('firstPayDate', (minFirstPayDate, maxFirstPayDate, modelable) => ({
            flatpickrInstance: null,
            init() {
                this.$wire.$watch(modelable, (newVal) => {
                    if (newVal === '') {
                        this.flatpickrInstance?.clear();
                    }
                });
            },
            flatpickr() {
                this.flatpickrInstance = window.flatpickr(this.$el, {
                    dateFormat: 'Y-m-d',
                    inline: true,
                    minDate: minFirstPayDate,
                    
                })
            },
            destroy() {
                this.flatpickrInstance?.destroy();
            }
        }))
    </script>
@endscript
@use('App\Enums\NegotiationType')

<div x-data="firstPayDate">
    <style>
        .flatpickr-calendar {
            margin-top: 1rem;
            border: 0.5px solid #94a3b8;
            border-radius: 8px;
        }

        .flatpickr-months .flatpickr-next-month.flatpickr-next-month {
            right: 4px !important;
            top: 4px !important;
        }

        .flatpickr-months .flatpickr-prev-month.flatpickr-prev-month {
            left: 4px !important;
            top: 4px !important;
        }

        .flatpickr-months .flatpickr-prev-month:hover svg,
        .flatpickr-months .flatpickr-next-month:hover svg {
            fill: #fff !important;
        }

        .flatpickr-innerContainer{
            border-bottom-right-radius: 8px !important;
            border-bottom-left-radius: 8px !important;
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

        .flatpickr-month,
        .flatpickr-monthDropdown-months,
        .flatpickr-weekdays,
        .flatpickr-weekday,
        .flatpickr-weekdaycontainer,
        .flatpickr-next-month,
        .flatpickr-prev-month {
            color: #ffff !important;
            background-color: #2563eb !important;
        }

        .flatpickr-next-month:hover,
        .flatpickr-prev-month:hover {
            color: #ffff !important;
        }

        .flatpickr-monthDropdown-months, .numInputWrapper {
            border: 1px solid #fff !important;
        }

         .numInputWrapper span:hover {
            background: #fff !important;
        }

        .flatpickr-day {
            max-width: none;
        }
    </style>
    <div
        wire:ignore
        class="block"
    >
        <span
            class="font-semibold text-base lg:text-xl capitalize"
            x-text="$wire.form.negotiation_type === @js(NegotiationType::PIF->value) ? @js(__('Choose payment date')) : @js(__('Choose first payment date'))"
        ></span>
        <input
            hidden
            wire:model="form.first_pay_date"
            x-init="flatpickr"
            class="peer w-full"
        >
    </div>
    @error('form.first_pay_date')
        <div class="mt-1">
            <span class="text-medium text-error">
                {{ $message }}
            </span>
        </div>
    @enderror
</div>

@script
    <script>
        Alpine.data('firstPayDate', () => ({
            flatpickrInstance: null,
            init() {
                this.$wire.$watch('form.first_pay_date', (newVal) => {
                    if (newVal === '') {
                        this.flatpickrInstance?.clear()
                    }
                })
            },
            flatpickr() {
                this.flatpickrInstance = window.flatpickr(this.$el, {
                    dateFormat: 'Y-m-d',
                    inline: true,
                    minDate: @js(today()->addDay()->toDateString()),
                    maxDate: @js(today()->addYear()->toDateString()),
                })
            },
            destroy() {
                this.flatpickrInstance?.destroy()
            }
        }))
    </script>
@endscript

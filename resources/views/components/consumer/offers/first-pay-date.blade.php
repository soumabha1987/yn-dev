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

        .flatpickr-day {
            font-weight: 600;
        }

        .flatpickr-day.nextMonthDay {
            color: rgba(72, 72, 72, 0.7)
        }

        /* Hover effect for valid dates */
        .flatpickr-day:hover {
            background-color: rgba(255, 255, 0, 0.3); /* Yellow with transparency */
            color: #333; /* Dark text color */
            cursor: pointer; /* Ensure the pointer cursor is shown */
        }

        /* Hover effect for valid dates with yellow background */
        .flatpickr-day.bg-yellow-500:hover {
            background-color: rgba(255, 255, 0, 0.5); /* Lighter yellow for hover */
            color: #333; /* Dark text color */
        }

        /* Hover effect for invalid dates with transparent green background */
        .flatpickr-day.bg-green-500:hover {
            background-color: rgba(0, 255, 0, 0.3); /* Green with transparency */
            color: #155724; /* Dark green text */
        }

        /* Hover effect for invalid dates with transparent yellow background */
        .flatpickr-day.bg-yellow-500 {
            background-color: rgba(255, 255, 0, 0.3); /* Yellow with transparency */
            color: black;
        }

        .flatpickr-day.bg-green-500 {
            background-color: rgba(0, 128, 0, 0.3); /* Green with transparency */
            color: black;
        }

        
    </style>
    <div
        wire:ignore
        class="block">
        <input
            hidden
            wire:model="{{ $modelable }}"
            x-init="flatpickr"
            class="peer w-full">
    </div>
   

</div>

<div x-data="{ modalOpen: false, invalidDate: null }"
    x-init="window.addEventListener('invalid-date-clicked', e => { 
         invalidDate = e.detail.date; 
         modalOpen = true; 
     })">

    <!-- Modal -->
    <div x-show="modalOpen" x-transition class="fixed inset-0 flex items-center justify-center bg-black inset-0 bg-slate-900/60 transition-opacity z-50">
        <div class="bg-white rounded-lg p-6 max-w-sm w-full shadow-lg text-center">
            <p class="text-red-600 font-semibold text-base mb-4">
                Your selected 1st pay date is out of the Creditor's 1st pay date range
            </p>
            <div class="flex justify-center gap-4">
                <button
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded"
                    @click="modalOpen = false">
                    Change Date
                </button>
                <x-form.button
                    wire:click="sendToCreditor"
                    wire:loading.attr="disabled"
                    wire:target="sendToCreditor"
                    type="button"
                    variant="primary">
                    <span>{{ __('Send to Creditor') }}</span>
                </x-form.button>

            </div>
        </div>
    </div>
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
                showMonths: 1,
                showDaysOutsideMonth: false,
                disable: [
                    // Disable all dates before today
                    (date) => {
                        const today = new Date();
                        return date < today.setHours(0, 0, 0, 0); // Disable all previous dates
                    }
                ],
                onDayCreate: function(dObj, dStr, fp, dayElem) {
                    const date = dayElem.dateObj;
                    const min = new Date(minFirstPayDate);
                    const max = new Date(maxFirstPayDate);

                    const dateOnly = new Date(date.getFullYear(), date.getMonth(), date.getDate());
                    const minOnly = new Date(min.getFullYear(), min.getMonth(), min.getDate());
                    const maxOnly = new Date(max.getFullYear(), max.getMonth(), max.getDate());

                    // ✅ Hide days outside current month
                    if (date.getMonth() !== fp.currentMonth) {
                        dayElem.style.visibility = "hidden"; // 👈 Hide the day completely
                        return;
                    }

                    // Reset any styles first
                    dayElem.className = "flatpickr-day"; // Reset classes first to avoid duplication

                    // Now add circle styles
                    dayElem.classList.add(
                        'flex', 'items-center', 'justify-center',
                        'rounded-full', 'w-10', 'h-10',
                        'text-sm', 'mx-auto', 'my-1'
                    );  
                    //dayElem.style.flex = 'none';

                    if (dateOnly >= minOnly && dateOnly <= maxOnly) {
                        dayElem.classList.add('bg-green-500', 'text-black', 'font-semibold');
                    } else {
                        dayElem.classList.add('bg-yellow-500', 'text-black', 'font-semibold', 'cursor-pointer');
                        dayElem.addEventListener('click', () => {
                            window.dispatchEvent(new CustomEvent('invalid-date-clicked', {
                                detail: {
                                    date: dateOnly.toISOString().split('T')[0]
                                }
                            }));
                        });
                    }
                }


            })
        },
        destroy() {
            this.flatpickrInstance?.destroy();
        }
    }))
</script>
@endscript
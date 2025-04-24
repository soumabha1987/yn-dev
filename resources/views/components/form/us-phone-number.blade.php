@props(['name', 'label'])

<div x-data="phoneNumber">
    {{-- Note: Please avoid using `wire:model` on this component, as it will interfere with proper masking functionality. --}}
    <x-form.input-field
        type="text"
        :$label
        :$name
        class="w-full"
        x-model="formattedPhoneNumber"
        x-mask="(999) 999-9999"
        {{ $attributes }}
    />
</div>

@script
    <script>
        Alpine.data('phoneNumber', () => ({
            formattedPhoneNumber: '',
            init () {
                this.formattedPhoneNumber = this.$wire.{{ $name }}
                if (localStorage.getItem('component_name') === 'step-component') {
                    let phonenumber = localStorage.getItem('phone_number')
                    this.formattedPhoneNumber = phonenumber !== '' && phonenumber !== null
                        ? phonenumber
                        : this.formattedPhoneNumber
                }

                this.$watch('formattedPhoneNumber', () => {
                    this.$wire.{{ $name }} = this.formattedPhoneNumber.replace(/[()-\s]/g, '')
                    if (localStorage.getItem('component_name') === 'step-component') {
                        localStorage.setItem('phone_number', this.formattedPhoneNumber.replace(/[()-\s]/g, ''))
                    }
                })
            },
        }))
    </script>
@endscript

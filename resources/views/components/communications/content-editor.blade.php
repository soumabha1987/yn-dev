@props([
    'form',
    'field' => '',  
    'label' => '',
    'placeholder' => '',
])

<div class="mt-5">
    <div class="my-1.5 flex items-center justify-between">
        <h2 class="font-semibold tracking-wide text-black lg:text-md">
            {{ $label }}<span class="text-error text-base">*</span>
        </h2>
    </div>
    <div>
        <x-form.quill-editor
            class="h-48"
            :name="$form->$field"
            alpine-variable-name="{{ $field }}"
            form-input-name="form.{{ $field }}"
            :$placeholder
        />
        @error("form.$field")
            <div class="mt-2">
                <span class="text-error text-sm+">
                    {{ $message }}
                </span>
            </div>
        @enderror
    </div>
</div>

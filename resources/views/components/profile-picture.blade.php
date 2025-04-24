<div>
    <div class="flex space-x-4 items-center">
        @if ($image && in_array($image->getMimeType(), ['image/jpeg', 'image/png', 'image/jpg']))
            <img
                src="{{ $image->temporaryUrl() }}"
                class="rounded-full object-fit object-cover size-20"
                alt="{{ __('avatar') }}"
            >
        @elseif (auth()->user()->image)
            <img
                src="{{ Storage::url('profile-images/' . auth()->user()->image) }}"
                class="rounded-full object-fit object-cover size-20"
                alt="{{ __('avatar') }}"
            >
        @endif

        <div class="block">
            <x-form.input-file
                wire:model="image"
                name="image"
                accept="image/jpeg,image/png,image/jpg"
            />
            @if($image && !$errors->has('image') && ! in_array($image->getMimeType(), ['image/jpeg', 'image/png', 'image/jpg']))
                <div class="mt-1">
                    <span class="text-sm+ text-error">
                        {{ __('The uploaded file must be an image.') }}
                    </span>
                </div>
            @endif
        </div>
    </div>
</div>

@use('App\Enums\TemplateCustomField')

@props([
    'label' => '',
])

<div>
    <div class="mt-5">
        <div class="my-1.5 flex items-center justify-between">
            <h2 class="font-medium capitalize tracking-wide text-black lg:text-sm+">
                {{ __($label) }}
            </h2>
        </div>
    </div>
    <p>
        @foreach(TemplateCustomField::values() as $customField)
            <button
                type="button"
                {{ $attributes->merge(['class' => 'inline-block mb-1 mr-2' ]) }}
                class="inline-block mb-1 mr-2"
            >
                {{ $customField }}
            </button>
        @endforeach
    </p>
</div>

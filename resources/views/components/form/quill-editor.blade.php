@props([
    'name',
    'placeHolder' => __('Enter Your Content'),
    'formInputName',
])

<div
    x-data="{ quillEditor: null }"
    {{ $attributes }}
    x-init="
        quillEditor = new Quill($el, {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    [{ 'align': [] }],
                    ['link', 'image']
                ],
                imageResize: {
                    displaySize: true
                }
            },
            placeholder: '{{ $placeHolder }}'
        })
        quillEditor.on('text-change', () => $dispatch('quill-input'))
    "
    x-modelable="{{ $alpineVariableName }}"
    wire:model="{{ $formInputName }}"
    @quill-input="{{ $alpineVariableName }} = quillEditor.root.innerHTML"
>
    {!! $name !!}
</div>

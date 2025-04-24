@props(['name'])

<label
    x-data="{ textType: false }"
    class="relative flex"
>
    <input
        class="form-input text-black w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pr-9 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
        :type="textType ? 'text' : 'password'"
        {{ $attributes }}
        autocomplete="off"
    >
    <span
        @click="textType = !textType"
        class="cursor-pointer absolute right-0 flex h-full w-10 items-center justify-center peer-focus:text-primary"
    >
        <x-heroicon-o-eye x-show="textType" class="size-5.5" />
        <x-heroicon-o-eye-slash x-show="!textType" class="size-5.5" />
    </span>
</label>

@error($name)
    <div class="mt-2">
        <span class="text-error text-sm+">
            {{ $message }}
        </span>
    </div>
@enderror

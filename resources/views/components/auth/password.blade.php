@props([
    'name',
    'hasIcon' => true,
    'placeholder' => __('Password'),
])

<div
    x-data="passwordChecker"
    class="relative"
>
    <label class="relative flex mt-4 {{ $attributes->get('label-class') }}">
        <input
            x-bind:type="showPassword ? 'text' : 'password'"
            x-on:focus="passwordFocus = true"
            x-on:blur="passwordBlur"
            placeholder="{{ $placeholder }}"
            {{ $attributes->merge(['class' => 'form-input peer w-full rounded-lg']) }}
            autocomplete="off"
        >
        @if ($hasIcon)
            <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-600 peer-focus:text-primary">
                <x-heroicon-o-lock-closed class="w-5" />
            </span>
        @endif
        <span class="flex absolute right-0 h-full pr-3 text-slate-600 peer-focus:text-primary">
            <x-heroicon-o-eye-slash
                x-on:click="showPassword = !showPassword"
                x-show="!showPassword"
                class="w-5.5"
            />
            <x-heroicon-o-eye
                x-on:click="showPassword = !showPassword"
                x-show="showPassword"
                class="w-5.5"
            />
        </span>
    </label>
    <div
        x-show="passwordFocus && $wire.{{ $name  }}.length > 0"
        x-transition.opacity.duration.500ms
        class="absolute right-0 card bg-white p-4 z-20 mt-1"
    >
        <span class="font-bold text-black mb-1">
            {{ __('Create Password that:') }}
        </span>
        <ul class="text-md font-medium space-y-1">
            <li x-bind:class="isValidLength ? 'text-success font-semibold' : 'text-error'">
                <span class="flex items-center space-x-4">
                    <template x-if="isValidLength">
                        <x-lucide-check class="size-4" />
                    </template>
                    <template x-if="!isValidLength">
                        <x-lucide-x class="size-4" />
                    </template>
                    <span>{{ __('contains length is between 8 to 16 characters') }}</span>
                </span>
            </li>
            <li x-bind:class="hasUpperLetter ? 'text-success font-semibold' : 'text-error'">
                <span class="flex items-center space-x-4">
                    <template x-if="hasUpperLetter">
                        <x-lucide-check class="size-4" />
                    </template>
                    <template x-if="!hasUpperLetter">
                        <x-lucide-x class="size-4" />
                    </template>
                    <span>{{ __('contains at least one upper case character') }}</span>
                </span>
            </li>
            <li x-bind:class="hasLowerLetter ? 'text-success font-semibold' : 'text-error'">
                <span class="flex items-center space-x-4">
                    <template x-if="hasLowerLetter">
                        <x-lucide-check class="size-4" />
                    </template>
                    <template x-if="!hasLowerLetter">
                        <x-lucide-x class="size-4" />
                    </template>
                    <span>{{ __('contains at least one lower case character') }}</span>
                </span>
            </li>
            <li x-bind:class="hasSymbol ? 'text-success font-semibold' : 'text-error'">
                <span class="flex items-center space-x-4">
                    <template x-if="hasSymbol">
                        <x-lucide-check class="size-4" />
                    </template>
                    <template x-if="!hasSymbol">
                        <x-lucide-x class="size-4" />
                    </template>
                    <span>{{ __('contains at least one symbol') }}</span>
                </span>
            </li>
            <li x-bind:class="hasNumber ? 'text-success font-semibold' : 'text-error'">
                <span class="flex items-center space-x-4">
                    <template x-if="hasNumber">
                        <x-lucide-check class="size-4" />
                    </template>
                    <template x-if="!hasNumber">
                        <x-lucide-x class="size-4" />
                    </template>
                    <span>{{ __('contains at least one number (0-9)') }}</span>
                </span>
            </li>
        </ul>
    </div>
    <div class="mt-1">
        @error($name)
            <span class="text-sm+ text-error">
                {{ $message }}
            </span>
        @enderror
    </div>
</div>

@script
    <script>
        Alpine.data('passwordChecker', () => ({
            passwordFocus: false,
            showPassword : false,
            passwordBlur() {
                this.passwordFocus = false
            },
            get hasLowerLetter() {
                return /[a-z]/.test(this.$wire.{{ $name }})
            },
            get hasSymbol() {
                return /[!@#$%^&*(),.?":{}|<>]/.test(this.$wire.{{ $name }})
            },
            get hasUpperLetter() {
                return /[A-Z]/.test(this.$wire.{{ $name }})
            },
            get hasNumber() {
                return /\d/.test(this.$wire.{{ $name }})
            },
            get isValidLength() {
                return this.$wire.{{ $name }}?.length >= 8 && this.$wire.{{ $name }}?.length <= 16
            },
        }));
    </script>
@endscript

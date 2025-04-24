<div>
    <form
        method="POST"
        wire:submit="createOrUpdate"
        autocomplete="off"
    >
        <div
            x-data="embed_code"
            class="card"
        >
            <div class="p-4">
                <h2 class="text-md lg:text-lg font-semibold text-slate-700">
                    {{ __('Experiment with colors to make it fun for your consumers!') }}
                </h2>

                <div class="mt-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <div class="mt-4">
                                <x-form.input-field
                                    wire:model.live="form.primary_color"
                                    :label="__('Select Primary Color')"
                                    type="color"
                                    name="form.primary_color"
                                    class="w-full"
                                />
                            </div>

                            <div class="mt-4">
                                <x-form.input-field
                                    wire:model.live="form.secondary_color"
                                    :label="__('Select Secondary Color')"
                                    type="color"
                                    name="form.secondary_color"
                                    class="w-full"
                                />
                            </div>

                            <div class="mt-4">
                                <label class="block">
                                    <span class="text-black block tracking-wide font-semibold space-x-3">
                                        {{ __('Resize Logo ') }}
                                    </span>
                                    <span class="text-xs text-slate-600">
                                        {{ __('Note: Dynamically generates the embed code with the colors and size chosen') }}
                                    </span>
                                    <div class="flex">
                                        <input
                                            type="range"
                                            wire:model.live="form.size"
                                            name="form.size"
                                            min="160"
                                            max="520"
                                            step="10"
                                            class="form-range mt-1.5 text-info"
                                        />
                                        <span class="p-2 text-black bg-slate-150" x-text="$wire.form.size + 'px'"></span>
                                    </div>

                                </label>
                            </div>

                            <div class="flex flex-col mt-4">
                                <div
                                    x-on:click="isCollapsible = ! isCollapsible"
                                    class="flex cursor-pointer items-center justify-between py-4 text-md font-semibold text-black"
                                >
                                    <p>{{ __('Embed Code for Your Website') }}</p>
                                    <div
                                        x-bind:class="isCollapsible && '-rotate-180'"
                                        class="text-sm font-normal leading-none text-slate-400 transition-transform duration-300"
                                    >
                                        <x-heroicon-o-chevron-down class="size-5" />
                                    </div>
                                </div>

                                <hr>

                                <div
                                    x-collapse
                                    x-show="isCollapsible"
                                >
                                    <div class="w-full items-center mt-5">
                                        <div class="flex">
                                            <p class="form-input w-full h-20 border rounded-l-lg border-slate-300 overflow-y-auto is-scrollbar-hidden bg-transparent px-3 py-2 border-r-0 hover:z-10">
                                                {{ $data = '<a href="https://consumer.younegotiate.com" target="_blank">' . view('components.logo', [
                                                        'primary_color' => $form->primary_color,
                                                        'secondary_color' => $form->secondary_color,
                                                        'logo_range' => $form->size.'px'
                                                    ]) . '</a>'
                                                }}
                                            </p>
                                            <input
                                                x-ref="embedded_code_of_personalized_logo"
                                                type="hidden"
                                                value="{{ $data }}"
                                            >
                                            <button
                                                x-on:click="copyToClipboard"
                                                type="button"
                                                class="bg-primary text-white hover:bg-primary-focus px-2 py-1 border rounded-r-lg"
                                            >
                                                {{ __('Copy') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-center my-8">
                                <svg
                                    viewBox="-1135.6452 -3.6259 3009.5033 383.13"
                                    xmlns="http://www.w3.org/2000/svg"
                                    x-bind:width="$wire.form.size + 'px'"
                                >
                                    <script xmlns="" />
                                    <g transform="matrix(0.9999999999999999, 0, 0, 0.9999999999999999, -1578.105224609375, -858.02587890625)">
                                        <polygon
                                            points="661.4,872.46 589.55,984.08 516.22,872.46 442.46,872.46 556.6,1035.85 556.6,1154.58 619.31,1154.58 &#xA;&#9;&#9;619.31,1036.23 733.88,872.46 &#9;"
                                            :style="'fill:' + $wire.form.primary_color + '!important;'"
                                        />
                                        <path
                                            d="M918.81,975.71c-21.82-20.08-49.81-30.12-83.96-30.12c-22.11,0-42.12,4.43-60.05,13.28 c-17.93,8.85-31.78,21.68-41.56,38.49c-9.78,16.81-14.67,34.19-14.67,52.15c0,23.48,4.89,43.4,14.67,59.75 c9.78,16.36,24.05,28.77,42.83,37.24c18.78,8.47,38.51,12.7,59.2,12.7c33.44,0,61.18-10.17,83.22-30.5 c22.03-20.33,33.05-45.96,33.05-76.88C951.54,1021.16,940.63,995.79,918.81,975.71z M874.28,1098.97 c-10.56,10.78-23.63,16.17-39.22,16.17c-15.59,0-28.7-5.39-39.32-16.17c-10.63-10.78-15.94-26.3-15.94-46.57 c0-20.27,5.31-35.79,15.94-46.57c10.63-10.78,23.73-16.17,39.32-16.17c15.59,0,28.66,5.39,39.22,16.17 c10.55,10.78,15.84,26.17,15.84,46.19C890.11,1072.54,884.83,1088.19,874.28,1098.97z"
                                            :style="'fill:' + $wire.form.primary_color + '!important;'"
                                        />
                                        <polygon
                                            points="1454.04,1060.86 1326.5,872.46 1265.28,872.46 1265.28,1154.58 1323.74,1154.58 1323.74,970.61 1449.36,1154.58 &#xA;&#9;&#9;1512.49,1154.58 1512.49,872.46 1454.04,872.46 &#9;"
                                            :style="'fill:' + $wire.form.secondary_color + '!important;'"
                                        />
                                        <path
                                            d="M1664.69,945.59c-29.9,0-54.63,9.59-74.18,28.77c-19.56,19.18-29.33,45.71-29.33,79.58c0,28.36,7.44,51.83,22.32,70.43 c18.85,23.22,47.9,34.83,87.15,34.83c24.8,0,45.45-5.16,61.96-15.49c16.51-10.33,28.59-25.37,36.24-45.13l-59.52-9.04 c-3.26,10.27-8.08,17.7-14.45,22.32c-6.38,4.62-14.24,6.93-23.59,6.93c-13.75,0-25.23-4.46-34.44-13.37 c-9.21-8.92-14.03-21.39-14.45-37.43h149.64c0.85-41.44-8.43-72.2-27.84-92.28C1724.77,955.63,1698.27,945.59,1664.69,945.59z M1623.45,1034.88c-0.14-14.75,4.04-26.43,12.54-35.02c8.5-8.59,19.27-12.89,32.31-12.89c12.19,0,22.53,4.07,31.03,12.22 c8.5,8.15,12.97,20.05,13.39,35.7H1623.45z"
                                            :style="'fill:' + $wire.form.secondary_color + '!important;'"
                                        />
                                        <path
                                            d="M1971.63,978.88c-18.14-22.19-41.17-33.29-69.08-33.29c-27.07,0-49.71,9.05-67.91,27.13 c-18.21,18.09-27.31,44.26-27.31,78.52c0,27.33,6.94,50.17,20.83,68.51c17.71,23.22,41.73,34.83,72.06,34.83 c27.21,0,49.74-11.03,67.59-33.1v29.83c0,12.06-0.92,20.33-2.76,24.83c-2.69,6.28-6.66,10.84-11.9,13.66 c-7.8,4.23-19.49,6.35-35.07,6.35c-12.19,0-21.12-1.92-26.78-5.77c-4.11-2.69-6.73-7.64-7.87-14.82l-68.23-7.5 c-0.14,2.56-0.21,4.74-0.21,6.54c0,18.22,8,33.26,24.02,45.13c16.01,11.87,43.15,17.8,81.41,17.8c20.26,0,37.02-1.92,50.27-5.77 c13.25-3.85,23.84-9.17,31.78-15.97c7.93-6.8,14.1-16.17,18.49-28.1c4.39-11.93,6.59-29.96,6.59-54.08v-183.4h-55.9V978.88z M1953.77,1095.41c-10.06,10.33-22.39,15.49-36.99,15.49c-13.6,0-25.05-5.03-34.33-15.11c-9.28-10.07-13.92-25.95-13.92-47.63 c0-20.65,4.64-36.02,13.92-46.09c9.28-10.07,21.08-15.11,35.39-15.11c14.74,0,26.92,5.13,36.56,15.4 c9.63,10.27,14.45,25.98,14.45,47.15C1968.86,1069.78,1963.83,1085.08,1953.77,1095.41z"
                                            :style="'fill:' + $wire.form.secondary_color + '!important;'"
                                        />
                                        <path
                                            d="M2431.82,1114.36c-3.97,0-7.33-0.9-10.1-2.69c-2.76-1.8-4.54-4.07-5.31-6.83c-0.78-2.76-1.17-12.48-1.17-29.15v-82.37 h40.81v-43.11h-40.81v-72.17h-59.94v72.17h-27.42v43.11h27.42v89.1c0,19.12,0.64,31.82,1.91,38.1c1.56,8.85,4.36,15.88,8.4,21.07 c4.04,5.2,10.38,9.43,19.02,12.7c8.64,3.27,18.35,4.91,29.12,4.91c17.57,0,33.3-2.69,47.19-8.08l-5.1-41.95 C2445.35,1112.63,2437.35,1114.36,2431.82,1114.36z"
                                            :style="'fill:' + $wire.form.secondary_color + '!important;'"
                                        />
                                        <rect x="2497.51" y="872.46" width="59.73" height="50.04" :style="'fill:' + $wire.form.secondary_color + '!important;'" />
                                        <rect x="2497.51" y="950.21" width="59.73" height="204.38" :style="'fill:' + $wire.form.secondary_color + '!important;'" />
                                        <path
                                            d="M2801.04,1087.42l0.64-63.12c0-23.48-2.66-39.61-7.97-48.4c-5.31-8.79-14.49-16.04-27.53-21.75 c-13.04-5.71-32.88-8.56-59.52-8.56c-29.33,0-51.44,4.75-66.32,14.24c-14.88,9.5-25.37,24.12-31.46,43.88l54.2,8.85 c3.68-9.49,8.5-16.13,14.45-19.92c5.95-3.78,14.24-5.68,24.87-5.68c15.73,0,26.43,2.21,32.1,6.64c5.67,4.43,8.5,11.84,8.5,22.23 v5.39c-10.77,4.11-30.11,8.53-58.03,13.28c-20.69,3.59-36.53,7.79-47.51,12.6c-10.98,4.81-19.52,11.74-25.61,20.78 c-6.1,9.05-9.14,19.34-9.14,30.89c0,17.45,6.7,31.88,20.09,43.3c13.39,11.42,31.7,17.13,54.95,17.13c13.18,0,25.58-2.25,37.2-6.74 c11.62-4.49,22.53-11.22,32.73-20.21c0.42,1.03,1.13,3.15,2.13,6.35c2.26,7.06,4.18,12.38,5.74,15.97h59.09 c-5.24-9.75-8.82-18.89-10.73-27.42C2802,1118.63,2801.04,1105.38,2801.04,1087.42z M2743.01,1067.21 c0,12.96-0.78,21.75-2.34,26.37c-2.27,7.06-7.01,13.02-14.24,17.9c-9.78,6.42-20.05,9.62-30.82,9.62c-9.64,0-17.57-2.76-23.81-8.28 c-6.24-5.51-9.35-12.06-9.35-19.63c0-7.7,3.9-14.05,11.69-19.05c5.1-3.08,15.94-6.22,32.52-9.43c16.58-3.21,28.69-5.97,36.35-8.28 V1067.21z"
                                            :style="'fill:' + $wire.form.secondary_color + '!important;'"
                                        />
                                        <path
                                            d="M2939.85,1114.36c-3.97,0-7.33-0.9-10.1-2.69c-2.76-1.8-4.54-4.07-5.31-6.83c-0.78-2.76-1.17-12.48-1.17-29.15v-82.37 h40.81v-43.11h-40.81v-72.17h-59.94v72.17h-27.42v43.11h27.42v89.1c0,19.12,0.64,31.82,1.91,38.1c1.56,8.85,4.36,15.88,8.4,21.07 c4.04,5.2,10.38,9.43,19.02,12.7c8.64,3.27,18.35,4.91,29.12,4.91c17.57,0,33.3-2.69,47.19-8.08l-5.1-41.95 C2953.38,1112.63,2945.37,1114.36,2939.85,1114.36z"
                                            :style="'fill:' + $wire.form.secondary_color + '!important;'"
                                        />
                                        <path
                                            d="M3171.11,975.71c-19.42-20.08-45.91-30.12-79.5-30.12c-29.9,0-54.63,9.59-74.18,28.77 c-19.56,19.18-29.33,45.71-29.33,79.58c0,28.36,7.44,51.83,22.32,70.43c18.84,23.22,47.9,34.83,87.15,34.83 c24.8,0,45.45-5.16,61.96-15.49c16.51-10.33,28.59-25.37,36.24-45.13l-59.52-9.04c-3.26,10.27-8.08,17.7-14.46,22.32 c-6.38,4.62-14.24,6.93-23.59,6.93c-13.75,0-25.23-4.46-34.44-13.37c-9.21-8.92-14.03-21.39-14.45-37.43h149.64 C3199.81,1026.55,3190.53,995.79,3171.11,975.71z M3050.38,1034.88c-0.14-14.75,4.04-26.43,12.54-35.02 c8.5-8.59,19.27-12.89,32.31-12.89c12.19,0,22.53,4.07,31.03,12.22c8.5,8.15,12.97,20.05,13.39,35.7H3050.38z"
                                            :style="'fill:' + $wire.form.secondary_color + '!important;'"
                                        />
                                        <path
                                            d="M1179.56,1093.99V944.43h-59.73v86.22c0,29.25-1.49,47.63-4.46,55.13c-2.98,7.51-8.5,13.79-16.58,18.86 c-8.08,5.07-17.22,7.6-27.42,7.6c-8.93,0-16.3-1.89-22.11-5.68c-5.81-3.78-9.81-8.92-12.01-15.4c-2.2-6.48-3.29-24.09-3.29-52.83 v-93.91h-59.73v129.32c0,19.24,2.69,34.32,8.08,45.23c5.38,10.91,14.1,19.37,26.14,25.4c12.04,6.03,25.65,9.05,40.81,9.05 c14.88,0,29.02-3.15,42.41-9.43c13.39-6.28,24.2-14.88,32.42-25.79c0,16.9,13.7,30.6,30.6,30.6h12.98h11.9h22.56V1119 c0,0-4.07-0.99-9.76-3.87C1184.44,1111.12,1179.56,1102.87,1179.56,1093.99z"
                                            :style="'fill:' + $wire.form.primary_color + '!important;'"
                                        />
                                        <path
                                            d="M2279.02,999.79l-14.37,10.27c-6.81,4.87-9.84,13.48-7.56,21.54c2.09,7.39,3.06,15.14,2.83,23.03v0 c-0.66,22.46-10.94,43.15-28.73,57.39c-12.3,9.84-27.5,15.62-43.23,16.44c-22.75,1.18-44.18-7.46-59.6-23.81 c-13.96-14.8-21.31-34.16-20.71-54.49c0.97-32.78,22.62-60.18,52.05-70.02c9.43-3.15,15.73-12.04,15.13-21.97l-0.98-16.31 c-0.58-9.64-9.69-16.51-19.1-14.32c-55.3,12.87-97.38,61.71-99.13,121.08c-1.01,34.23,11.37,66.81,34.87,91.72 c23.49,24.92,55.28,39.2,89.51,40.2c1.29,0.04,2.58,0.06,3.87,0.06c34.12,0,66.36-13.33,90.7-37.69 c22.12-22.15,35.42-51.87,37.2-83.12c1.04-18.22-1.73-36.02-7.95-52.53C2300.05,997.23,2287.74,993.56,2279.02,999.79z"
                                            :style="'fill:' + $wire.form.primary_color + '!important;'"
                                        />
                                        <circle cx="2232.99" cy="960.38" r="36.99" :style="'fill:' + $wire.form.primary_color + '!important;'" />
                                    </g>
                                    <g
                                        transform="matrix(1.0358690023422241, 0, 0, 1.0989489555358887, -1694.444580078125, -946.2471923828125)"
                                        :style="'fill:' + $wire.form.secondary_color + '!important;'"
                                    >
                                        <text style="font-family: Arial, sans-serif;font-size: 30.4px;font-weight: 700;white-space: pre;" transform="matrix(2.950635, 0, 0, 2.884712, -6381.762207, -1750.011963)" x="3260.85" y="938.973">R</text>
                                        <text style="font-family: Arial, sans-serif; font-size: 22.4px; white-space: pre;"
                                              x="3438.51" y="1007.84"> </text>
                                    </g>
                                    <g
                                        transform="matrix(0.9999999999999999, 0, 0, 0.9999999999999999, -1578.105224609375, -858.02587890625)"
                                        :style="'fill:' + $wire.form.secondary_color + '!important;'"
                                    >
                                        <path d="M3272.81,1003.26c-41.04,0-74.43-33.39-74.43-74.43c0-41.04,33.39-74.43,74.43-74.43c41.04,0,74.43,33.39,74.43,74.43 C3347.24,969.87,3313.85,1003.26,3272.81,1003.26z M3272.81,867.83c-33.64,0-61,27.37-61,61c0,33.64,27.37,61,61,61 c33.64,0,61-27.37,61-61C3333.81,895.19,3306.45,867.83,3272.81,867.83z" />
                                    </g>
                                </svg>
                            </div>

                            <div class="flex flex-col items-center">
                                <label class="text-black font-semibold">
                                    <p>{{ __('YouNegotiate Consumer QR Code') }}</p>
                                    <span class="text-sm">
                                        {{ __('(social media profiles, consumer communications, other)') }}
                                    </span>
                                </label>
                                <div class="mt-5">
                                    <a
                                        href="https://consumer.younegotiate.com"
                                        target="_blank"
                                    >
                                        <img src="{{ asset('images/yn-qr-code.png') }}" class="size-64">
                                    </a>

                                    <div class="flex items-center justify-between mt-2 px-2">
                                        <button
                                            type="button"
                                            class="text-primary hover:text-primary-focus hover:underline text-center cursor-pointer"
                                            x-on:click="copyUrl"
                                        >
                                            {{ __('Copy this Link') }}
                                        </button>
                                        <span class="text-gray-400 text-sm">|</span>
                                        <button
                                            type="button"
                                            class="text-primary hover:text-primary-focus hover:underline text-center cursor-pointer"
                                            x-on:click="downloadQrCode"
                                        >
                                            {{ __('Download') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex mt-8 mb-4 sm:ml-4 justify-center sm:justify-start items-center space-x-2">
                    <x-form.button
                        type="button"
                        variant="info"
                        wire:click="resetAndSave"
                        wire:target="resetAndSave"
                        wire:loading.attr="disabled"
                        class="disabled:opacity-50"
                    >
                        <x-lucide-loader-2
                            wire:loading
                            wire:target="resetAndSave"
                            class="size-4 animate-spin mr-2"
                        />
                        {{ __('Reset to YN Brand Logo') }}
                    </x-form.button>

                    <x-form.button
                        type="submit"
                        variant="primary"
                        wire:target="createOrUpdate"
                        wire:loading.attr="disabled"
                        class="disabled:opacity-50"
                    >
                        <x-lucide-loader-2
                            wire:loading
                            wire:target="createOrUpdate"
                            class="size-4 animate-spin mr-2"
                        />
                        {{ __('Save') }}
                    </x-form.button>
                </div>
            </div>
        </div>
    </form>

    @script
        <script>
            Alpine.data('embed_code', () => ({
                url: 'https://consumer.younegotiate.com',
                isCollapsible: true,
                async downloadQrCode() {
                    try {
                        const response = await fetch('images/yn-qr-code.png')
                        if (!response.ok) this.$notification({ text: @js(__('QR code download failed. Try again and contact help@younegotiate.com for help.')), variant: 'error' })
                        const blob = await response.blob()
                        const link = document.createElement('a')
                        link.href = URL.createObjectURL(blob)
                        link.download = 'yn-qr-code.jpg'
                        document.body.appendChild(link)
                        link.click()
                        document.body.removeChild(link)
                    } catch (error) {
                        this.$notification({ text: @js(__('QR code download failed. Try again and contact help@younegotiate.com for help.')), variant: 'error' })
                    }
                },
                copyToClipboard() {
                    navigator.clipboard
                        .writeText(this.$refs.embedded_code_of_personalized_logo.value)
                        .then(() => {
                            this.$notification({ text: @js(__('Embed code copied!')) })
                        })
                },
                copyUrl() {
                    navigator.clipboard
                        .writeText(this.url)
                        .then(() => {
                            this.$notification({ text: @js(__('URL copied!')) })
                        })
                }
            }))
        </script>
    @endscript
</div>

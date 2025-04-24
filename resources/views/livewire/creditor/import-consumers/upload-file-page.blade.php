<div>
    <div @class([
        'card mb-8',
        'sm:w-1/2' => $csvHeaders->isEmpty(),
    ])>
        <div @class([
            'grid grid-cols-1 gap-5 px-5',
            'sm:grid-cols-1' => $csvHeaders->isEmpty(),
            'sm:grid-cols-2' => $csvHeaders->isNotEmpty(),
        ])>
            <div class="py-6">
                <h3 class="text-sm font-medium text-slate-800">
                    <span>{{ __('Easily map and save your header fields into YouNegotiate to save time importing accounts.') }}</span>
                </h3>

                <p class="mt-5">
                    <x-dialog>
                        <span
                            x-on:click="dialogOpen = true"
                            class="mt-2 text-primary cursor-pointer hover:underline text-base font-semibold"
                        >
                            {{ __('How it Works') }}
                        </span>

                        <x-dialog.panel
                            :heading="__('How to Create Your Header Profile')"
                            size="2xl"
                        >
                            <ul class="text-black space-y-3 py-4">
                                <li class="justify-between space-x-1">
                                    <span class="font-bold text-nowrap">{{ __('Step 1:') }}</span>
                                    <span>{{ __('Download and familiarize yourself with the YouNegotiate data fields (On this page).') }}</span>
                                </li>
                                <li class="flex justify-between space-x-1">
                                    <span class="font-bold text-nowrap">{{ __('Step 2:') }}</span>
                                    <span>{{ __('Run a list of your delinquent accounts, confirm minimum required YN fields are present, save as a CSV file.') }}</span>
                                </li>
                                <li>
                                    <span class="font-bold">{{ __('Step 3:') }}</span>
                                    <span>{{ __('Create your Header Profile (name, upload, set date, map fields, save).') }}</span>
                                </li>
                            </ul>
                        </x-dialog.panel>
                    </x-dialog>
                    <span class="text-black">{{ __('Click here for a detailed explanation of how the mapping process works.') }}</span>
                </p>

                <p class="text-black mt-5">
                    <a
                        href="{{ asset('yn-header-guidelines.xlsx') }}"
                        class="mt-2 text-primary hover:cursor-pointer hover:underline text-base font-semibold"
                        download
                    >
                        <span>{{ __('YouNegotiate Header Field Guidelines') }}</span>
                    </a>
                    <br />
                    <span>{{ __('Get to know the header fields to maximize your experience') }}</span>
                </p>

                <form method="POST" wire:submit="createHeader" autocomplete="off">
                    <div class="mt-5">
                        <x-form.input-field
                            type="text"
                            wire:model="form.header_name"
                            :label="__('Header Profile Name')"
                            :placeholder="__('Enter up to 160 characters')"
                            name="form.header_name"
                            class="w-full"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-5">
                        <div>
                            <div
                                x-data="{ fileName: '' }"
                                class="flex flex-col space-x-4 items-center"
                                x-on:livewire-upload-start="$wire.resetHeaderFileValidation()"
                            >
                                <label class="btn relative bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 w-full">
                                    <input
                                        wire:model="form.header_file"
                                        type="file"
                                        class="pointer-events-none absolute inset-0 size-full opacity-0"
                                        @change="fileName = $event.target.files.length ? $event.target.files[0].name : 'Choose File'"
                                        name="form.header_file"
                                    />

                                    <div class="flex items-center space-x-2">
                                        <x-lucide-loader-2
                                            class="size-5 animate-spin"
                                            wire:loading
                                            wire:target="form.header_file"
                                        />
                                        <span
                                            wire:loading
                                            wire:target="form.header_file"
                                        >
                                        {{ __('Uploading') }}
                                    </span>
                                        <x-lucide-upload
                                            class="size-5"
                                            wire:loading.remove
                                            wire:target="form.header_file"
                                        />
                                        <span
                                            wire:loading.remove
                                            wire:target="form.header_file"
                                        >
                                            {{ __('Choose File') }}
                                        </span>
                                    </div>
                                </label>
                                <p x-text="fileName ? fileName : ''"></p>
                            </div>

                            @error('form.header_file')
                            <div class="mt-2">
                                <span class="text-error text-sm+">
                                    {!! $message !!}
                                </span>
                            </div>
                            @enderror
                        </div>

                        <div>
                            <x-form.button
                                type="submit"
                                variant="primary"
                                wire:loading.class="opacity-50"
                                wire:loading.attr="disabled"
                                wire:target="form.header_file"
                                class="w-full text-nowrap"
                            >
                                {{ __('Proceed to Mapping') }}
                            </x-form.button>
                        </div>
                    </div>
                </form>
            </div>

            @if ($csvHeaders->isNotEmpty())
                <div class="py-6">
                    <h3 class="text-base font-semibold text-slate-800">
                        {{ __('Current Header Profile(s)') }}
                        @if($selectedHeader && ! $selectedHeader->is_mapped)
                            <span class="badge bg-error text-white text-sm+">
                                {{ __('Incomplete') }}
                            </span>
                        @endif
                    </h3>
                    <div class="flex flex-wrap lg:flex-nowrap gap-2 items-center mt-5">
                        <div class="w-full mb-1">
                            <x-form.select
                                wire:model.live="selectedHeaderId"
                                name="selectedHeaderId"
                                :options="$csvHeaders"
                                placeholder="Header"
                            />
                        </div>
                        @if ($selectedHeader)
                            <a
                                wire:navigate
                                href="{{ route('creditor.import-consumers.upload-file.map', $selectedHeader->id) }}"
                                class="btn text-xs sm:text-sm select-none text-white bg-info hover:bg-info-focus focus:bg-info-focus active:bg-info-focus/90"
                            >
                                <div class="flex items-center space-x-2">
                                    <x-heroicon-o-pencil-square class="size-4.5 sm:size-5" />
                                    <span>{{ __('Edit') }}</span>
                                </div>
                            </a>

                            <x-form.button
                                type="button"
                                variant="primary"
                                class="text-xs sm:text-sm disabled:opacity-50"
                                wire:click="downloadUploadedFile({{ $selectedHeader->id }})"
                                wire:target="downloadUploadedFile({{ $selectedHeader->id }})"
                                wire:loading.attr="disabled"
                            >
                                <div
                                    wire:loading.flex
                                    wire:target="downloadUploadedFile({{ $selectedHeader->id }})"
                                    class="flex items-center space-x-2"
                                >
                                    <x-lucide-loader class="size-4.5 sm:size-5 animate-spin" />
                                    <span class="whitespace-nowrap">{{ __('Downloading') }}</span>
                                </div>
                                <div
                                    wire:loading.remove
                                    wire:target="downloadUploadedFile({{ $selectedHeader->id }})"
                                    class="flex items-center space-x-2"
                                >
                                    <x-heroicon-o-arrow-down-tray class="size-4.5 sm:size-5" />
                                    <span class="whitespace-nowrap">{{ __('Download') }}</span>
                                </div>
                            </x-form.button>

                            <x-confirm-box
                                :message="__('Are you sure you want to delete this header?')"
                                :ok-button-label="__('Delete')"
                                action="deleteSelectedHeader({{ $selectedHeader->id }})"
                            >
                                <x-form.button
                                    type="button"
                                    variant="error"
                                    class="text-xs sm:text-sm"
                                >
                                    <div class="flex space-x-2 items-center">
                                        <x-heroicon-o-trash class="size-4.5 sm:size-5"/>
                                        <span>{{ __('Delete') }}</span>
                                    </div>
                                </x-form.button>
                            </x-confirm-box>
                        @endif
                    </div>
                    @if ($selectedHeader)
                        <div>
                            <x-table
                                :disable-loader="true"
                                class="text-sm+ mt-5"
                            >
                                <x-slot name="tableHead" class="border-x">
                                    <x-table.tr>
                                        <x-table.th>
                                            {{ __('Mapping with Fields') }}
                                        </x-table.th>
                                        <x-table.th>
                                            {{ __('Your Uploaded File Headers') }}
                                        </x-table.th>
                                    </x-table.tr>
                                </x-slot>
                                <x-slot name="tableBody" class="border-x border-b">
                                    @forelse ($selectedHeader->mapped_headers as $uploadedFileHeder => $mappingWithFields)
                                        <x-table.tr>
                                            <x-table.td>{{ $uploadedFileHeder }}</x-table.td>
                                            <x-table.td>{{ $mappingWithFields }}</x-table.td>
                                        </x-table.tr>
                                    @empty
                                        <x-table.no-items-found colspan="2" />
                                    @endforelse
                                </x-slot>
                            </x-table>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <livewire:creditor.sftp-connection.attach-header-profile />
</div>

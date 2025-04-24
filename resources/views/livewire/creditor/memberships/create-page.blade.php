@use('App\Enums\MembershipFrequency')
@use('App\Enums\MembershipFeatures')

<div>
    <div class="card px-4 py-4 sm:px-5">
        <form wire:submit="create" method="POST" autocomplete="off">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <x-form.input-field
                        type="text"
                        wire:model="form.name"
                        :label="__('Name')"
                        name="form.name"
                        class="w-full"
                        :placeholder="__('Enter Name')"
                        maxlength="40"
                        required
                    />
                </div>
                <div>
                    <x-form.input-group
                        :label="__('Licensing Fee')"
                        type="text"
                        name="form.price"
                        :placeholder="__('Licensing Fee')"
                        wire:model="form.price"
                        icon="$"
                        required
                    />
                </div>
                <div>
                    <x-form.input-group
                        :label="__('Percentage of Payments')"
                        type="text"
                        name="form.fee"
                        :placeholder="__('Percentage of Payments(Fees on all consumer payments)')"
                        wire:model="form.fee"
                        icon="%"
                        icon-position="right"
                        required
                    />
                </div>
                <div x-data="accountLimit">
                    <x-form.input-field
                        :label="__('Upload Accounts Limit')"
                        type="text"
                        x-model="uploadAccountLimit"
                        x-on:input="formatWithCommas"
                        name="form.upload_accounts_limit"
                        class="w-full"
                        :placeholder="__('Upload Accounts Limit')"
                        min="1"
                        required
                    />
                </div>

                <div>
                    <x-form.select
                        :label="__('Frequency')"
                        :placeholder="__('Frequency')"
                        :options="MembershipFrequency::displaySelectionBox()"
                        name="form.frequency"
                        wire:model="form.frequency"
                        required
                    />
                </div>

                <div>
                    <x-form.input-group
                        :label="__('E-Letter Fee')"
                        type="text"
                        name="form.e_letter_fee"
                        :placeholder="__('E-Letter Fee')"
                        wire:model="form.e_letter_fee"
                        icon="$"
                        required
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-subgrid gap-4 sm:col-span-3">
                    <div>
                        <x-form.text-area
                            :label="__('Description')"
                            wire:model="form.description"
                            name="form.description"
                            class="w-full"
                            rows="5"
                            required
                            placeholder="Enter Description"
                        />
                    </div>
                    <div class="col-span-2">
                        <span class="font-medium tracking-wide text-black lg:text-sm+">
                            {{__('Features')}}
                        </span>
                        @error('form.features')
                            <div class="mt-2">
                                <span class="text-error text-sm+">
                                    {{ $message }}
                                </span>
                            </div>
                        @enderror
                        <div class="grid grid-cols-1 sm:grid-cols-2">
                            @foreach(MembershipFeatures::displayFeatures() as $key => $value)
                                <div class="m-2 text-black font-medium">
                                    <x-form.switch
                                        wire:model="form.features"
                                        name="form.features.{{ $key }}"
                                        value="{{ $key }}"
                                        label="{{ $value }}"
                                    />
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="my-2 flex justify-center sm:justify-end space-x-2 mt-9">
                <a
                    wire:navigate
                    href="{{ route('super-admin.memberships') }}"
                    class="btn border focus:border-slate-400 bg-slate-150 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80"
                >
                    {{ __('Cancel') }}
                </a>
                <x-form.button
                    type="submit"
                    variant="primary"
                    wire:target="create"
                    wire:loading.attr="disabled"
                    class="border focus:border-primary-focus disabled:opacity-50"
                >
                    <x-lucide-loader-2
                        wire:target="create"
                        wire:loading
                        class="animate-spin size-5 mr-2"
                    />
                    {{ __('Create') }}
                </x-form.button>
            </div>
        </form>
    </div>
    @script
        <script>
            Alpine.data('accountLimit', () => ({
                uploadAccountLimit: '',
                formatWithCommas() {
                    let uploadAccountLimit = this.$el.value.replace(/[^0-9]/g, '')
                    this.uploadAccountLimit = uploadAccountLimit.replace(/\B(?=(\d{3})+(?!\d))/g, ',')
                    this.$wire.form.upload_accounts_limit = uploadAccountLimit
                }
            }))
        </script>
    @endscript
</div>

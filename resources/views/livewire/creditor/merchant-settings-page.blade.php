@use('App\Enums\MerchantName')
@use('App\Enums\BankAccountType')
@use('App\Enums\CompanyStatus')
@use('App\Enums\MerchantType')
@use('App\Enums\YearlyVolumeRange')
@use('App\Enums\State')
@use('App\Enums\CompanyCategory')
@use('Illuminate\Support\Carbon')
@use('Illuminate\Support\Number')
@use('App\Enums\IndustryType')
@use('App\Enums\CompanyBusinessCategory')

<div>
    <div
        x-data="{ merchantName: $wire.merchant_name }"
        class="flex flex-col"
    >
        <x-loader wire:loading wire:target="updateMerchantSettings" />
        <div class="card p-2 tab-content">
            <div class="is-scrollbar-hidden my-3 sm:max-w-5xl mx-auto overflow-x-auto rounded-lg bg-slate-200 text-slate-600">
                @if ($isNotEditable)
                    <div class="m-2 bg-white p-2">
                        @if ($merchant_name === MerchantName::AUTHORIZE->value)
                            <img
                                src="{{ asset('images/authorize.png') }}"
                                x-bind:alt="merchantName"
                                class="aspect-3/2 w-[200px] h-[50px] object-contain"
                            >
                        @endif
                        @if ($merchant_name === MerchantName::STRIPE->value)
                            <img
                                src="{{ asset('images/stripe.png') }}"
                                x-bind:alt="merchantName"
                                class="aspect-3/2 w-[200px] h-[50px] object-contain"
                            >
                        @endif
                        @if ($merchant_name === MerchantName::USA_EPAY->value)
                            <img
                                src="{{ asset('images/usaepay.png') }}"
                                x-bind:alt="merchantName"
                                class="aspect-3/2 w-[200px] h-[50px] object-contain"
                            >
                        @endif
                        @if ($merchant_name === MerchantName::YOU_NEGOTIATE->value)
                            <livewire:creditor.logo />
                        @endif
                    </div>
                @else
                    <div class="flex space-x-2 px-1.5 py-1">
                        <button
                            x-on:click="() => {
                                merchantName = @js(MerchantName::AUTHORIZE->value);
                                $wire.merchant_name = merchantName
                            }"
                            class="btn shrink-0 px-1 py-1.5 font-medium select-none"
                            x-bind:class="merchantName === @js(MerchantName::AUTHORIZE->value) ? 'bg-white shadow' : 'hover:bg-slate-100'"
                        >
                            <img
                                src="{{ asset('images/authorize.png') }}"
                                x-bind:alt="merchantName"
                                class="aspect-3/2 w-[200px] h-[50px] object-contain"
                            >
                        </button>
                        <button
                            x-on:click="() => {
                                merchantName = @js(MerchantName::STRIPE->value);
                                $wire.merchant_name = merchantName
                            }"
                            class="btn shrink-0 px-3 py-1.5 font-medium select-none"
                            x-bind:class="merchantName === @js(MerchantName::STRIPE->value) ? 'bg-white shadow' : 'hover:bg-slate-100'"
                        >
                            <img
                                src="{{ asset('images/stripe.png') }}"
                                x-bind:alt="merchantName"
                                class="aspect-3/2 w-[200px] h-[50px] object-contain"
                            >
                        </button>
                        <button
                            x-on:click="() => {
                                merchantName = @js(MerchantName::USA_EPAY->value);
                                $wire.merchant_name = merchantName
                            }"
                            class="btn shrink-0 px-3 py-1.5 font-medium select-none"
                            x-bind:class="merchantName === @js(MerchantName::USA_EPAY->value) ? 'bg-white shadow' : 'hover:bg-slate-100'"
                        >
                            <img
                                src="{{ asset('images/usaepay.png') }}"
                                x-bind:alt="merchantName"
                                class="aspect-3/2 w-[200px] h-[50px] object-contain"
                            >
                        </button>

                        @if (! in_array($company->business_category, CompanyBusinessCategory::notAllowedYouNegotiateMerchant()))
                            <button
                                x-on:click="() => {
                                    merchantName = @js(MerchantName::YOU_NEGOTIATE->value);
                                    $wire.merchant_name = merchantName
                                }"
                                class="btn shrink-0 px-3 py-1.5 font-medium select-none"
                                x-bind:class="merchantName === @js(MerchantName::YOU_NEGOTIATE->value) ? 'bg-white shadow' : 'hover:bg-slate-100'"
                            >
                                <livewire:creditor.logo />
                            </button>
                        @endif
                    </div>
                @endif
            </div>

            @error('merchant_name')
                <div class="text-error mx-auto mt-1">{{ $message }}</div>
            @enderror

            <div class="py-2 px-3 text-end">
                <a
                    class="text-primary underline text-base"
                    href="mailto:help@younegotiate.com?subject=New Merchant Processor Request [{{ $company->id }}]"
                    target="_blank"
                >
                    {{ __('Request New Merchant Processor') }}
                </a>
            </div>

            @if ($isNotEditable)
                <div class="mx-3 alert flex space-x-2 rounded-lg border bg-warning/10 border-warning p-3 text-warning mb-5">
                    <x-heroicon-o-no-symbol class="size-5" />
                    <span class="font-medium">
                        {{ __('Current payment plans set up on your current merchant processor. Use "Request New Merchant" link above to change.') }}
                    </span>
                </div>
            @endif

            @if (! in_array($company->business_category, CompanyBusinessCategory::notAllowedYouNegotiateMerchant()))
                <div
                    x-show="merchantName === @js(MerchantName::YOU_NEGOTIATE->value)"
                    x-transition:enter="transition-all duration-500 easy-in-out"
                    x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]"
                    x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]"
                >
                    @if ($company->status === CompanyStatus::SUBMITTED && $company->merchant->merchant_name === MerchantName::YOU_NEGOTIATE)
                        <div class="mx-3 alert flex space-x-2 rounded-lg border bg-warning/10 border-warning p-3 text-warning mb-5">
                            <x-heroicon-o-exclamation-triangle class="size-5" />
                            <span class="font-medium">
                                {{ __('Merchant Application Submitted, Some details are locked for verification. You can still edit other fields.') }}
                            </span>
                        </div>
                    @endif
                    <div class="mx-3">
                        @if (in_array($company->status, [CompanyStatus::SUBMITTED, CompanyStatus::ACTIVE]) && $company->merchant->merchant_name === MerchantName::YOU_NEGOTIATE)
                            <div class="opacity-65">
                                <h2 class="text-lg font-medium tracking-wide text-slate-700">
                                    {{ __('Company Information') }}
                                </h2>
                                <hr class="mt-2 h-px bg-slate-200">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-5 mb-3">
                                    <div class="my-2">
                                        <label class="block">
                                            <span class="font-medium tracking-wide text-slate-700 line-clamp-1 lg:text-base">
                                                {{ __('Legal Name') }}
                                            </span>
                                        </label>
                                        <div class="mt-2 rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400">
                                            {{ $tilledForm->legal_name ?? 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="my-2">
                                        <label class="block">
                                            <span class="font-medium tracking-wide text-slate-700 line-clamp-1 lg:text-base">
                                                {{ __('Business Type') }}
                                            </span>
                                        </label>
                                        <div class="mt-2 rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400">
                                            {{ IndustryType::tryFrom($tilledForm->industry_type)->displayName() ?? 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="my-2">
                                        <label class="block">
                                            <span class="font-medium tracking-wide text-slate-700 line-clamp-1 lg:text-base">
                                                {{ __('Company Category') }}
                                            </span>
                                        </label>
                                        <div class="mt-2 rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400">
                                            {{ CompanyCategory::tryFrom($tilledForm->company_category)->displayName() ?? 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="my-2">
                                        <label class="block">
                                            <span class="font-medium tracking-wide text-slate-700 line-clamp-1 lg:text-base">
                                                {{ __('Average Transaction Amount') }}
                                            </span>
                                        </label>
                                        <div class="mt-2 rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400">
                                            {{ $tilledForm->average_transaction_amount ?? 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="my-2">
                                        <label class="block">
                                            <span class="font-medium tracking-wide text-slate-700 line-clamp-1 lg:text-base">
                                                {{ __('Statement Descriptor') }}
                                            </span>
                                        </label>
                                        <div class="mt-2 rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400">
                                            {{ $tilledForm->statement_descriptor ?? 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="my-2">
                                        <label class="block">
                                            <span class="font-medium tracking-wide text-slate-700 line-clamp-1 lg:text-base">
                                                {{ __('Tax Identification Number') }}
                                            </span>
                                        </label>
                                        <div class="mt-2 rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400">
                                            {{ filled($tilledForm->fed_tax_id) ? $tilledForm->fed_tax_id : 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="my-2">
                                        <label class="block">
                                            <span class="font-medium tracking-wide text-slate-700 line-clamp-1 lg:text-base">
                                                {{ __('Yearly Volume Range') }}
                                            </span>
                                        </label>
                                        <div class="mt-2 rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400">
                                            {{ filled($yearlyRange = ($range = YearlyVolumeRange::tryFrom($tilledForm->yearly_volume_range))?->displayName() . ' ' . $range?->range()) ? $yearlyRange : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <h2 class="text-lg font-medium text-slate-700">
                                    {{ __('Business Owner Information') }}
                                </h2>
                                <hr class="mt-2 h-px bg-slate-200">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-5 mb-3">
                                    <div class="my-2">
                                        <label class="block">
                                            <span class="font-medium tracking-wide text-slate-700 line-clamp-1 lg:text-base">
                                                {{ __('First Name') }}
                                            </span>
                                        </label>
                                        <div class="mt-2 rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400">
                                            {{ $tilledForm->first_name ?? 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="my-2">
                                        <label class="block">
                                            <span class="font-medium tracking-wide text-slate-700 line-clamp-1 lg:text-base">
                                                {{ __('Last Name') }}
                                            </span>
                                        </label>
                                        <div class="mt-2 rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400">
                                            {{ filled($tilledForm->last_name) ? $tilledForm->last_name : 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="my-2">
                                        <label class="block">
                                            <span class="font-medium tracking-wide text-slate-700 line-clamp-1 lg:text-base">
                                                {{ __('Date of birth') }}
                                            </span>
                                        </label>
                                        <div class="mt-2 rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400">
                                            {{ Carbon::parse($tilledForm->dob)->format('M d, Y') ?? 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="my-2">
                                        <label class="block">
                                            <span class="font-medium tracking-wide text-slate-700 line-clamp-1 lg:text-base">
                                                {{ __('Job Title') }}
                                            </span>
                                        </label>
                                        <div class="mt-2 rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400">
                                            {{ $tilledForm->job_title ?? 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="my-2">
                                        <label class="block">
                                            <span class="font-medium tracking-wide text-slate-700 line-clamp-1 lg:text-base">
                                                {{ __('Percentage Share Holding') }}
                                            </span>
                                        </label>
                                        <div class="mt-2 rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400">
                                            {{ Number::percentage((float) $tilledForm->percentage_shareholding) ?? 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="my-2">
                                        <label class="block">
                                            <span class="font-medium tracking-wide text-slate-700 line-clamp-1 lg:text-base">
                                                {{ __('SSN') }}
                                            </span>
                                        </label>
                                        <div class="mt-2 rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400">
                                            {{ filled($tilledForm->ssn) ? $tilledForm->ssn : '*********' }}
                                        </div>
                                    </div>
                                </div>
                                <h2 class="text-lg font-medium text-slate-700">
                                    {{ __('Address Details') }}
                                </h2>
                                <hr class="mt-2 h-px bg-slate-200">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-5 mb-3">
                                    <div class="my-2">
                                        <label class="block">
                                            <span class="font-medium tracking-wide text-slate-700 line-clamp-1 lg:text-base">
                                                {{ __('Contact Address') }}
                                            </span>
                                        </label>
                                        <div class="mt-2 rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400">
                                            {{ filled($tilledForm->contact_address) ? $tilledForm->contact_address : 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="my-2">
                                        <label class="block">
                                            <span class="font-medium tracking-wide text-slate-700 line-clamp-1 lg:text-base">
                                                {{ __('Contact State') }}
                                            </span>
                                        </label>
                                        <div class="mt-2 rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400">
                                            {{ State::tryFrom($tilledForm->contact_state)?->displayName() ?? 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="my-2">
                                        <label class="block">
                                            <span class="font-medium tracking-wide text-slate-700 line-clamp-1 lg:text-base">
                                                {{ __('Contact City') }}
                                            </span>
                                        </label>
                                        <div class="mt-2 rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400">
                                            {{ filled($tilledForm->contact_city) ? $tilledForm->contact_city : 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="my-2">
                                        <label class="block">
                                            <span class="font-medium tracking-wide text-slate-700 line-clamp-1 lg:text-base">
                                                {{ __('Contact Zip') }}
                                            </span>
                                        </label>
                                        <div class="mt-2 rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400">
                                            {{ filled($tilledForm->contact_zip) ? $tilledForm->contact_zip : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <h2 class="text-lg font-medium tracking-wide text-slate-700">
                                {{ __('Company Information') }}
                            </h2>
                            <hr class="mt-2 h-px bg-slate-200">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-5 mb-3">
                                <div class="my-2">
                                    <x-form.input-field
                                        wire:model="tilledForm.legal_name"
                                        type="text"
                                        :label="__('Legal Name')"
                                        name="tilledForm.legal_name"
                                        required
                                        class="w-full"
                                        :placeholder="__('Legal Name')"
                                    />
                                </div>
                                <div class="my-2">
                                    <x-form.select
                                        wire:model="tilledForm.industry_type"
                                        :label="__('Business Type')"
                                        :options="IndustryType::displaySelectionBox()"
                                        name="tilledForm.industry_type"
                                        :placeholder="__('Business Type')"
                                        required
                                    />
                                </div>
                                <div class="my-2">
                                    <x-form.select
                                        wire:model="tilledForm.company_category"
                                        :label="__('Company Category')"
                                        :options="CompanyCategory::displaySelectionBox()"
                                        name="tilledForm.company_category"
                                        :placeholder="__('Company Category')"
                                        required
                                    />
                                </div>
                                <div class="my-2">
                                    <x-form.input-field
                                        wire:model="tilledForm.average_transaction_amount"
                                        type="text"
                                        :label="__('Average Transaction Amount')"
                                        name="tilledForm.average_transaction_amount"
                                        required
                                        class="w-full"
                                        :placeholder="__('Average Transaction Amount')"
                                    />
                                </div>
                                <div class="my-2">
                                    <x-form.input-field
                                        wire:model="tilledForm.statement_descriptor"
                                        type="text"
                                        :label="__('Statement Descriptor')"
                                        name="tilledForm.statement_descriptor"
                                        required
                                        class="w-full"
                                        :placeholder="__('Statement Descriptor')"
                                    />
                                </div>
                                <div class="my-2">
                                    <x-form.input-field
                                        wire:model="tilledForm.fed_tax_id"
                                        type="text"
                                        :label="__('Tax Identification Number')"
                                        name="tilledForm.fed_tax_id"
                                        required
                                        class="w-full"
                                        :placeholder="__('Tax Identification Number')"
                                    />
                                </div>
                                <div class="my-2">
                                    <x-form.select
                                        wire:model="tilledForm.yearly_volume_range"
                                        :label="__('Yearly Volume Range')"
                                        :options="YearlyVolumeRange::displaySelectionBox()"
                                        name="tilledForm.yearly_volume_range"
                                        required
                                        class="w-full"
                                        :placeholder="__('Yearly Volume Range')"
                                    />
                                </div>
                            </div>
                            <h2 class="text-lg font-medium text-slate-700">
                                {{ __('Business Owner Information') }}
                            </h2>
                            <hr class="mt-2 h-px bg-slate-200">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-5 mb-3">
                                <div class="my-2">
                                    <x-form.input-field
                                        wire:model="tilledForm.first_name"
                                        type="text"
                                        :label="__('First Name')"
                                        name="tilledForm.first_name"
                                        required
                                        class="w-full"
                                        :placeholder="__('First Name')"
                                    />
                                </div>
                                <div class="my-2">
                                    <x-form.input-field
                                        wire:model="tilledForm.last_name"
                                        type="text"
                                        :label="__('Last Name')"
                                        name="tilledForm.last_name"
                                        required
                                        class="w-full"
                                        :placeholder="__('Last Name')"
                                    />
                                </div>
                                <div
                                    x-data="datePicker"
                                    class="my-2"
                                >
                                    <span class="inline-flex font-semibold tracking-wide text-black lg:text-md">
                                        {{ __('Date of Birth') }}<span class="text-error text-base leading-none">*</span>
                                    </span>
                                    <div wire:ignore>
                                        <input
                                            wire:model="tilledForm.dob"
                                            type="text"
                                            x-init="flatPickr"
                                            placeholder="mm/dd/yyyy"
                                            class="form-input mt-1.5 rounded-lg border text-black border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary w-full"
                                            autocomplete="off"
                                            required
                                        />
                                    </div>
                                    @error('tilledForm.dob')
                                        <div class="mt-2">
                                            <span class="text-error text-sm+">
                                                {{ $message }}
                                            </span>
                                        </div>
                                    @enderror
                                </div>
                                <div class="my-2">
                                    <x-form.input-field
                                        wire:model="tilledForm.job_title"
                                        type="text"
                                        :label="__('Job Title')"
                                        name="tilledForm.job_title"
                                        required
                                        class="w-full"
                                        :placeholder="__('Job Title')"
                                    />
                                </div>
                                <div class="my-2">
                                    <x-form.input-field
                                        wire:model="tilledForm.percentage_shareholding"
                                        type="number"
                                        :label="__('Percentage Shareholding')"
                                        name="tilledForm.percentage_shareholding"
                                        required
                                        class="w-full"
                                        :placeholder="__('Percentage Shareholding')"
                                    />
                                </div>
                                <div
                                    x-bind:class="@js(IndustryType::ssnIsNotRequired()).includes($wire.tilledForm.industry_type) ? 'my-3' : 'my-2'"
                                >
                                    <label class="block">
                                        <span class="font-semibold tracking-wide text-black lg:text-md">
                                            {{ __('SSN') }}<span
                                                class="text-error text-base"
                                                x-bind:class="@js(IndustryType::ssnIsNotRequired()).includes($wire.tilledForm.industry_type) && 'hidden'"
                                            >*</span>
                                        </span>
                                        <input
                                            class="w-full form-input mt-1.5 rounded-lg border text-black border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-500 hover:border-slate-400 focus:border-primary"
                                            type="text"
                                            name="tilledForm.ssn"
                                            wire:model="tilledForm.ssn"
                                            autocomplete="off"
                                            placeholder="{{ __('SSN') }}"
                                            x-bind:required="! @js(IndustryType::ssnIsNotRequired()).includes($wire.tilledForm.industry_type)"
                                        >
                                    </label>
                                    @error('tilledForm.ssn')
                                        <div class="mt-2">
                                            <span class="text-error text-sm+">
                                                {{ $message }}
                                            </span>
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="my-2">
                                <x-smarty-address
                                    :blockTitle="__('Address Details')"
                                    :label="[
                                        'address' => __('Contact Address'),
                                        'city' => __('Contact City'),
                                        'state' => __('Contact State'),
                                        'zip' => __('Contact Zip Code'),
                                    ]"
                                    :wire-element="[
                                        'address' => 'tilledForm.contact_address',
                                        'city' => 'tilledForm.contact_city',
                                        'state' => 'tilledForm.contact_state',
                                        'zip' => 'tilledForm.contact_zip',
                                    ]"
                                    :required="true"
                                />
                            </div>
                        @endif
                        <h2 class="text-lg font-medium tracking-wide text-black">
                            {{ __('Bank Account Details') }}
                        </h2>
                        <hr class="mt-2 h-px bg-slate-200">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-5 mb-3">
                            <div class="my-2">
                                <x-form.input-field
                                    wire:model="tilledForm.account_holder_name"
                                    type="text"
                                    :label="__('Account Holder Name')"
                                    name="tilledForm.account_holder_name"
                                    class="w-full"
                                    :placeholder="__('Account Holder Name')"
                                    required
                                />
                            </div>
                            <div class="my-2">
                                <x-form.input-field
                                    wire:model="tilledForm.bank_name"
                                    type="text"
                                    :label="__('Bank Name')"
                                    name="tilledForm.bank_name"
                                    required
                                    class="w-full"
                                    :placeholder="__('Bank Name')"
                                />
                            </div>
                            <div class="my-2">
                                <x-form.select
                                    wire:model="tilledForm.bank_account_type"
                                    :label="__('Bank Account Type')"
                                    :options="BankAccountType::displaySelectionBox()"
                                    name="tilledForm.bank_account_type"
                                    required
                                    class="w-full"
                                    :placeholder="__('Bank Account Type')"
                                />
                            </div>
                            <div class="my-2">
                                <x-form.input-field
                                    wire:model="tilledForm.bank_account_number"
                                    type="text"
                                    :label="__('Bank Account Number')"
                                    name="tilledForm.bank_account_number"
                                    required
                                    class="w-full"
                                    :placeholder="__('Bank Account Number')"
                                />
                            </div>
                            <div class="my-2">
                                <x-form.input-field
                                    wire:model="tilledForm.bank_routing_number"
                                    type="text"
                                    :label="__('Bank Routing Number')"
                                    name="tilledForm.bank_routing_number"
                                    required
                                    class="w-full"
                                    :placeholder="__('Bank Routing Number')"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="flex justify-end space-x-2">
                            <a
                                wire:navigate
                                href="{{ route('home') }}"
                                class="btn border focus:border-slate-400 bg-slate-150 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80"
                            >
                                {{ __('Cancel') }}
                            </a>
                            <x-form.button
                                type="button"
                                variant="primary"
                                wire:click="updateMerchantSettings"
                                wire:loading.class="opacity-50"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading wire:target="updateMerchantSettings">{{ __('Submitting') }}</span>
                                <span wire:loading.remove>{{ __('Submit') }}</span>
                            </x-form.button>
                        </div>
                    </div>
                </div>
            @endif
            <div
                x-show="merchantName === @js(MerchantName::USA_EPAY->value)"
                x-transition:enter="transition-all duration-500 easy-in-out"
                x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]"
                x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]"
                :class="@js($isNotEditable) && 'opacity-75'"
            >
                <div class="mx-3">
                    <h2 class="text-base font-semibold tracking-wide text-black">
                        {{ __('Merchant Account Credentials') }}
                    </h2>
                    <hr class="mt-2 h-px bg-slate-200">

                    <div class="my-3">
                        <fieldset class="font-medium tracking-wide text-slate-700 line-clamp-1 lg:text-base">
                            <div>
                                <legend class="text-md text-black tracking-wide font-semibold">
                                    {{ __('Payment Method') }}<sup class="text-error text-base">*</sup>
                                </legend>
                            </div>
                            <div class="flex space-x-12 mt-3 items-center">
                                @if ($isNotEditable)
                                    <div x-tooltip.placement.bottom="@js(__('This field is not editable'))">
                                        <x-form.input-checkbox
                                            wire:model="usaEpayForm.merchant_type"
                                            :label="__(MerchantType::ACH->displayName())"
                                            value="{{ MerchantType::ACH->value }}"
                                            name="usaEpayForm.merchant_type"
                                            disabled
                                        />
                                    </div>
                                    <div x-tooltip.placement.bottom="@js(__('This field is not editable'))">
                                        <x-form.input-checkbox
                                            wire:model="usaEpayForm.merchant_type"
                                            :label="__(MerchantType::CC->displayName())"
                                            value="{{ MerchantType::CC->value }}"
                                            name="usaEpayForm.merchant_type"
                                            disabled
                                        />
                                    </div>
                                @else
                                    <div>
                                        <label class="inline-flex space-x-2 items-center">
                                            <input
                                                type="checkbox"
                                                class="form-checkbox is-basic size-4 sm:size-4.5 rounded border-slate-400/70 checked:bg-primary hover:border-primary focus:border-primary"
                                                value="{{ MerchantType::ACH->value }}"
                                                name="usaEpayForm.merchant_type"
                                                wire:model="usaEpayForm.merchant_type"
                                            >
                                            <span>{{ __(MerchantType::ACH->displayName()) }}</span>
                                        </label>
                                    </div>
                                    <div>
                                        <label class="inline-flex space-x-2 items-center">
                                            <input
                                                type="checkbox"
                                                class="form-checkbox is-basic size-4 sm:size-4.5 rounded border-slate-400/70 checked:bg-primary hover:border-primary focus:border-primary"
                                                value="{{ MerchantType::CC->value }}"
                                                name="usaEpayForm.merchant_type"
                                                wire:model="usaEpayForm.merchant_type"
                                            >
                                            <span>{{ __(MerchantType::CC->displayName()) }}</span>
                                        </label>
                                    </div>
                                @endif
                            </div>
                            @error('usaEpayForm.merchant_type')
                                <div class="mt-2">
                                    <span class="text-error text-sm+">
                                        {{ $message }}
                                    </span>
                                </div>
                            @enderror
                        </fieldset>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 mb-1.5">
                        <div
                            class="my-2"
                            @if ($isNotEditable)
                                x-tooltip.placement.bottom="@js(__('This field is not editable'))"
                            @endif
                        >
                            <x-form.input-field
                                type="text"
                                wire:model="usaEpayForm.usaepay_key"
                                :label="__('Key')"
                                name="usaEpayForm.usaepay_key"
                                class="w-full"
                                :placeholder="__('Enter Key')"
                                required
                                :disabled="$isNotEditable"
                            />
                        </div>
                        <div
                            class="my-2"
                            @if ($isNotEditable)
                                x-tooltip.placement.bottom="@js(__('This field is not editable'))"
                            @endif
                        >
                            <x-form.input-field
                                wire:model="usaEpayForm.usaepay_pin"
                                :label="__('Pin')"
                                name="usaEpayForm.usaepay_pin"
                                class="w-full"
                                :placeholder="__('Pin')"
                                type="text"
                                required
                                :disabled="$isNotEditable"
                            />
                        </div>
                    </div>
                </div>
                @if (! $isNotEditable)
                    <div class="p-4">
                        <div class="flex justify-end space-x-2">
                            <a
                                wire:navigate
                                href="{{ route('home') }}"
                                class="btn border focus:border-slate-400 bg-slate-150 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80"
                            >
                                {{ __('Cancel') }}
                            </a>
                            <x-form.button
                                type="button"
                                variant="primary"
                                wire:click="updateMerchantSettings"
                                wire:loading.class="opacity-50"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading wire:target="updateMerchantSettings">{{ __('Submitting') }}</span>
                                <span wire:loading.remove>{{ __('Submit') }}</span>
                            </x-form.button>
                        </div>
                    </div>
                @endif
            </div>
            <div
                x-show="merchantName === @js(MerchantName::STRIPE->value)"
                x-transition:enter="transition-all duration-500 easy-in-out"
                x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]"
                x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]"
                :class="@js($isNotEditable) && 'opacity-75'"
            >
                <div class="mx-3">
                    <h2 class="text-base font-semibold tracking-wide text-black">
                        {{ __('Merchant Account Credentials') }}
                    </h2>
                    <hr class="mt-2 h-px bg-slate-200">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5">
                        <div
                            class="my-2"
                            @if ($isNotEditable)
                                x-tooltip.placement.bottom="@js(__('This field is not editable'))"
                            @endif
                        >
                            <x-form.input-field
                                wire:model="stripeForm.stripe_secret_key"
                                type="text"
                                :label="__('Secret Key')"
                                name="stripeForm.stripe_secret_key"
                                required
                                class="w-full"
                                :placeholder="__('Secret Key')"
                                :disabled="$isNotEditable"
                            />
                        </div>
                    </div>
                </div>
                @if (! $isNotEditable)
                    <div class="p-4">
                        <div class="flex justify-end space-x-2">
                            <a
                                wire:navigate
                                href="{{ route('home') }}"
                                class="btn border focus:border-slate-400 bg-slate-150 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80"
                            >
                                {{ __('Cancel') }}
                            </a>
                            <x-form.button
                                type="button"
                                variant="primary"
                                wire:click="updateMerchantSettings"
                                wire:loading.class="opacity-50"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading wire:target="updateMerchantSettings">{{ __('Submitting') }}</span>
                                <span wire:loading.remove>{{ __('Submit') }}</span>
                            </x-form.button>
                        </div>
                    </div>
                @endif
            </div>
            <div
                x-show="merchantName === @js(MerchantName::AUTHORIZE->value)"
                x-transition:enter="transition-all duration-500 easy-in-out"
                x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]"
                x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]"
                :class="@js($isNotEditable) && 'opacity-75'"
            >
                <div class="m-4">
                    <h2 class="text-base font-semibold tracking-wide text-black">
                        {{ __('Merchant Account Credentials') }}
                    </h2>
                    <hr class="mt-2 h-px bg-slate-200">

                    <div class="my-3">
                        <fieldset class="font-medium tracking-wide text-slate-700 line-clamp-1 lg:text-base">
                            <div>
                                <legend class="text-md text-black tracking-wide font-semibold">
                                    {{ __('Payment Method') }}<span class="text-error text-base">*</span>
                                </legend>
                            </div>
                            <div class="flex space-x-12 mt-3 items-center">
                                @if ($isNotEditable)
                                    <div x-tooltip.placement.bottom="@js(__('This field is not editable'))">
                                        <x-form.input-checkbox
                                            wire:model="authorizeForm.merchant_type"
                                            :label="__(MerchantType::ACH->displayName())"
                                            value="{{ MerchantType::ACH->value }}"
                                            name="authorizeForm.merchant_type"
                                            disabled
                                        />
                                    </div>
                                    <div x-tooltip.placement.bottom="@js(__('This field is not editable'))">
                                        <x-form.input-checkbox
                                            wire:model="authorizeForm.merchant_type"
                                            :label="__(MerchantType::CC->displayName())"
                                            value="{{ MerchantType::CC->value }}"
                                            name="authorizeForm.merchant_type"
                                            disabled
                                        />
                                    </div>
                                @else
                                    <div>
                                        <label class="inline-flex space-x-2 items-center">
                                            <input
                                                type="checkbox"
                                                class="form-checkbox is-basic size-4 sm:size-4.5 rounded border-slate-400/70 checked:bg-primary hover:border-primary focus:border-primary"
                                                value="{{ MerchantType::ACH->value }}"
                                                name="authorizeForm.merchant_type"
                                                wire:model="authorizeForm.merchant_type"
                                            >
                                            <span>{{ __(MerchantType::ACH->displayName()) }}</span>
                                        </label>
                                    </div>
                                    <div>
                                        <label class="inline-flex space-x-2 items-center">
                                            <input
                                                type="checkbox"
                                                class="form-checkbox is-basic size-4 sm:size-4.5 rounded border-slate-400/70 checked:bg-primary hover:border-primary focus:border-primary"
                                                value="{{ MerchantType::CC->value }}"
                                                name="authorizeForm.merchant_type"
                                                wire:model="authorizeForm.merchant_type"
                                            >
                                            <span>{{ __(MerchantType::CC->displayName()) }}</span>
                                        </label>
                                    </div>
                                @endif
                            </div>
                            @error('authorizeForm.merchant_type')
                                <div class="mt-2">
                                    <span class="text-error text-sm+">
                                        {{ $message }}
                                    </span>
                                </div>
                            @enderror
                        </fieldset>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5">
                        <div
                            class="my-2"
                            @if ($isNotEditable)
                                x-tooltip.placement.bottom="@js(__('This field is not editable'))"
                            @endif
                        >
                            <x-form.input-field
                                wire:model="authorizeForm.authorize_login_id"
                                type="text"
                                :label="__('API Login ID')"
                                name="authorizeForm.authorize_login_id"
                                required
                                class="w-full"
                                placeholder="{{ __('API Login ID') }}"
                                :disabled="$isNotEditable"
                            />
                        </div>
                        <div
                            class="my-2"
                            @if ($isNotEditable)
                                x-tooltip.placement.bottom="@js(__('This field is not editable'))"
                            @endif
                        >
                            <x-form.input-field
                                wire:model="authorizeForm.authorize_transaction_key"
                                type="text"
                                :label="__('Transaction Key')"
                                name="authorizeForm.authorize_transaction_key"
                                required
                                class="w-full"
                                placeholder="{{ __('Transaction Key') }}"
                                :disabled="$isNotEditable"
                            />
                        </div>
                    </div>
                </div>
                @if (! $isNotEditable)
                    <div class="p-4">
                        <div class="flex justify-end space-x-2">
                            <a
                                wire:navigate
                                href="{{ route('home') }}"
                                class="btn border focus:border-slate-400 bg-slate-150 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80"
                            >
                                {{ __('Cancel') }}
                            </a>
                            <x-form.button
                                type="button"
                                variant="primary"
                                wire:click="updateMerchantSettings"
                                wire:loading.class="opacity-50"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading wire:target="updateMerchantSettings">{{ __('Submitting') }}</span>
                                <span wire:loading.remove>{{ __('Submit') }}</span>
                            </x-form.button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @script
        <script>
            Alpine.data('datePicker', () => ({
                flatPickrInstance: null,
                flatPickr () {
                    this.flatPickrInstance = window.flatpickr(this.$el, {
                        altInput: true,
                        altFormat: 'm/d/Y',
                        allowInput: true,
                        dateFormat: 'Y-m-d',
                        allowInvalidPreload: true,
                        disableMobile: true,
                        ariaDateFormat: 'm/d/Y',
                        maxDate: @js(now()->subYears(18)->toDateString()),
                    })
                },
                destroy() {
                    this.flatPickrInstance?.destroy()
                }
            }))
        </script>
    @endscript
</div>

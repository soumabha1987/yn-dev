@use('App\Enums\CreditorCurrentStep')
@use('App\Enums\CompanyMembershipStatus')
@use('Carbon\Carbon')
@use('App\Enums\CompanyBusinessCategory')
@use('App\Enums\MerchantType')
@use('App\Enums\State')
@use('Illuminate\Support\Number')
@use('Illuminate\Support\Facades\Storage')

<div>
    <div class="flex flex-col sm:flex-row gap-4">
        <div class="sm:basis-1/3 lg:basis-1/4 2xl:basis-1/5 rounded-lg card sm:min-h-[calc(100vh-60px-52px-32px)] flex flex-col">
            <div class="flex sm:flex-col lg:flex-row items-center gap-2 px-4 py-3 border-b border-slate-200">
                <div class="text-base border rounded-full is-initial border-primary/30 bg-primary/10 text-primary">
                    @if ($company->creditorUser->image)
                        <img
                            src="{{ Storage::url('profile-images/' . $company->creditorUser->image) }}"
                            class="rounded-full object-cover avatar size-16"
                        >
                    @else
                        <span class="uppercase avatar size-16 flex items-center justify-center">{{ $company->company_name ? substr($company->company_name, 0, 1) : "N/A" }}</span>
                    @endif
                </div>
                <div class="sm:text-center lg:text-left">
                    <h2 class="text-md text-black font-medium text-lg capitalize">
                        {{ $company->creditorUser->name }}
                    </h2>
                    <p class="text-xs">{{ $company->owner_email ?? 'N/A' }}</p>
                </div>
            </div>
            <div>
                <div class="grid grid-cols-2 gap-4 px-4 py-3">
                    <div class="sm:col-span-2">
                        <span class="text-sm font-medium text-black">
                            {{ __('Name') }}
                        </span>
                        <p class="text-xs mt-1">
                            {{ $company->company_name ?? 'N/A' }}
                        </p>
                    </div>

                    <div class="sm:col-span-2">
                        <span class="text-sm font-medium text-black">
                            {{ __('URL') }}
                        </span>
                        <p class="text-xs mt-1">
                            <a
                                href="{{ $company->url }}"
                                target="_blank"
                                class="select-all"
                            >
                                {{ $company->url ?? 'N/A' }}
                            </a>
                        </p>
                    </div>

                    <div class="sm:col-span-2">
                        <span class="text-sm font-medium text-black">
                            {{ __('Owner Full Name') }}
                        </span>
                        <p class="text-xs mt-1">
                            {{ str($company->owner_full_name ?? 'N/A')->title()->toString() }}
                        </p>
                    </div>

                    <div>
                        <span class="text-sm font-medium text-black">
                            {{ __('Owner Email') }}
                        </span>
                        <p class="text-xs mt-1 break-words">
                            {{ $company->owner_email ?? 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <span class="text-sm font-medium text-black">
                            {{ __('Owner Number') }}
                        </span>
                        <p class="text-xs mt-1">
                            {{ $company->owner_phone ? Str::formatPhoneNumber($company->owner_phone) : 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <span class="text-sm font-medium text-black">
                            {{ __('Billing Email') }}
                        </span>
                        <p class="text-xs mt-1 break-words">
                            {{ $company->billing_email ?? 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <span class="text-sm font-medium text-black">
                            {{ __('Billing Phone') }}
                        </span>
                        <p class="text-xs mt-1">
                            {{ $company->billing_phone ? Str::formatPhoneNumber($company->billing_phone) : 'N/A' }}
                        </p>
                    </div>

                    <div class="sm:col-span-2">
                        <span class="block text-sm font-medium text-black">
                            {{ __('Status') }}
                        </span>
                        <p
                            @class([
                                'badge text-xs+ py-0.5 mt-1',
                                'bg-success/20 text-success' => $company->current_step === CreditorCurrentStep::COMPLETED->value,
                                'bg-primary/20 text-primary' => $company->current_step !== CreditorCurrentStep::COMPLETED->value,
                            ])
                        >
                            {{ $company->current_step === CreditorCurrentStep::COMPLETED->value ? __('Active') : __('In Progress') }}
                        </p>
                    </div>
                    @if ($company->company_category && $company->industry_type)
                        <div class="sm:col-span-2">
                            <span class="text-sm font-medium text-black">
                                {{ __('Category') }}
                            </span>
                            <p class="text-xs mt-1">
                                {{ $company->company_category->displayName() }}
                            </p>
                        </div>

                        <div class="sm:col-span-2">
                            <span class="text-sm font-medium text-black">
                                {{ __('Industry Type') }}
                            </span>
                            <p class="text-xs mt-1">
                                {{ $company->industry_type->displayName() }}
                            </p>
                        </div>
                    @endif
                    <div class="sm:col-span-2">
                        <span class="text-sm font-medium text-black">
                            {{ __('Business category') }}
                        </span>
                        <p class="text-xs mt-1">
                            {{ $company->business_category ? CompanyBusinessCategory::displaySelectionBox()[$company->business_category->value] : 'N/A' }}
                        </p>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="text-sm font-medium text-black">
                            {{ __('Time Zone') }}
                        </span>
                        <ul class="text-xs flex items-center gap-2 mt-1">
                            <li>{{ $company->timezone->value . ' (' .$company->timezone->getName() .')' }}</li>
                        </ul>
                    </div>

                    <div class="col-span-2">
                        <span class="text-sm font-medium text-black">
                            {{ __('Contact Time') }}
                        </span>
                        <ul class="text-xs flex item-center gap-8 mt-1">
                            @if (data_get(Carbon::getDays(), $company->from_day) && data_get(Carbon::getDays(), $company->to_day) && $company->from_time && $company->to_time)
                                <li>{{ Carbon::getDays()[$company->from_day] . ' to ' . Carbon::getDays()[$company->to_day] }}</li>
                                <li>{{ $company->from_time->format('h : ia') . ' to ' . $company->to_time->format('h : ia') }}</li>
                            @else
                                N/A
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="sm:basis-2/3 lg:basis-3/4 2xl:basis-4/5">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6 px-4 py-3 card">
                <div class="relative flex flex-col overflow-hidden border border-info rounded-lg bg-info/15 p-3.5">
                    <p class="text-xs uppercase text-black">{{ __('Consumers Count') }}</p>
                    <div class="flex items-end justify-between space-x-2 space-x-reverse">
                        <p class="mt-4 text-xl font-medium text-black">{{ $consumerCount }}</p>
                        <a
                            wire:navigate
                            href="{{ route('manage-consumers') . '?company=' . $company->id }}"
                            class="border-b text-primary border-dotted border-current pb-0.5 text-xs font-medium outline-none transition-colors duration-300 line-clamp-1 hover:text-primary-focus focus:text-primary-focus"
                        >
                            {{ __('Find Consumers') }}
                        </a>
                    </div>
                    <x-heroicon-o-user-group class="mask absolute top-0 right-0 -m-3 size-16 opacity-10 text-primary"/>
                </div>
                <div class="relative flex flex-col overflow-hidden border border-warning bg-warning/15 rounded-lg p-3.5">
                    <p class="text-xs uppercase text-black">
                        {{ __('Total pending transactions amount') }}
                    </p>
                    <div class="flex items-end justify-between space-x-2 space-x-reverse">
                        <p class="mt-4 text-xl font-medium text-black">
                            {{ Number::currency((float)$scheduleTransactionAmount) }}
                        </p>
                    </div>
                    <x-heroicon-o-currency-dollar class="mask  absolute top-0 right-0 -m-3 size-16  opacity-10 text-warning"/>
                </div>
                <div class="group relative flex flex-col overflow-hidden border border-success bg-success/15 rounded-lg p-3.5">
                    <p class="text-xs uppercase text-black">
                        {{ filled($merchantName) ? $merchantName : __('Merchant Not Found') }}
                    </p>
                    <div class="flex items-end justify-between space-x-2 space-x-reverse text-black">
                        <div class="mt-4">
                            <div class="flex justify-between space-x-5 text-xl mt-1">
                                <span class="flex space-x-1 items-center font-medium">
                                    <x-heroicon-s-check-circle @class(['w-5', 'text-success' => $achMerchant]) />
                                    <span>{{ MerchantType::ACH->displayName() }}</span>
                                </span>
                                <span class="flex space-x-1 items-center font-medium">
                                    <x-heroicon-s-check-circle @class(['w-5', 'text-success' => $ccMerchant]) />
                                    <span>{{ MerchantType::CC->displayName() }}</span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <x-heroicon-o-ticket class="mask absolute top-0 right-0 -m-3 size-16 opacity-10 text-success"/>
                </div>
            </div>

            <div class="py-4 grid lg:grid-cols-2 gap-4">
                <div class="card">
                    <div class="relative border rounded-lg border-slate-200 m-4 h-full">
                        <h3 class="absolute left-2 top-0 bg-white px-2 -mt-3 text-lg font-medium text-black capitalize">{{ __('Plan Details') }}</h3>
                        <div class="p-4 mt-2">
                            <div class="grid grid-cols-2 lg:grid-cols-3 gap-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-black font-medium">{{ __('Name') }}</h4>
                                        <span @class([
                                            'badge text-xs+ py-0.5',
                                            'bg-success/20 text-success' => $company->activeCompanyMembership?->status === CompanyMembershipStatus::ACTIVE,
                                            'bg-error/20 text-error' => $company->activeCompanyMembership?->status === CompanyMembershipStatus::INACTIVE,
                                        ])>
                                            {{ $company->activeCompanyMembership?->status->value }}
                                        </span>
                                    </div>
                                    <p>{{ $company->activeCompanyMembership?->membership->name ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <h4 class="text-black font-medium">{{ __('Type') }}</h4>
                                    <p>{{ $company->activeCompanyMembership?->membership->frequency->displayName() ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <h4 class="text-black font-medium">{{ __('Plan end') }}</h4>
                                    <div class="badge bg-error/20 py-0.5">
                                        <p class="text-xs text-error">
                                            {{ $company->activeCompanyMembership?->current_plan_end->format('M d, Y') ?? 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-black font-medium">{{ __('Amount') }}</h4>
                                    <p>{{ $company->activeCompanyMembership ? Number::currency((float) $company->activeCompanyMembership->membership->price) : 'N/A' }}</p>
                                </div>
                                <div x-data="{ showTooltip: false }">
                                    <h4
                                        class="text-black font-medium line-clamp-1 cursor-pointer"
                                        x-on:mouseover="showTooltip = true"
                                        x-on:mouseleave="showTooltip = false"
                                    >
                                        {{ __('Percentage of payment') }}
                                        <div
                                            x-show="showTooltip"
                                            x-transition:enter="transition-transform duration-200 ease-out absolute origin-top"
                                            x-transition:enter-start="scale-75"
                                            x-transition:enter-end="scale-100 static"
                                            class="z-10 flex rounded space-x-3 absolute bg-slate-200 p-2 mt-2 transition-opacity duration-300 ease-in-out"
                                        >
                                            {{ __('Percentage of payment') }}
                                        </div>
                                    </h4>
                                    <p>{{ $company->activeCompanyMembership ? Number::percentage((float) $company->activeCompanyMembership->membership->fee, 2) : 'N/A' }}</p>
                                </div>
                                <div>
                                    <h4 class="text-black font-medium">{{ __('Account limit') }}</h4>
                                    <p>{{ $company->activeCompanyMembership ? Number::format((int) $company->activeCompanyMembership->membership->upload_accounts_limit) : 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="relative border rounded-lg border-slate-200 m-4 h-full">
                        <h3 class="absolute left-2 top-0 bg-white px-2 -mt-3 text-lg font-medium text-black capitalize">{{ __('Address') }}</h3>
                        <div class="p-4 mt-2">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <h4 class="text-black font-medium mb-1">{{ __('Company Address') }}</h4>
                                    <p>
                                        {{ $company->address ? $company->address. ',' : 'N/A' }}<br>
                                        {{ $company->city ? $company->city. ',' : '' }}<br>
                                        {{ $company->state ? State::displaySelectionBox()[$company->state]. ',' : '' }}<br>
                                        {{ $company->zip }}
                                    </p>
                                </div>
                                <div>
                                    <h4 class="text-black font-medium mb-1">{{ __('Billing Address') }}</h4>
                                    <p>
                                        {{ $company->billing_address ? $company->billing_address. ',' : 'N/A' }}<br>
                                        {{ $company->billing_city ? $company->billing_city. ',' : '' }}<br>
                                        {{ $company->billing_state ? State::displaySelectionBox()[$company->billing_state]. ',' : '' }}<br>
                                        {{ $company->billing_zip }}<br>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @if ($company->partner)
                    <div class="card lg:col-span-2">
                        <div class="relative border rounded-lg border-slate-200 m-4">
                            <h3 class="absolute left-2 top-0 bg-white px-2 -mt-3 text-lg font-medium text-black capitalize">{{ __('Partner Info') }}</h3>
                            <div class="h-full p-4 mt-2">
                                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-2">
                                    <div>
                                        <h4 class="text-black font-medium">{{ __('Name') }}</h4>
                                        <p>{{ $company->partner->name }}</p>
                                    </div>
                                    <div>
                                        <h4 class="text-black font-medium">{{ __('Contact Email') }}</h4>
                                        <p>{{ $company->partner->contact_email }}</p>
                                    </div>
                                    <div>
                                        <h4 class="text-black font-medium">{{ __('Contact Phone') }}</h4>
                                        <p>{{ $company->partner->contact_phone }}</p>
                                    </div>
                                    <div>
                                        <h4 class="text-black font-medium">{{ __('Rev share %') }}</h4>
                                        <p>{{ Number::percentage((float) $company->partner->revenue_share, 2) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="card lg:col-span-2">
                    <div class="relative border rounded-lg border-slate-200 m-4">
                        <h3 class="absolute left-2 top-0 bg-white px-2 -mt-3 text-lg font-medium text-black capitalize">{{ __('Master Terms Offer') }}</h3>
                        <div class="h-full px-2 py-4 mt-2">
                            <div class="min-w-full overflow-auto">
                                <table class="w-full text-left">
                                    <tr>
                                        <th class="capitalize text-black font-medium text-nowrap lg:text-wrap px-2">{{ __('Settlement Balance Discount %') }}</th>
                                        <th class="capitalize text-black font-medium text-nowrap lg:text-wrap px-2">{{ __('Payment Plan Balance Discount %') }}</th>
                                        <th class="capitalize text-black font-medium text-nowrap lg:text-wrap px-2">{{ __('Min. Monthly Payment %') }}</th>
                                        <th class="capitalize text-black font-medium text-nowrap lg:text-wrap px-2">{{ __('Max. Days 1st Payment') }}</th>
                                    </tr>
                                    <tr>
                                        <td class="px-2">{{ $company->pif_balance_discount_percent ? Number::percentage((float) $company->pif_balance_discount_percent) : 'N/A' }}</td>
                                        <td class="px-2">{{ $company->ppa_balance_discount_percent ? Number::percentage((float) $company->ppa_balance_discount_percent) : 'N/A' }}</td>
                                        <td class="px-2">{{ $company->min_monthly_pay_percent ? Number::percentage((float)$company->min_monthly_pay_percent) : 'N/A' }}</td>
                                        <td class="px-2">{{ $company->max_days_first_pay ?? 'N/A' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card lg:col-span-2">
                    <div class="relative border rounded-lg border-slate-200 m-4">
                        <h3 class="absolute left-2 top-0 bg-white px-2 -mt-3 text-lg font-medium text-black capitalize">{{ __('Users Details') }}</h3>
                        <div class="h-full px-2 py-4 mt-2">
                            <div class="min-w-full overflow-auto">
                                <table class="w-full text-left">
                                    <tr>
                                        <th class="capitalize text-black font-medium text-nowrap px-2">{{ __('Index') }}</th>
                                        <th class="capitalize text-black font-medium text-nowrap px-2">{{ __('Name') }}</th>
                                        <th class="capitalize text-black font-medium text-nowrap px-2">{{ __('Email') }}</th>
                                        <th class="capitalize text-black font-medium text-nowrap px-2">{{ __('Phone') }}</th>
                                    </tr>
                                    @foreach ($company->users as $user)
                                        <tr>
                                            <td class="px-2">{{ $loop->iteration }}</td>
                                            <td class="px-2">{{ $user->name ?? 'N/A' }}</td>
                                            <td class="px-2 lg:w-1/4">{{ $user->email ?? 'N/A' }}</td>
                                            <td class="px-2">{{ $user->phone_no ? preg_replace('/^(\d{3})(\d{3})(\d{4})$/', '($1) $2-$3', $user->phone_no) : 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

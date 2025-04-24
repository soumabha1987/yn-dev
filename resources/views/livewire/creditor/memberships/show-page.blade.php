@use('Illuminate\Support\Number')
@use('App\Enums\CompanyMembershipStatus')
@use('App\Enums\MembershipFeatures')

<div>
    <div class="card mb-8 px-4 py-4 sm:px-5">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-y-5 gap-5">
            <div>
                <span class="mt-1 text-xs">
                    {{ __('Membership Name') }}
                </span>
                <p class="text-lg font-semibold text-black">{{ $membership->name }}</p>
            </div>

            <div>
                <span class="mt-1 text-xs">
                    {{ __('Frequency') }}
                </span>
                <p class="text-lg font-semibold text-black">{{ $membership->frequency->name }}</p>
            </div>

            <div>
                <span class="mt-1 text-xs">
                    {{ __('Licensing Fee') }}
                </span>
                <p class="text-lg font-semibold text-black">{{ Number::currency((float) $membership->price) }}</p>
            </div>

            <div>
                <span class="mt-1 text-xs">
                    {{ __('E-Letter Fee') }}
                </span>
                <p class="text-lg font-semibold text-black">{{ Number::currency((float) $membership->e_letter_fee) }}</p>
            </div>

            <div>
                <span class="mt-1 text-xs">
                    {{ __('Percentage of Payments') }}
                </span>
                <p class="text-lg font-semibold text-black">{{ Number::percentage($membership->fee) }}</p>
            </div>

            <div>
                <span class="mt-1 text-xs">
                    {{ __('Upload Accounts Limit') }}
                </span>
                <p class="text-lg font-semibold text-black">{{ Number::format($membership->upload_accounts_limit) }}</p>
            </div>

            <div>
                <span class="mt-1 text-xs">
                    {{ __('Status') }}
                </span>
                <br>
                <p
                    @class([
                        'text-lg font-semibold',
                        'text-error' => ! $membership->status,
                        'text-success' => $membership->status,
                    ])
                >
                    {{ $membership->status ? __('Active') : __('Inactive') }}
                </p>
            </div>

            <div>
                <span class="mt-1 text-xs">
                    {{ __(' Description') }}
                </span>
                <p class="text-sm font-semibold text-black">{{ str($membership->description)->title() }}</p>
            </div>
            <div class="mb-3">
                <span class="mt-1 text-xs">
                    {{ __('Features') }}
                </span>
                    @foreach(MembershipFeatures::displayFeatures() as $key => $value)
                        <div class="flex items-center space-x-2 my-2 text-black font-semibold">
                            @if(in_array($key, $membership->features))
                                <x-lucide-check class="size-4.5 text-success"/>
                            @else
                                <x-lucide-x class="size-4.5 text-error"/>
                            @endif
                            <p>{{$value}}</p>
                        </div>
                    @endforeach
            </div>
        </div>
        <div class="flex items-center justify-end space-x-4">
            <div class="flex space-x-3 items-center">
                <a
                    wire:navigate
                    class="btn border focus:border-slate-400 bg-slate-150 font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80"
                    href="{{ route('super-admin.memberships') }}"
                >
                    {{ __('Cancel') }}
                </a>
                <a
                    wire:navigate
                    class="btn text-white border focus:border-info-focus bg-info hover:bg-info-focus focus:bg-info-focus active:bg-info-focus/90"
                    href="{{ route('super-admin.memberships.edit', $membership->id) }}"
                >
                    <div class="flex space-x-1 items-center">
                        <x-heroicon-m-pencil-square class="size-5 text-white"/>
                        <span>{{ __('Edit') }}</span>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div
            @class([
                'flex p-4 sm:items-center gap-4',
                'justify-between' => $companyMemberships->isNotEmpty(),
                'justify-start' => $companyMemberships->isEmpty()
            ])
        >
            <h2 class="text-md text-black font-semibold lg:text-lg">
                {{ __('List of creditors on this membership') }}
            </h2>
            <x-table.per-page-count :items="$companyMemberships" />
        </div>

        <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
            <x-table class="text-base">
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th>{{ __('Company name') }}</x-table.th>
                        <x-table.th>{{ __('Membership start date') }}</x-table.th>
                        <x-table.th>{{ __('Membership end date') }}</x-table.th>
                        <x-table.th class="text-center">{{ __('Status') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse ($companyMemberships as $companyMembership)
                        <x-table.tr>
                            <x-table.td>{{ $companyMembership->company->company_name }}</x-table.td>
                            <x-table.td>{{ $companyMembership->current_plan_start->formatWithTimezone() }}</x-table.td>
                            <x-table.td>{{ $companyMembership->current_plan_end->formatWithTimezone() }}</x-table.td>
                            <x-table.td class="text-center">
                                <span @class([
                                    'badge',
                                    'bg-error/20 text-error' => $companyMembership->status === CompanyMembershipStatus::INACTIVE,
                                    'bg-success/20 text-success' => $companyMembership->status === CompanyMembershipStatus::ACTIVE,
                                ])>
                                    {{ $companyMembership->status->name }}
                                </span>
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="4"/>
                    @endforelse
                </x-slot>
            </x-table>
            <x-table.per-page :items="$companyMemberships"/>
        </div>
    </div>
</div>

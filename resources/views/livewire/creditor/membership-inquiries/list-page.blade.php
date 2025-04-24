@use(App\Enums\MembershipInquiryStatus)
<div>
    <div class="card">
        <div
            x-on:refresh-parent.window="$wire.$refresh"
            @class([
                'flex flex-col sm:flex-row p-4 sm:items-center gap-4',
                'justify-between' => $membershipInquiries->isNotEmpty(),
                'justify-end' => $membershipInquiries->isEmpty()
            ])
        >
            <x-table.per-page-count :items="$membershipInquiries" />
            <div class="flex flex-col sm:flex-row space-x-2 justify-end items-stretch sm:items-center w-full sm:w-auto">
                <x-search-box
                    name="search"
                    wire:model.live.debounce.400="search"
                    placeholder="{{ __('Search') }}"
                    :description="__('You can search by its companies name, email and phone number')"
                />
            </div>
        </div>

        <div class="min-w-full overflow-auto">
            <x-table>
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th column="created_on" :$sortCol :$sortAsc>{{ __('Created On') }}</x-table.th>
                        <x-table.th column="company_name" :$sortCol :$sortAsc>{{ __('Company Name') }}</x-table.th>
                        <x-table.th column="email" :$sortCol :$sortAsc>{{ __('Email') }}</x-table.th>
                        <x-table.th column="phone" :$sortCol :$sortAsc class="lg:w-40">{{ __('Phone') }}</x-table.th>
                        <x-table.th column="account-in-scope" :$sortCol :$sortAsc class="lg:w-40">{{ __('Accounts In Scope') }}</x-table.th>
                        <x-table.th column="status" :$sortCol :$sortAsc class="lg:w-40">{{ __('Status') }}</x-table.th>
                        <x-table.th colspan="2" class="lg:w-1/12">{{ __('Actions') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse ($membershipInquiries as $membershipInquiry)
                        <x-table.tr @class(['font-bold' => $membershipInquiry->status === MembershipInquiryStatus::NEW_INQUIRY])>
                            <x-table.td>{{ $membershipInquiry->created_at->formatWithTimezone() }}</x-table.td>
                            <x-table.td>{{ str($membershipInquiry->company->company_name)->title() }}</x-table.td>
                            <x-table.td>{{ $membershipInquiry->company->owner_email }}</x-table.td>
                            <x-table.td>{{ Str::formatPhoneNumber($membershipInquiry->company->owner_phone) }}</x-table.td>
                            <x-table.td>{{ number_format($membershipInquiry->accounts_in_scope, 0, '.', ',') }}</x-table.td>
                            <x-table.td class="text-nowrap">
                                <span @class([
                                    'badge py-1.5 rounded-full font-semibold text-nowrap',
                                    'text-blue-600 bg-blue-100' => $membershipInquiry->status === MembershipInquiryStatus::NEW_INQUIRY,
                                    'text-green-600 bg-green-100' => $membershipInquiry->status === MembershipInquiryStatus::RESOLVED,
                                    'text-red-600 bg-red-100' => $membershipInquiry->status === MembershipInquiryStatus::CLOSE,
                                ])>
                                    {{ $membershipInquiry->status->displayName() }}
                                </span>
                            </x-table.td>
                            <x-table.td>
                                <div class="flex space-x-2 items-center">
                                    <livewire:creditor.membership-inquiries.view-page
                                        :$membershipInquiry
                                        :key="str()->random(10)"
                                    />

                                    @if ($membershipInquiry->status === MembershipInquiryStatus::NEW_INQUIRY || $membershipInquiry->status === MembershipInquiryStatus::RESOLVED)
                                        <livewire:creditor.membership-inquiries.view-page
                                            :$membershipInquiry
                                            :create-plan="true"
                                            :key="str()->random(10)"
                                        />
                                    @endif

                                    @if ($membershipInquiry->status === MembershipInquiryStatus::NEW_INQUIRY)
                                        <x-confirm-box
                                            :message="__('Are you sure you want to close this inquiry?')"
                                            :ok-button-label="__('Close')"
                                            action="closeInquiry({{ $membershipInquiry->id }})"
                                            isLoading=true
                                            okButtonVariant="error"
                                        >
                                            <x-form.button
                                                type="button"
                                                variant="error"
                                                class="text-xs sm:text-sm text-nowrap space-x-1 px-2 sm:px-3 py-1.5 hover:bg-error-focus"
                                                wire:target="closeInquiry({{ $membershipInquiry->id }})"
                                                wire:loading.attr="disabled"
                                                wire:loading.class="disabled:opacity-50"
                                            >
                                                <div class="flex space-x-1 items-center">
                                                    <x-lucide-ban class="size-4.5 sm:size-5 text-white" />
                                                    <span>{{ __('Close/No Deal') }}</span>
                                                </div>
                                            </x-form.button>
                                        </x-confirm-box>
                                    @endif
                                </div>
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="7" />
                    @endforelse
                </x-slot>
            </x-table>
        </div>
        <x-table.per-page :items="$membershipInquiries" />
    </div>
</div>

@use('Illuminate\Support\Number')

<div class="card">
    <div
        @class([
            'flex flex-col sm:flex-row p-4 sm:items-center gap-4',
            'justify-between' => $partners->isNotEmpty(),
            'justify-end' => $partners->isEmpty()
        ])
    >
        <x-table.per-page-count :items="$partners" />
        <div class="flex sm:items-center w-full sm:w-auto gap-2">
            <div>
                <a
                    wire:navigate
                    href="{{ route('super-admin.manage-partners.create') }}"
                    class="btn text-white bg-primary hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
                >
                    <div class="flex gap-x-1 items-center">
                        <x-lucide-circle-plus class="size-5"/>
                        <span>{{ __('Create') }}</span>
                    </div>
                </a>
            </div>
            @if ($partners->isNotEmpty())
                <div>
                    <x-form.button
                        wire:click="export"
                        wire:loading.attr="disabled"
                        type="button"
                        variant="primary"
                        class="space-x-2 disabled:opacity-50"
                    >
                        <div class="flex space-x-1 gap-2 items-center">
                            <span>{{ __('Export') }}</span>
                            <x-lucide-download class="size-5" wire:loading.remove wire:target="export" />
                            <x-lucide-loader-2 class="animate-spin size-5" wire:loading wire:target="export" />
                        </div>
                    </x-form.button>
                </div>
            @endif
        </div>
    </div>
    <div
        x-data="partner_link"
        class="min-w-full overflow-x-auto"
    >
        <x-table>
            <x-slot name="tableHead">
                <x-table.tr>
                    <x-table.th column="company-name" :$sortCol :$sortAsc>{{ __('Company Name') }}</x-table.th>
                    <x-table.th column="contact-first-name" :$sortCol :$sortAsc>{{ __('Contact First Name') }}</x-table.th>
                    <x-table.th column="contact-last-name" :$sortCol :$sortAsc>{{ __('Contact Last Name') }}</x-table.th>
                    <x-table.th column="contact-email" :$sortCol :$sortAsc>{{ __('Contact Email') }}</x-table.th>
                    <x-table.th column="contact-phone" :$sortCol :$sortAsc>{{ __('Contact Phone') }}</x-table.th>
                    <x-table.th>{{ __('Report Email(s)') }}</x-table.th>
                    <x-table.th column="revenue-share" :$sortCol :$sortAsc>{{ __('Rev Share %') }}</x-table.th>
                    <x-table.th column="creditors-quota" :$sortCol :$sortAsc>{{ __('# Quota') }}</x-table.th>
                    <x-table.th column="joined" :$sortCol :$sortAsc>{{ __('#Joined') }}</x-table.th>
                    <x-table.th column="quota-percentage" :$sortCol :$sortAsc>{{ __('% Quota') }}</x-table.th>
                    <x-table.th column="yn-total-revenue" :$sortCol :$sortAsc>{{ __('YN Total Rev to Date') }}</x-table.th>
                    <x-table.th column="partner-total-revenue" :$sortCol :$sortAsc>{{ __('Partner Rev. To Date') }}</x-table.th>
                    <x-table.th column="yn-net-revenue" :$sortCol :$sortAsc>{{ __('YN Net Rev To Date') }}</x-table.th>
                    <x-table.th>{{ __('Actions') }}</x-table.th>
                </x-table.tr>
            </x-slot>
            <x-slot name="tableBody">
                @forelse ($partners as $partner)
                    <x-table.tr>
                        <x-table.td>{{ str($partner->name)->title() }}</x-table.td>
                        <x-table.td>{{ str($partner->contact_first_name)->title() }}</x-table.td>
                        <x-table.td>{{ str($partner->contact_last_name ?? 'N/A')->title() }}</x-table.td>
                        <x-table.td>{{ $partner->contact_email }}</x-table.td>
                        <x-table.td>{{ Str::formatPhoneNumber($partner->contact_phone) }}</x-table.td>
                        <x-table.td>
                            @foreach ($partner->report_emails as $email)
                                {{ $email }}<br>
                            @endforeach
                        </x-table.td>
                        <x-table.td>{{ Number::percentage($partner->revenue_share ?? 0, 2) }}</x-table.td>
                        <x-table.td>{{ Number::format($partner->creditors_quota ?? 0) }}</x-table.td>
                        <x-table.td>{{ Number::format($partner->companies_count ?? 0) }}</x-table.td>
                        <x-table.td>
                            {{ Number::percentage($partner->companies_count > 0 ? (($partner->companies_count * 100) / $partner->creditors_quota) : 0, 2) }}
                        </x-table.td>
                        @php
                            $totalAmount = $partner->total_yn_transactions_amount + $partner->total_membership_transactions_amount;
                            $partnerTotalAmount = $partner->total_yn_transaction_partner_revenue + $partner->total_membership_transactions_partner_revenue;
                            $ynTotalAmount = $totalAmount - $partnerTotalAmount;
                        @endphp
                        <x-table.td>{{ Number::currency((float) $totalAmount) }}</x-table.td>
                        <x-table.td>{{ Number::currency((float) $partnerTotalAmount) }}</x-table.td>
                        <x-table.td>{{ Number::currency((float) $ynTotalAmount) }}</x-table.td>
                        <x-table.td>
                            <x-menu>
                                <x-menu.button class="hover:bg-slate-100 p-1 rounded-full">
                                    <x-heroicon-m-ellipsis-horizontal class="w-7" />
                                </x-menu.button>
                                <x-menu.items
                                    @close-menu-item.window="menuOpen = false"
                                    class="w-60"
                                >
                                    <x-menu.item
                                        x-on:click="copyToClipboard('{{ route('register', ['code' => $partner->registration_code]) }}'); menuOpen = false"
                                        wire:loading.class="opacity-50"
                                        wire:loading.attr="disabled"
                                    >
                                        <x-lucide-link class="w-5 mr-3" />
                                        <span>{{ __('Copy Partnership Link') }}</span>
                                    </x-menu.item>
                                    <x-menu.item
                                        wire:click="exportMembers({{ $partner->id }})"
                                        wire:target="exportMembers({{ $partner->id }})"
                                        wire:loading.class="opacity-50"
                                        wire:loading.attr="disabled"
                                    >
                                        <x-lucide-loader-2
                                            class="animate-spin size-5"
                                            wire:loading
                                            wire:target="exportMembers({{ $partner->id }})"
                                        />
                                        <x-lucide-download
                                            class="w-5 mr-3"
                                            wire:loading.remove
                                            wire:target="exportMembers({{ $partner->id }})"
                                        />
                                        <span>{{ __('List of Current Members') }}</span>
                                    </x-menu.item>
                                    <a
                                        wire:navigate
                                        href="{{ route('super-admin.manage-partners.edit', $partner->id) }}"
                                    >
                                        <x-menu.item>
                                            <x-heroicon-o-pencil-square class="w-5 mr-3" />
                                            <span>{{ __('Edit') }}</span>
                                        </x-menu.item>
                                    </a>
                                </x-menu.items>
                            </x-menu>
                        </x-table.td>
                    </x-table.tr>
                @empty
                    <x-table.no-items-found :colspan="14" />
                @endforelse
            </x-slot>
        </x-table>
        <x-table.per-page :items="$partners" />
    </div>

    @script
        <script>
            Alpine.data('partner_link', () => ({
                copyToClipboard(url) {
                    navigator.clipboard.writeText(url).then(() => {
                        this.$notification({ text: @js(__('Partner link copied!')) })
                    })
                }
            }))
        </script>
    @endscript
</div>

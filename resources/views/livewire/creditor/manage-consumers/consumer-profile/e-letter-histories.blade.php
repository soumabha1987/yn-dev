@use('App\Enums\Role')
@use('App\Enums\ELetterType')
@use('App\Enums\TemplateCustomField')

<div>
    <div x-on:refresh-parent.window="$wire.$refresh">
        <div
            @class([
                'flex flex-wrap sm:flex-nowrap p-4 sm:items-center gap-4',
                'justify-between' => $eLetters->isNotEmpty(),
                'justify-start' => $eLetters->isEmpty()
            ])
        >
            <h2 class="text-black tracking-wide font-semibold text-lg">
                <span>{{ __('E-Letters Histories') }}</span>
            </h2>
            <x-table.per-page-count :items="$eLetters" />
        </div>

        <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
            <x-table>
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th>{{ __('Created At') }}</x-table.th>
                        <x-table.th>{{ __('Eletter Id') }}</x-table.th>
                        @role(Role::SUPERADMIN)
                            <x-table.th>{{ __('Company Name') }}</x-table.th>
                        @endrole
                        @hasanyrole([Role::SUPERADMIN, Role::CREDITOR])
                            <x-table.th>{{ __('Subclient Name') }}</x-table.th>
                        @endhasanyrole
                        <x-table.th>{{ __('Read By Consumer') }}</x-table.th>
                        <x-table.th>{{ __('Enabled') }}</x-table.th>
                        <x-table.th>{{ __('View') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse ($eLetters as $eLetter)
                        <x-table.tr>
                            <x-table.td>{{ $eLetter->created_at->formatWithTimezone() }}</x-table.td>
                            <x-table.td>{{ $eLetter->e_letter_id }}</x-table.td>
                            @role(Role::SUPERADMIN)
                                <x-table.td>{{ $eLetter->eLetter->company?->company_name ?? 'N/A' }}</x-table.td>
                            @endrole
                            @hasanyrole([Role::SUPERADMIN, Role::CREDITOR])
                                <x-table.td>{{ $eLetter->eLetter->subclient?->subclient_name ?? 'N/A' }}</x-table.td>
                            @endhasanyrole
                            <x-table.td>
                                <span @class([
                                    'badge rounded-md p-2',
                                    'bg-success/20 text-success' => $eLetter->read_by_consumer,
                                    'bg-error/20 text-error' => ! $eLetter->read_by_consumer,
                                ])>
                                    {{ $eLetter->read_by_consumer ? 'Read' : 'Unread' }}
                                </span>
                            </x-table.td>
                            <x-table.td>
                                <span @class([
                                    'badge rounded-md p-2',
                                    'bg-success/20 text-success' => $eLetter->enabled,
                                    'bg-error/20 text-error' => ! $eLetter->enabled,
                                ])>
                                    {{ $eLetter->enabled ? 'Enabled' : 'Disabled' }}
                                </span>
                            </x-table.td>
                            <x-table.td>
                                @if (in_array($eLetter->eLetter->type, [ELetterType::CFPB_WITHOUT_QR, ELetterType::CFPB_WITH_QR]))
                                    <x-form.button
                                        type="button"
                                        variant="success"
                                        wire:click="downloadCFPBLetter({{ $eLetter }})"
                                        wire:target="downloadCFPBLetter({{ $eLetter }})"
                                        wire:loading.attr="disabled"
                                        class="text-xs sm:text-sm+ py-1.5 px-3 text-nowrap flex items-center gap-3"
                                    >
                                        <x-lucide-loader-2
                                            wire:loading
                                            wire:target="downloadCFPBLetter({{ $eLetter }})"
                                            class="size-5 animate-spin"
                                        />
                                        <span>{{ __('Download CFPB Letter') }}</span>
                                    </x-form.button>
                                @else
                                    <x-dialog>
                                        <x-dialog.open>
                                            <x-form.button 
                                                class="text-xs sm:text-sm+ py-1.5 px-3 text-nowrap"
                                                type="button" 
                                                variant="success"
                                            >
                                                {{ __('View') }}
                                            </x-form.button>
                                        </x-dialog.open>
                                        <x-dialog.panel size="xl">
                                            <x-slot name="heading">{{ __('View Eco letter') }}</x-slot>
                                            <x-creditor.email.preview
                                                :content="TemplateCustomField::swapContent($consumer, $eLetter->eLetter->message)"
                                            />
                                            <div class="mt-5">
                                                <x-slot name="footer">
                                                    <x-dialog.close>
                                                        <x-form.default-button type="button">
                                                            {{ __('Close') }}
                                                        </x-form.default-button>
                                                    </x-dialog.close>
                                                </x-slot>
                                            </div>
                                        </x-dialog.panel>
                                    </x-dialog>
                                @endif
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="6" />
                    @endforelse
                </x-slot>
            </x-table>
        </div>
        <x-table.per-page :items="$eLetters" />
    </div>
</div>

@use('App\Enums\Role')
@use('App\Enums\TemplateType')

<div
    class="card mt-8"
    x-on:refresh-list-view.window="$wire.$refresh"
>
    <div
        @class([
            'flex flex-col sm:flex-row p-4 sm:items-center gap-4',
            'justify-between' => $eLetters->isNotEmpty(),
            'justify-end' => $eLetters->isEmpty()
        ])
    >
        <x-table.per-page-count :items="$eLetters" />
        <div class="sm:flex items-center space-x-3">
            <x-search-box
                placeholder="{{ __('Search') }}"
                :description="__('You can search by name')"
                wire:model.live.debounce.400="search"
            />
        </div>
    </div>
    <div class="min-w-full overflow-auto">
        <x-table>
            <x-slot name="tableHead">
                <x-table.tr>
                    <x-table.th column="created-on" :$sortAsc :$sortCol>{{ __('Created On') }}</x-table.th>
                    <x-table.th column="template-name" :$sortAsc :$sortCol>{{ __('Template Name') }}</x-table.th>
                    @hasrole (Role::SUPERADMIN)
                        <x-table.th column="type" :$sortAsc :$sortCol>{{ __('Type') }}</x-table.th>
                    @endhasrole
                    <x-table.th column="created-by" :$sortAsc :$sortCol>{{ __('Created By') }}</x-table.th>
                    <x-table.th class="lg:w-1/12">{{ __('Actions') }}</x-table.th>
                </x-table.tr>
            </x-slot>
            <x-slot name="tableBody">
                @forelse ($eLetters as $eLetter)
                    <x-table.tr>
                        <x-table.td>{{ $eLetter->created_at->formatWithTimezone() }}</x-table.td>
                        <x-table.td>{{ $eLetter->name }}</x-table.td>
                        @hasrole (Role::SUPERADMIN)
                            <x-table.td>{{ $eLetter->type }}</x-table.td>
                        @endhasrole
                        <x-table.td>{{ $eLetter->user->name }}</x-table.td>
                        <x-table.td>
                            <div class="flex items-center">
                                <div class="mr-2">
                                    <x-dialog>
                                        <x-dialog.open>
                                            <x-form.button
                                                variant="primary"
                                                type="button"
                                                class="text-xs sm:text-sm+ px-3 py-1.5"
                                            >
                                            <x-lucide-eye class="size-4.5 sm:size-5 mr-1"/>
                                                {{ __('Preview') }}
                                            </x-form.button>
                                        </x-dialog.open>
                                        <x-dialog.panel size="xl">
                                            <x-slot name="heading">
                                                {{ __(':name Preview', ['name' => $eLetter->type->displayName()]) }}
                                            </x-slot>
                                            <div>
                                                @if (in_array($eLetter->type, [TemplateType::EMAIL, TemplateType::E_LETTER]))
                                                    <x-creditor.email.preview
                                                        :subject="$eLetter->subject"
                                                        :content="$eLetter->description"
                                                        :from="null"
                                                    />
                                                @endif

                                                @if ($eLetter->type === TemplateType::SMS)
                                                    <x-creditor.sms.preview
                                                        :content="$eLetter->description"
                                                    />
                                                @endif
                                            </div>
                                            <x-slot name="footer" class="mt-3">
                                                <x-dialog.close>
                                                    <x-form.default-button type="button">
                                                        {{ __('Close') }}
                                                    </x-form.default-button>
                                                </x-dialog.close>
                                            </x-slot>
                                        </x-dialog.panel>
                                    </x-dialog>
                                </div>
                                <div class="mr-2">
                                    <x-form.button
                                        type="button"
                                        wire:click="$parent.edit({{ $eLetter }})"
                                        variant="info"
                                        class="text-xs sm:text-sm+ px-3 py-1.5"
                                    >
                                    <x-heroicon-o-pencil-square class="size-4.5 sm:size-5 mr-1"/>
                                        {{ __('Edit') }}
                                    </x-form.button>
                                </div>
                                <div class="mr-2">
                                    <x-confirm-box
                                        :ok-button-label="__('Delete')"
                                        action="delete({{ $eLetter->id }})"
                                    >
                                        <x-slot name="message">
                                            @hasrole(Role::SUPERADMIN)
                                                {{ __('Are you sure you want to delete this template?') }}
                                            @else
                                                {{ __('Are you sure you want to delete this e-letter?') }}
                                            @endhasrole
                                        </x-slot>

                                        <x-form.button
                                            type="button"
                                            variant="error"
                                            class="text-xs sm:text-sm+ px-3 py-1.5"
                                        >
                                            <x-heroicon-o-trash class="size-4.5 sm:size-5 mr-1"/>
                                            <span>{{ __('Delete') }}</span>
                                        </x-form.button>
                                    </x-confirm-box>
                                </div>
                            </div>
                        </x-table.td>
                    </x-table.tr>
                @empty
                    <x-table.no-items-found :colspan="7" />
                @endforelse
            </x-slot>
        </x-table>
        <x-table.per-page :items="$eLetters" />
    </div>
</div>

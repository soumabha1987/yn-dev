@use('App\Enums\AutomatedTemplateType')

<div>
    <div class="card">
        <div
            @class([
                'flex flex-col sm:flex-row p-4 sm:items-center gap-4',
                'justify-between' => $automatedTemplates->isNotEmpty(),
                'justify-end' => $automatedTemplates->isEmpty()
            ])
        >
            <x-table.per-page-count :items="$automatedTemplates" />
            <div class="flex flex-col sm:flex-row items-start sm:items-center w-full sm:w-auto gap-3">
                <a
                    wire:navigate
                    href="{{ route('super-admin.automated-templates.create') }}"
                    class="btn text-white bg-primary hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
                >
                    <div class="flex gap-x-1 items-center">
                        <x-lucide-circle-plus class="size-5"/>
                        <span>{{ __('Create') }}</span>
                    </div>
                </a>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center w-full sm:w-auto">
                    <x-search-box
                        name="search"
                        wire:model.live.debounce.400="search"
                        placeholder="{{ __('Search') }}"
                        :description="__('You can search by its name and type.')"
                    />
                </div>
            </div>
        </div>

        <div class="min-w-full overflow-auto">
            <x-table>
                <x-slot name="tableHead">
                    <x-table.tr>
                        <x-table.th column="name" :$sortCol :$sortAsc>{{ __('Name') }}</x-table.th>
                        <x-table.th column="type" :$sortCol :$sortAsc class="lg:w-1/12">{{ __('Type') }}</x-table.th>
                        <x-table.th column="subject" :$sortCol :$sortAsc>{{ __('Subject') }}</x-table.th>
                        <x-table.th class="lg:w-1/6">{{ __('Actions') }}</x-table.th>
                    </x-table.tr>
                </x-slot>
                <x-slot name="tableBody">
                    @forelse($automatedTemplates as $key => $automatedTemplate)
                        <x-table.tr>
                            <x-table.td>
                                <span
                                    x-tooltip.placement.right="@js(str($automatedTemplate->name)->replace("'", '')->toString())"
                                    class="hover:underline cursor-pointer"
                                >
                                    {{ (string) str($automatedTemplate->name)->words(3) }}
                                </span>
                            </x-table.td>
                            <x-table.td>{{ $automatedTemplate->type }}</x-table.td>
                            <x-table.td @class(['text-error' => ! $automatedTemplate->subject])>
                                <span
                                    @if ($automatedTemplate->subject)
                                        x-tooltip.placement.right="@js($automatedTemplate->subject)"
                                    class="hover:underline cursor-pointer"
                                    @endif
                                >
                                    {{ $automatedTemplate->subject ? (string) str($automatedTemplate->subject)->words(3) : 'N / A' }}
                                </span>
                            </x-table.td>
                            <x-table.td class="text-center">
                                <div class="flex space-x-2">
                                    <x-dialog>
                                        <x-dialog.open>
                                            <x-form.button
                                                type="button"
                                                variant="success"
                                                class="text-xs sm:text-sm+ px-3 py-1.5"
                                            >
                                                <div class="flex space-x-1 items-center">
                                                    <x-heroicon-o-eye class="size-4.5 sm:size-5 text-white"/>
                                                    <span>{{ __('View') }}</span>
                                                </div>
                                            </x-form.button>
                                        </x-dialog.open>

                                        <x-dialog.panel size="xl">
                                            <x-slot name="heading">
                                                {{ __(':name Preview', [
                                                    'name' => $automatedTemplate->type->name === 'SMS'
                                                        ? $automatedTemplate->type->name
                                                        : (string) str($automatedTemplate->type->name)->title(),
                                                ]) }}
                                            </x-slot>
                                            <div>
                                                @if ($automatedTemplate->type === AutomatedTemplateType::EMAIL)
                                                    <x-creditor.email.preview
                                                        :subject="$automatedTemplate->subject"
                                                        :content="$automatedTemplate->content"
                                                        :from="null"
                                                    />
                                                @endif

                                                @if ($automatedTemplate->type === AutomatedTemplateType::SMS)
                                                    <x-creditor.sms.preview
                                                        :content="$automatedTemplate->content"
                                                    />
                                                @endif
                                            </div>
                                            <x-slot name="footer" class="mt-5">
                                                <x-dialog.close>
                                                    <x-form.default-button type="button">
                                                        {{ __('Close') }}
                                                    </x-form.default-button>
                                                </x-dialog.close>
                                            </x-slot>
                                        </x-dialog.panel>
                                    </x-dialog>
                                    <a
                                        wire:navigate
                                        href="{{ route('super-admin.automated-templates.edit', $automatedTemplate->id) }}"
                                        class="text-xs sm:text-sm+ px-3 py-1.5 btn text-white bg-info hover:bg-info-focus focus:bg-info-focus active:bg-info-focus/90"
                                    >
                                        <div class="flex space-x-1 items-center">
                                            <x-heroicon-o-pencil-square class="size-4.5 sm:size-5"/>
                                            <span>{{ __('Edit') }}</span>
                                        </div>
                                    </a>
                                    <x-confirm-box
                                        :message="__('Are you sure you want to delete this automated template?')"
                                        :ok-button-label="__('Delete')"
                                        action="delete({{ $automatedTemplate->id }})"
                                    >
                                        <x-form.button
                                            class="text-xs sm:text-sm+ px-3 py-1.5"
                                            type="button"
                                            variant="error"
                                        >
                                            <div class="flex space-x-1 items-center">
                                                <x-heroicon-o-trash class="size-4.5 sm:size-5"/>
                                                <span>{{ __('Delete') }}</span>
                                            </div>
                                        </x-form.button>
                                    </x-confirm-box>
                                </div>
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.no-items-found :colspan="5"/>
                    @endforelse
                </x-slot>
            </x-table>
        </div>
        <x-table.per-page :items="$automatedTemplates"/>
    </div>
</div>

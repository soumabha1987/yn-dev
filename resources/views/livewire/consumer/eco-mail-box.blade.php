@use('App\Enums\ConsumerStatus')
@use('App\Enums\ELetterType')
@use('App\Enums\TemplateCustomField')

<div>
    <main class="max-w-full sm:max-w-4xl mx-auto pb-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-y-4">
            <div class="flex-col items-center">
                <span class="flex items-center gap-2 text-lg text-black font-semibold lg:text-2xl">
                    {{ __('MyEcoMailBox') }}
                    <span class="badge py-1.5 text-purple-600 bg-purple-100 rounded-full">{{ $unreadCount. __(' unread') }}</span>
                </span>
                <span>{{ __('View your secure ecoMail. we protect you from any marketing and/or junk mail, always!') }}</span>
            </div>
        </div>

        <div class="mt-3">
            <div @class([
                'flex flex-col sm:flex-row items-start sm:items-center gap-3',
                'justify-between' => $consumerELetters->isNotEmpty(),
                'justify-end' => ! $consumerELetters->isNotEmpty(),
            ])>
                @if ($consumerELetters->isNotEmpty())
                    <div>
                        <x-table.per-page-count :items="$consumerELetters" />
                    </div>
                @endif
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center w-full sm:w-auto gap-4">
                    <div class="text-base font-medium tracking-wide text-slate-700">
                        <label class="flex items-center space-x-2">
                            <span class="text-xs text-black">{{ __('Only Show Unread') }}</span>
                            <input
                                type="checkbox"
                                wire:model.live="only_read_by_consumer"
                                class="form-switch h-5 w-10 rounded-full bg-slate-300 before:rounded-full before:bg-gray-50 checked:bg-primary checked:before:bg-white"
                            >
                        </label>
                    </div>
                    <div class="flex items-center">
                        <label class="relative flex w-full">
                            <input
                                wire:model.live.debounce.400="search"
                                class="form-input peer h-9 w-full sm:w-44 rounded-full border border-slate-300 bg-transparent px-3 py-2 pl-9 text-xs+ placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary"
                                placeholder="{{ __('Search here...') }}"
                                autocomplete="off"
                            />
                            <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary">
                                <x-lucide-search class="size-5" />
                            </span>
                            <div
                                x-show="$wire.search"
                                class="absolute right-0 flex h-full w-10 items-center justify-center hover:text-error hover:cursor-pointer"
                                x-tooltip.placement.right="@js(__('Clear'))"
                                wire:click="$set('search', '')"
                            >
                                <x-lucide-x class="size-3.5 transition-colors duration-200" />
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-3 is-scrollbar-hidden min-w-full overflow-x-auto">
                <x-table>
                    <x-slot name="tableHead" class="border-x">
                        <x-table.tr>
                            <x-table.th column="created-at" :$sortCol :$sortAsc class="lg:w-1/6">{{ __('Date') }}</x-table.th>
                            <x-table.th column="company-name" :$sortCol :$sortAsc>{{ __('Sender') }}</x-table.th>
                            <x-table.th column="account-offer" :$sortCol :$sortAsc>{{ __('Account Offer') }}</x-table.th>
                            <x-table.th class="lg:w-1/12">{{ __('Actions') }}</x-table.th>
                        </x-table.tr>
                    </x-slot>
                    <x-slot name="tableBody" class="border-x text-nowrap">
                        @forelse ($consumerELetters as $key => $consumerELetter)
                            <x-table.tr
                                x-data="{ openPreviewDialog{{ $key }}: false }"
                                wire:loading
                                wire:loading.class="!table-row"
                                wire:target.except="delete, downloadCFPBLetter"
                            >
                                <x-table.td class="sm:px-5 lg:w-1/6">
                                    <div class="skeleton animate-wave px-4 py-3 sm:px-5 rounded bg-slate-150"></div>
                                </x-table.td>
                                <x-table.td class="sm:px-5">
                                    <div class="skeleton animate-wave px-4 py-3 sm:px-5 rounded bg-slate-150"></div>
                                </x-table.td>
                                <x-table.td class="sm:px-5">
                                    <div class="skeleton animate-wave px-4 py-3 sm:px-5 rounded bg-slate-150"></div>
                                </x-table.td>
                                <x-table.td class="sm:px-5 lg:w-1/12">
                                    <div class="skeleton animate-wave px-4 py-3 sm:px-5 rounded bg-slate-150"></div>
                                </x-table.td>
                            </x-table.tr>
                            <x-table.tr
                                x-data="{ openPreviewDialog{{ $key }}: false }"
                                wire:loading.remove
                                wire:target.except="delete, downloadCFPBLetter"
                                @class([
                                    'cursor-pointer border-y border-slate-200 !text-xs sm:!text-base',
                                    'font-bold' => ! $consumerELetter->read_by_consumer
                                ])
                            >
                                <x-table.td class="sm:px-5">{{ $consumerELetter->created_at->format('M d, Y') }}</x-table.td>
                                <x-table.td class="sm:px-5">
                                    @if (in_array($consumerELetter->eLetter->type, [ELetterType::CFPB_WITHOUT_QR, ELetterType::CFPB_WITH_QR]))
                                        <span
                                            class="flex space-x-4 items-center gap-4 text-primary hover:underline cursor-pointer"
                                            wire:click="downloadCFPBLetter({{ $consumerELetter->id }})"
                                        >
                                            {{ $consumerELetter->eLetter->company->company_name }}
                                            <x-lucide-loader-2 class="animate-spin size-5" wire:loading wire:target="downloadCFPBLetter({{ $consumerELetter->id }})" />
                                        </span>
                                    @else
                                        <x-dialog x-model="openPreviewDialog{{ $key }}">
                                            <x-dialog.open>
                                                <span
                                                    class="flex space-x-4 items-center gap-4 text-primary hover:underline cursor-pointer"
                                                    wire:click="readByConsumer({{ $consumerELetter->id }})"
                                                >
                                                    {{ $consumerELetter->eLetter->company->company_name }}
                                                </span>
                                            </x-dialog.open>
                                            <x-dialog.panel
                                                size="xl"
                                                :heading="__('Preview')"
                                            >
                                                <x-creditor.email.preview
                                                    :subject="null"
                                                    :content="TemplateCustomField::swapContent($consumerELetter->consumer, $consumerELetter->eLetter->message)"
                                                    :from="null"
                                                />
                                            </x-dialog.panel>
                                        </x-dialog>
                                    @endif
                                </x-table.td>
                                <x-table.td class="sm:px-5">
                                    @if (in_array($consumerELetter->consumer->status, [ConsumerStatus::JOINED, ConsumerStatus::UPLOADED, ConsumerStatus::RENEGOTIATE]))
                                        <a
                                            wire:navigate
                                            href="{{ route('consumer.negotiate', $consumerELetter->consumer->id) }}"
                                            class="btn text-xs sm:text-sm+ px-3 py-1.5 bg-success text-center text-nowrap font-medium text-white hover:bg-success-focus focus:bg-success-focus active:bg-success-focus/90"
                                        >
                                            {{ __('View My Offer') }}
                                        </a>
                                    @else -
                                    @endif
                                </x-table.td>
                                <x-table.td class="sm:px-5">
                                    <div
                                        @class([
                                            'flex items-center justify-end',
                                            'justify-between' => in_array($consumerELetter->consumer->status, [ConsumerStatus::JOINED, ConsumerStatus::UPLOADED, ConsumerStatus::RENEGOTIATE]),
                                        ])
                                    >
                                        <x-menu>
                                            <x-menu.button class="hover:bg-slate-100 p-1 rounded-full">
                                                <x-heroicon-m-ellipsis-vertical class="size-7 text-slate-500" />
                                            </x-menu.button>
                                            <x-menu.items>
                                                @if (in_array($consumerELetter->consumer->status, [ConsumerStatus::JOINED, ConsumerStatus::UPLOADED, ConsumerStatus::RENEGOTIATE]))
                                                    <x-menu.item>
                                                        <x-lucide-eye class="size-5"/>
                                                        <a
                                                            wire:navigate
                                                            href="{{ route('consumer.negotiate', $consumerELetter->consumer) }}"
                                                            class="flex items-center gap-2"
                                                        >
                                                            {{ __('View My Offer') }}
                                                        </a>
                                                    </x-menu.item>
                                                @endif
                                                @if (in_array($consumerELetter->eLetter->type, [ELetterType::CFPB_WITHOUT_QR, ELetterType::CFPB_WITH_QR]))
                                                    <x-menu.item
                                                        wire:click="downloadCFPBLetter({{ $consumerELetter->id }})"
                                                        wire:target="downloadCFPBLetter({{ $consumerELetter->id }})"
                                                        wire:loading.attr="disabled"
                                                        class="flex items-center gap-3"
                                                    >
                                                        <x-lucide-loader-2
                                                            wire:loading
                                                            wire:target="downloadCFPBLetter({{ $consumerELetter->id }})"
                                                            class="size-5 animate-spin"
                                                        />
                                                        <x-heroicon-m-arrow-down-tray
                                                            wire:loading.remove
                                                            wire:target="downloadCFPBLetter({{ $consumerELetter->id }})"
                                                            class="size-5"
                                                        />
                                                        <span>{{ __('Download') }}</span>
                                                    </x-menu.item>
                                                @else
                                                    <x-menu.close>
                                                        <x-menu.item
                                                            x-on:click="openPreviewDialog{{ $key }} = true"
                                                            class="flex items-center gap-2"
                                                            wire:click="readByConsumer({{ $consumerELetter->id }})"
                                                        >
                                                            <x-lucide-mail-open class="size-5"/>
                                                            {{ __('Open Letter') }}
                                                        </x-menu.item>
                                                    </x-menu.close>
                                                @endif
                                                <x-confirm-box
                                                    :message="__('Are you sure you want to delete this eco mail?')"
                                                    :ok-button-label="__('Delete')"
                                                    action="delete({{ $consumerELetter->id }})"
                                                >
                                                    <x-menu.close>
                                                        <x-menu.item>
                                                            <x-heroicon-o-trash class="size-5" />
                                                            <span>{{ __('Delete') }}</span>
                                                        </x-menu.item>
                                                    </x-menu.close>
                                                </x-confirm-box>
                                            </x-menu.items>
                                        </x-menu>
                                    </div>
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.no-items-found :colspan="4" class="!border !border-t-0 !border-solid !border-slate-200" />
                        @endforelse
                    </x-slot>
                </x-table>
            </div>
            <x-table.per-page :items="$consumerELetters" />
        </div>
    </main>
</div>

@props([
    'paginationOptions' => [10, 30, 50],
    'items',
])

@if ($items->isNotEmpty())
    <div>
        <div class="flex items-center space-x-2 text-sm+">
            <span>{{ __('Show') }}</span>
            <label class="block">
                <select
                    wire:model.live="perPage"
                    class="form-select rounded-full border border-slate-300 bg-white px-2 py-1 pr-6 hover:border-slate-400 focus:border-primary"
                >
                    @foreach ($paginationOptions as $paginationOption)
                        <option value="{{ $paginationOption }}">{{ $paginationOption }}</option>
                    @endforeach
                </select>
            </label>
            <span>{{ __('entries') }}</span>
        </div>
    </div>
@endif

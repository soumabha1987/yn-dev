<div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        @foreach (range(1, 5) as $index)
            <div class="card rounded-2xl px-4 py-8 sm:px-5">
                <div class="flex flex-col h-full justify-between">
                    <div class="flex space-x-3 items-start">
                        <div class="mask is-hexagon size-14 rounded items-center justify-center">
                            <div class="skeleton animate-wave h-full w-32 rounded bg-slate-150"></div>
                        </div>
                        <div class="skeleton animate-wave h-8 w-full rounded bg-slate-150"></div>
                    </div>
                    <div class="py-4">
                        <div class="skeleton animate-wave h-8 w-full rounded bg-slate-150"></div>
                    </div>
                    <div>
                        <div class="skeleton animate-wave h-8 w-32 rounded bg-slate-150"></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

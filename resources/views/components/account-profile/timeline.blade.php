<div>
    <div
        x-data="{ isStuck: false }"
        class="my-2"
    >
        <div
            x-show="$store.breakpoints.isXs"
            x-intersect:enter.full.margin.-0.0.0.0="isStuck = false"
            x-intersect:leave.full.margin.-0.0.0.0="isStuck = true"
        >
        </div>

        <div x-bind:class="$store.breakpoints.isXs && isStuck && 'fixed right-0 top-0 w-full z-40'">
            <div
                class="transition-all duration-200 overflow-x-auto is-scrollbar-hidden"
                x-bind:class="$store.breakpoints.isXs && isStuck && 'py-2.5 px-8 bg-white shadow-lg relative'"
            >
                <ol class="steps pt-1">
                    @foreach ($steps as $index => $componentName)
                        @if (in_array($componentName, $this->completedSteps))
                            <li
                                class="step before:bg-success cursor-pointer"
                                wire:click="switchStep({{ $index }})"
                            >
                                <div @class([
                                    'step-header rounded-full bg-success text-white',
                                    'outline outline-offset-2 outline-primary !bg-primary' => $componentName === $currentStep,
                                ])>
                                    <x-heroicon-c-check class="w-5"/>
                                </div>
                                <h3
                                    @class([
                                        'text-base text-black select-none',
                                        'font-bold text-black' => $componentName === $currentStep,
                                    ])
                                >
                                    {{ $this->cardTitle($componentName) }}
                                </h3>
                            </li>
                        @else
                            <li
                                @if ($index === count($this->completedSteps) && array_search($currentStep, $steps) !== $index)
                                    wire:click="switchStep({{ $index }})"
                                @endif
                                @class([
                                    'step before:bg-slate-200',
                                    'cursor-pointer' => $index === count($this->completedSteps) && array_search($currentStep, $steps) !== $index,
                                ])
                            >
                                <div
                                    @class([
                                        'step-header rounded-full select-none',
                                        'cursor-pointer' => $index === count($this->completedSteps),
                                        'bg-primary text-white outline outline-offset-2 outline-primary' => array_search($currentStep, $steps) === $index,
                                        'bg-slate-200 text-black' => array_search($currentStep, $steps) !== $index,
                                    ])
                                >
                                    {{ $loop->index + 1 }}
                                </div>
                                <h3
                                    @class([
                                        'text-base text-black select-none',
                                        'font-bold text-black' => array_search($currentStep, $steps) === $index,
                                    ])
                                >
                                    {{ $this->cardTitle($componentName) }}
                                </h3>
                            </li>
                        @endif
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
</div>

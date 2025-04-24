<div>
    <div>
        <div x-effect="if(! visible && localStorage.getItem('dashboardWarningModal') === 'true') isDisplayWarningModal = true">
            <x-dashboard.warning-modal
                x-model="isDisplayWarningModal"
                :step1-is-completed="$step1IsCompleted"
                :step2-is-completed="$step2IsCompleted"
                :step3-is-completed="$step3IsCompleted"
                :step4-is-completed="$step4IsCompleted"
                :step5-is-completed="$step5IsCompleted"
                :step6-is-completed="$step6IsCompleted"
                :step7-is-completed="$step7IsCompleted"
                :step8-is-completed="$step8IsCompleted"
            />
        </div>
    </div>
    <div class="card">
        <div class="py-4 px-4 sm:px-5">
            <div>
                <h4 class="text-lg font-medium text-slate-700">
                    {{ __('Getting Started') }}
                </h4>
                <div
                    x-data="{ expandedItem: 'item-1' }"
                    class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-10"
                >
                    <div class="w-full">
                        <div class="mt-3 flex w-full flex-col divide-y divide-indigo-400 overflow-hidden rounded-lg border border-primary">
                            <div x-data="accordionItem('item-1')">
                                <div @click="expanded = !expanded"
                                     class="flex cursor-pointer items-center justify-between bg-primary px-4 py-4 text-base font-medium text-white">
                                    <p>{{ __('Question 1') }}</p>
                                    <div :class="expanded && '-rotate-180'"
                                         class="text-sm font-normal leading-none text-indigo-100 transition-transform duration-300">
                                        <x-lucide-chevron-up class="size-5 text-white"/>
                                    </div>
                                </div>
                                <div x-collapse x-show="expanded">
                                    <div class="px-4 py-4 sm:px-5">
                                        <p>
                                            Lorem ipsum dolor sit amet, consectetur adipisicing
                                            elit. Commodi earum magni officiis possimus
                                            repellendus. Accusantium adipisci aliquid praesentium
                                            quaerat voluptate.
                                        </p>
                                        <div class="mt-2 flex space-x-2">
                                            <a href="#"
                                               class="tag rounded-full border border-primary text-primary">
                                                Tag 1
                                            </a>
                                            <a href="#"
                                               class="tag rounded-full border border-primary text-primary">
                                                Tag 2
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div x-data="accordionItem('item-2')">
                                <div @click="expanded = !expanded"
                                     class="flex cursor-pointer items-center justify-between bg-primary px-4 py-4 text-base font-medium text-white">
                                    <p>{{ __('Question 2') }}</p>
                                    <div :class="expanded && '-rotate-180'"
                                         class="text-sm font-normal leading-none text-indigo-100 transition-transform duration-300">
                                        <x-lucide-chevron-up class="size-5 text-white"/>
                                    </div>
                                </div>
                                <div x-collapse x-show="expanded">
                                    <div class="px-4 py-4 sm:px-5">
                                        <p>
                                            Lorem ipsum dolor sit amet, consectetur adipisicing
                                            elit. Commodi earum magni officiis possimus
                                            repellendus. Accusantium adipisci aliquid praesentium
                                            quaerat voluptate.
                                        </p>
                                        <div class="mt-2 flex space-x-2">
                                            <a href="#"
                                               class="tag rounded-full border border-primary text-primary">
                                                Tag 1
                                            </a>
                                            <a href="#"
                                               class="tag rounded-full border border-primary text-primary">
                                                Tag 2
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div x-data="accordionItem('item-3')">
                                <div @click="expanded = !expanded"
                                     class="flex cursor-pointer items-center justify-between bg-primary px-4 py-4 text-base font-medium text-white ">
                                    <p>{{ __('Question 3') }}</p>
                                    <div :class="expanded && '-rotate-180'"
                                         class="text-sm font-normal leading-none text-indigo-100 transition-transform duration-300">
                                        <x-lucide-chevron-up class="size-5 text-white"/>
                                    </div>
                                </div>
                                <div x-collapse x-show="expanded">
                                    <div class="px-4 py-4 sm:px-5">
                                        <p>
                                            Lorem ipsum dolor sit amet, consectetur adipisicing
                                            elit. Commodi earum magni officiis possimus
                                            repellendus. Accusantium adipisci aliquid praesentium
                                            quaerat voluptate.
                                        </p>
                                        <div class="mt-2 flex space-x-2">
                                            <a href="#"
                                               class="tag rounded-full border border-primary text-primary">
                                                Tag 1
                                            </a>
                                            <a href="#"
                                               class="tag rounded-full border border-primary text-primary">
                                                Tag 2
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div x-data="accordionItem('item-4')">
                                <div @click="expanded = !expanded"
                                     class="flex cursor-pointer items-center justify-between bg-primary px-4 py-4 text-base font-medium text-white">
                                    <p>{{ __('Question 4') }}</p>
                                    <div :class="expanded && '-rotate-180'"
                                         class="text-sm font-normal leading-none text-indigo-100 transition-transform duration-300">
                                        <x-lucide-chevron-up class="size-5 text-white"/>
                                    </div>
                                </div>
                                <div x-collapse x-show="expanded">
                                    <div class="px-4 py-4 sm:px-5">
                                        <p>
                                            Lorem ipsum dolor sit amet, consectetur adipisicing
                                            elit. Commodi earum magni officiis possimus
                                            repellendus. Accusantium adipisci aliquid praesentium
                                            quaerat voluptate.
                                        </p>
                                        <div class="mt-2 flex space-x-2">
                                            <a href="#"
                                               class="tag rounded-full border border-primary text-primary">
                                                Tag 1
                                            </a>
                                            <a href="#"
                                               class="tag rounded-full border border-primary text-primary">
                                                Tag 2
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="w-full">
                        <div class="mt-3 flex w-full flex-col divide-y divide-indigo-400 overflow-hidden rounded-lg border border-primary">
                            <div x-data="accordionItem('item-5')">
                                <div @click="expanded = !expanded"
                                     class="flex cursor-pointer items-center justify-between bg-primary px-4 py-4 text-base font-medium text-white">
                                    <p>{{ __('Question 5') }}</p>
                                    <div :class="expanded && '-rotate-180'"
                                         class="text-sm font-normal leading-none text-indigo-100 transition-transform duration-300">
                                        <x-lucide-chevron-up class="size-5 text-white"/>
                                    </div>
                                </div>
                                <div x-collapse x-show="expanded">
                                    <div class="px-4 py-4 sm:px-5">
                                        <p>
                                            Lorem ipsum dolor sit amet, consectetur adipisicing
                                            elit. Commodi earum magni officiis possimus
                                            repellendus. Accusantium adipisci aliquid praesentium
                                            quaerat voluptate.
                                        </p>
                                        <div class="mt-2 flex space-x-2">
                                            <a href="#"
                                               class="tag rounded-full border border-primary text-primary">
                                                Tag 1
                                            </a>
                                            <a href="#"
                                               class="tag rounded-full border border-primary text-primary">
                                                Tag 2
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div x-data="accordionItem('item-6')">
                                <div @click="expanded = !expanded"
                                     class="flex cursor-pointer items-center justify-between bg-primary px-4 py-4 text-base font-medium text-white">
                                    <p>{{ __('Question 6') }}</p>
                                    <div :class="expanded && '-rotate-180'"
                                         class="text-sm font-normal leading-none text-indigo-100 transition-transform duration-300">
                                        <x-lucide-chevron-up class="size-5 text-white"/>
                                    </div>
                                </div>
                                <div x-collapse x-show="expanded">
                                    <div class="px-4 py-4 sm:px-5">
                                        <p>
                                            Lorem ipsum dolor sit amet, consectetur adipisicing
                                            elit. Commodi earum magni officiis possimus
                                            repellendus. Accusantium adipisci aliquid praesentium
                                            quaerat voluptate.
                                        </p>
                                        <div class="mt-2 flex space-x-2">
                                            <a href="#"
                                               class="tag rounded-full border border-primary text-primary">
                                                Tag 1
                                            </a>
                                            <a href="#"
                                               class="tag rounded-full border border-primary text-primary">
                                                Tag 2
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div x-data="accordionItem('item-7')">
                                <div @click="expanded = !expanded"
                                     class="flex cursor-pointer items-center justify-between bg-primary px-4 py-4 text-base font-medium text-white">
                                    <p>{{ __('Question 7') }}</p>
                                    <div :class="expanded && '-rotate-180'"
                                         class="text-sm font-normal leading-none text-indigo-100 transition-transform duration-300">
                                        <x-lucide-chevron-up class="size-5 text-white"/>
                                    </div>
                                </div>
                                <div x-collapse x-show="expanded">
                                    <div class="px-4 py-4 sm:px-5">
                                        <p>
                                            Lorem ipsum dolor sit amet, consectetur adipisicing
                                            elit. Commodi earum magni officiis possimus
                                            repellendus. Accusantium adipisci aliquid praesentium
                                            quaerat voluptate.
                                        </p>
                                        <div class="mt-2 flex space-x-2">
                                            <a href="#"
                                               class="tag rounded-full border border-primary text-primary">
                                                Tag 1
                                            </a>
                                            <a href="#"
                                               class="tag rounded-full border border-primary text-primary">
                                                Tag 2
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div x-data="accordionItem('item-8')">
                                <div @click="expanded = !expanded"
                                     class="flex cursor-pointer items-center justify-between bg-primary px-4 py-4 text-base font-medium text-white">
                                    <p>{{ __('Question 8') }}</p>
                                    <div :class="expanded && '-rotate-180'"
                                         class="text-sm font-normal leading-none text-indigo-100 transition-transform duration-300">
                                        <x-lucide-chevron-up class="size-5 text-white"/>
                                    </div>
                                </div>
                                <div x-collapse x-show="expanded">
                                    <div class="px-4 py-4 sm:px-5">
                                        <p>
                                            Lorem ipsum dolor sit amet, consectetur adipisicing
                                            elit. Commodi earum magni officiis possimus
                                            repellendus. Accusantium adipisci aliquid praesentium
                                            quaerat voluptate.
                                        </p>
                                        <div class="mt-2 flex space-x-2">
                                            <a href="#"
                                               class="tag rounded-full border border-primary text-primary">
                                                Tag 1
                                            </a>
                                            <a href="#"
                                               class="tag rounded-full border border-primary text-primary">
                                                Tag 2
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@script
    <script>
        Alpine.data('accordionItem', (id) => ({
            accordion_id: id,
            get expanded() {
                return this.expandedItem === this.accordion_id;
            },
            set expanded(val) {
                this.expandedItem = val ? this.accordion_id : null;
            }
        }))
    </script>
@endscript

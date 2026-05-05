<div
    x-show="tour.active"
    x-cloak
    class="fixed inset-0 z-50 bg-black/50 flex items-end sm:items-center justify-center p-4"
    @keydown.escape.window="tour.finish()"
>
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 relative">
        {{-- Close button --}}
        <button @click="tour.finish()"
                class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 transition-colors"
                aria-label="Close tour">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        {{-- Step title --}}
        <p class="text-xs text-indigo-500 font-semibold uppercase tracking-wide mb-1"
           x-text="tour.current.title"></p>

        {{-- Step body --}}
        <p class="text-gray-700 text-sm leading-relaxed mb-5"
           x-text="tour.current.body"></p>

        {{-- Progress dots --}}
        <div class="flex items-center justify-between">
            <div class="flex gap-1.5">
                <template x-for="(s, i) in tour.steps" :key="i">
                    <span class="w-2 h-2 rounded-full transition-colors"
                          :class="i === tour.step ? 'bg-indigo-600' : 'bg-gray-300'"></span>
                </template>
            </div>

            {{-- Navigation buttons --}}
            <div class="flex gap-2">
                <button
                    x-show="tour.step > 0"
                    @click="tour.prev()"
                    class="text-gray-500 hover:text-gray-700 text-sm px-3 py-1.5 rounded-lg border border-gray-200 hover:border-gray-300 transition-colors">
                    ← Back
                </button>
                <a
                    :href="tour.current.href"
                    @click="tour.next()"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-1.5 rounded-lg transition-colors"
                    x-text="tour.isLast ? 'Finish' : 'Go →'">
                </a>
            </div>
        </div>
    </div>
</div>

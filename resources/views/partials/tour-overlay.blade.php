<div
    x-show="tour.active"
    x-cloak
    class="fixed inset-0 z-60 flex items-end justify-center bg-base/70 p-4 backdrop-blur-sm sm:items-center"
    @keydown.escape.window="tour.finish()"
    role="dialog" aria-modal="true" aria-label="Feature tour"
>
    <div class="card relative w-full max-w-sm p-6 shadow-2xl">
        <button @click="tour.finish()"
                class="absolute right-3 top-3 cursor-pointer rounded-md p-1 text-muted transition-colors duration-200 hover:bg-raised hover:text-ink"
                aria-label="Close tour">
            <x-icon name="close" class="h-5 w-5" />
        </button>

        <p class="mb-2 font-mono text-xs uppercase tracking-[0.2em] text-accent" x-text="tour.current.title"></p>
        <p class="mb-5 text-sm leading-relaxed text-muted" x-text="tour.current.body"></p>

        <div class="flex items-center justify-between">
            <div class="flex gap-1.5" aria-hidden="true">
                <template x-for="(s, i) in tour.steps" :key="i">
                    <span class="h-1.5 w-1.5 rounded-full transition-colors duration-200"
                          :class="i === tour.step ? 'bg-accent' : 'bg-line'"></span>
                </template>
            </div>

            <div class="flex gap-2">
                <button x-show="tour.step > 0" @click="tour.prev()" class="btn-ghost">Back</button>
                <a :href="tour.current.href" @click="tour.next()" class="btn-primary py-2! text-sm"
                   x-text="tour.isLast ? 'Finish' : 'Go'"></a>
            </div>
        </div>
    </div>
</div>

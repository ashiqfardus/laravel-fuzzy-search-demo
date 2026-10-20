{{-- The start card is a modal over the dashboard; on a stop's own page the card docks to a
     corner with no backdrop, so the page it describes stays usable. --}}
<div
    x-show="tour.active"
    x-cloak
    class="fixed inset-0 z-60 flex p-4"
    :class="tour.docked ? 'pointer-events-none items-end justify-end' : 'items-end justify-center bg-base/70 backdrop-blur-sm sm:items-center'"
    @keydown.escape.window="tour.active && !tour.docked && tour.finish()"
    x-effect="tour.active && $nextTick(() => $refs.tourPrimary.focus())"
>
    <div class="card pointer-events-auto w-full max-w-sm p-6 shadow-2xl"
         role="dialog" :aria-modal="tour.docked ? 'false' : 'true'" aria-labelledby="tour-title"
         @keydown.escape="tour.finish()">
        <p id="tour-title" class="mb-2 font-mono text-xs uppercase tracking-[0.2em] text-accent" x-text="tour.current.title"></p>
        <p class="mb-5 text-sm leading-relaxed text-muted" x-text="tour.current.body"></p>

        <div class="flex items-center justify-between gap-3">
            <div class="flex gap-1.5" aria-hidden="true">
                <template x-for="(s, i) in tour.steps" :key="i">
                    <span class="h-1.5 w-1.5 rounded-full transition-colors duration-200"
                          :class="i === tour.step ? 'bg-accent' : 'bg-line'"></span>
                </template>
            </div>

            <div class="flex gap-2">
                <button type="button" @click="tour.finish()" class="btn-ghost" aria-label="End tour">End</button>
                <button type="button" x-ref="tourPrimary" class="btn-primary py-2! text-sm"
                        @click="tour.docked && tour.here ? tour.next() : tour.go()"
                        x-text="!tour.docked || !tour.here ? 'Go' : (tour.isLast ? 'Finish' : 'Next')"></button>
            </div>
        </div>
    </div>
</div>

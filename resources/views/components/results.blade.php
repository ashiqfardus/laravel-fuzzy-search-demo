@props(['title' => 'Results', 'count' => null, 'meta' => null])
<section {{ $attributes->merge(['class' => 'card overflow-hidden']) }}>
    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-3.5 sm:px-6">
        <h2 class="font-mono text-xs font-semibold uppercase tracking-[0.15em] text-muted">{{ $title }}</h2>
        <div class="flex items-center gap-2">
            {{ $meta ?? '' }}
            @isset($count)
                <span class="badge bg-raised text-ink">{{ $count }} {{ \Illuminate\Support\Str::plural('result', $count) }}</span>
            @endisset
        </div>
    </header>
    {{ $slot }}
</section>

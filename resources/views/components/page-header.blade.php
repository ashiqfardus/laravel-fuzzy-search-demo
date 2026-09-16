@props(['title', 'eyebrow' => null])
<div {{ $attributes->merge(['class' => 'mb-8']) }}>
    @if($eyebrow)
        <p class="mb-2 font-mono text-xs uppercase tracking-[0.2em] text-accent">{{ $eyebrow }}</p>
    @endif
    <h1 class="font-mono text-2xl font-semibold tracking-tight text-ink sm:text-3xl">{{ $title }}</h1>
    @if(trim($slot) !== '')
        <p class="mt-3 max-w-2xl leading-relaxed text-muted">{{ $slot }}</p>
    @endif
</div>

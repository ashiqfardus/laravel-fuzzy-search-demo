@props(['tone' => 'info', 'title' => null])
@php
    $styles = [
        'info'    => ['border-sky-500/30 bg-sky-500/10 text-sky-100',       'text-sky-300',   'info'],
        'warn'    => ['border-amber-500/30 bg-amber-500/10 text-amber-100', 'text-amber-300', 'warn'],
        'success' => ['border-accent/30 bg-accent/10 text-emerald-100',      'text-accent',    'check'],
        'danger'  => ['border-red-500/30 bg-red-500/10 text-red-100',       'text-red-300',   'x-circle'],
    ];
    [$box, $iconColor, $icon] = $styles[$tone] ?? $styles['info'];
@endphp
<div {{ $attributes->merge(['class' => "flex gap-3 rounded-xl border p-4 text-sm leading-relaxed $box"]) }} role="{{ $tone === 'danger' ? 'alert' : 'note' }}">
    <x-icon :name="$icon" class="mt-0.5 h-5 w-5 shrink-0 {{ $iconColor }}" />
    <div class="min-w-0 flex-1">
        @if($title)<p class="mb-1 font-semibold">{{ $title }}</p>@endif
        {{ $slot }}
    </div>
</div>

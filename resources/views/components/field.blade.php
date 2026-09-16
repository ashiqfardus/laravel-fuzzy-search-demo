@props(['label', 'for', 'hint' => null])
<div {{ $attributes }}>
    <label for="{{ $for }}" class="mb-1.5 block text-sm font-medium text-ink">{{ $label }}</label>
    {{ $slot }}
    @if($hint)
        <p class="mt-1.5 text-xs leading-relaxed text-muted">{{ $hint }}</p>
    @endif
</div>

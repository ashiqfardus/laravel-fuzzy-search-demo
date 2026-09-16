@props(['icon' => 'inbox'])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-2 px-6 py-12 text-center']) }}>
    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-raised text-muted">
        <x-icon :name="$icon" class="h-5 w-5" />
    </span>
    <p class="text-ink">{{ $slot }}</p>
    @isset($hint)
        <p class="max-w-md text-sm text-muted">{{ $hint }}</p>
    @endisset
</div>

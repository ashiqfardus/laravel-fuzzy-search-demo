@props(['button' => 'Search', 'cols' => 'md:grid-cols-4', 'engine' => null])
<form method="GET" {{ $attributes->merge(['class' => 'card mb-8 p-5 sm:p-6']) }} x-data="{ loading: false, engine: @js($engine) }" @submit="loading = true" @pageshow.window="if ($event.persisted) loading = false">
    <div class="grid gap-4 {{ $cols }}">
        {{ $slot }}
    </div>
    <div class="mt-5 flex flex-wrap items-center gap-3">
        <button type="submit" class="btn-primary" :disabled="loading">
            <svg x-show="loading" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
            <span x-text="loading ? 'Searching…' : @js($button)">{{ $button }}</span>
        </button>
        {{ $actions ?? '' }}
    </div>
</form>

@props(['model'])
<x-callout tone="warn" title="Index not built" {{ $attributes }}>
    The BM25 index holds no rows for this model yet, so the LIKE table scan answered. Build the index once:
    <span class="code">php artisan fuzzy-search:rebuild "{{ $model }}"</span>
</x-callout>

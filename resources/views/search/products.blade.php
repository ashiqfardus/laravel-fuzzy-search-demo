@extends('layouts.app')

@section('title', 'Product search — Fuzzy Search Demo')

@php
    $categoryName = $category !== '' ? $categories->firstWhere('id', (int) $category)?->name : null;
@endphp

@section('content')
<x-page-header eyebrow="Presets · filters · BM25 index" title="Product search">
    The BM25 index answers by default. It is typo-tolerant — try <span class="code">iphne</span> — and it honours the category filter. Column weights count too: a term in the product name (weight 10) outranks the same term found only in the description (weight 5) — try <span class="code">macbook</span>. Config presets set several search options at once: on the LIKE table scan (<span class="code">?engine=like</span>) they pick the algorithm, and their typo tolerance holds on the index too, so Exact turns the index's typo matching off.
</x-page-header>

<div class="card mb-8 p-5 sm:p-6" x-data="{
    q: '',
    rows: [],
    meta: null,
    loading: false,
    controller: null,
    async asYouType() {
        if (this.q.length < 2) { this.rows = []; this.meta = null; return; }
        this.loading = true;
        if (this.controller) this.controller.abort();
        this.controller = new AbortController();
        try {
            const resp = await fetch('/api/as-you-type?q=' + encodeURIComponent(this.q), { signal: this.controller.signal });
            const payload = await resp.json();
            this.rows = payload.data ?? [];
            this.meta = payload.meta ?? null;
        } catch (e) {
            if (e.name !== 'AbortError') { this.rows = []; this.meta = null; }
        } finally {
            this.loading = false;
        }
    }
}">
    <label for="as-you-type" class="mb-1.5 block text-sm font-medium text-ink">
        As you type — BM25 index <span class="text-muted">(prefix expansion of the last word, typo tolerant)</span>
    </label>
    <div class="relative">
        <input id="as-you-type" type="text" x-model="q" @input.debounce.200ms="asYouType()"
               class="field-control pr-10" placeholder="Type three letters, e.g. macb, ipho, samsu…" autocomplete="off">
        <span x-show="loading" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 text-accent" aria-hidden="true">
            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
        </span>
    </div>
    <ul x-show="rows.length > 0" x-cloak class="mt-3 divide-y divide-line overflow-hidden rounded-xl border border-line" aria-label="As-you-type matches">
        <template x-for="row in rows" :key="row.id ?? row.name">
            <li class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm">
                <div class="min-w-0">
                    <p class="truncate font-medium text-ink">
                        <span x-show="row._highlighted?.name" x-cloak x-html="row._highlighted?.name"></span>
                        <span x-show="!row._highlighted?.name" x-cloak x-text="row.name ?? '—'"></span>
                    </p>
                    <p class="truncate font-mono text-xs text-muted" x-text="row.brand ?? ''"></p>
                </div>
                <span class="badge bg-accent/15 text-accent" x-text="Number(row._score ?? 0).toFixed(4)"></span>
            </li>
        </template>
    </ul>
    <p x-show="!loading && q.length >= 2 && rows.length === 0" x-cloak class="mt-3 text-sm text-muted">
        No index matches yet — keep typing, or run <span class="code">php artisan fuzzy-search:rebuild "App\Models\Product"</span>.
    </p>
    <p x-show="meta" x-cloak class="mt-3 font-mono text-xs text-muted">
        <span x-text="meta?.algorithm"></span> · <span x-text="meta?.latency_ms"></span> ms
    </p>
</div>

<x-search-form button="Search products" cols="md:grid-cols-5">
    <x-field label="Search query" for="q" class="md:col-span-2">
        <input id="q" type="text" name="q" value="{{ $query }}" placeholder="Try: iphne, macbok, samsng…" class="field-control" autofocus>
    </x-field>
    <x-field label="Preset" for="preset">
        <select id="preset" name="preset" class="field-control">
            <option value="ecommerce" @selected($preset === 'ecommerce')>E-commerce (typo tolerant)</option>
            <option value="exact" @selected($preset === 'exact')>Exact (no typos)</option>
        </select>
    </x-field>
    <x-field label="Category" for="category">
        <select id="category" name="category" class="field-control">
            <option value="" @selected($category === '')>All categories</option>
            @foreach($categories as $option)
                <option value="{{ $option->id }}" @selected($category === (string) $option->id)>{{ $option->name }}</option>
            @endforeach
        </select>
    </x-field>
    <x-engine-field :engine="$engine" model="App\Models\Product" />
</x-search-form>

@if($query && $engine === 'bm25' && !$useIndex)
    <x-index-not-built model="App\Models\Product" class="mb-8" />
@elseif($query && $useIndex && $results->isNotEmpty())
    <x-callout tone="success" class="mb-8">
        Answered by the <strong>BM25 index</strong>{{ $categoryName ? ' with the “' . $categoryName . '” filter applied before the page was cut' : '' }}.
    </x-callout>
@endif

@if($query)
    <x-results :count="$results->count()">
        @if($results->isEmpty())
            <x-empty icon="bag">
                No products match “{{ $query }}”{{ $categoryName ? ' in “' . $categoryName . '”' : '' }}.
                @if($useIndex && $category !== '')
                    <x-slot:hint>Nothing in this category matches. <a href="{{ request()->fullUrlWithQuery(['category' => null]) }}" class="text-link underline">Search all categories</a>.</x-slot:hint>
                @elseif($useIndex)
                    <x-slot:hint><x-index-miss /></x-slot:hint>
                @else
                    <x-slot:hint>Try the E-commerce preset, clear the category, or switch the engine.</x-slot:hint>
                @endif
            </x-empty>
        @else
            <div class="grid gap-4 p-4 sm:p-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach($results as $product)
                    <article class="flex flex-col gap-2 rounded-xl border border-line bg-base/40 p-4 transition-colors duration-200 hover:border-accent/50">
                        <div class="flex items-center justify-between gap-2">
                            <span class="badge bg-raised text-muted">{{ $product->category?->name }}</span>
                            @isset($product->_score)
                                <span class="badge bg-accent/15 text-accent" title="Relevance score"><span class="sr-only">Relevance score </span>{{ number_format($product->_score, 1) }}</span>
                            @endisset
                        </div>
                        <h3 class="font-medium text-ink">@fuzzyHighlight($product, 'name')</h3>
                        <p class="text-sm text-muted">{{ $product->brand }} · <span class="font-mono">{{ $product->sku }}</span></p>
                        <p class="mt-auto font-mono text-lg font-semibold text-ink">${{ number_format($product->price, 2) }}</p>
                    </article>
                @endforeach
            </div>
        @endif
    </x-results>
@endif
@endsection

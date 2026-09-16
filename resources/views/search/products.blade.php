@extends('layouts.app')

@section('title', 'Product search — Fuzzy Search Demo')

@section('content')
<x-page-header eyebrow="Presets · filters · BM25 fallback" title="Product search">
    Config presets tune eight parameters at once. The category filter is honoured on the BM25 index path, and the fuzzy fallback answers when the index has no match.
</x-page-header>

<x-search-form button="Search products" cols="md:grid-cols-5">
    <x-field label="Search query" for="q" class="md:col-span-2">
        <input id="q" type="text" name="q" value="{{ $query }}" placeholder="Try: iphone, macbok, samsng…" class="field-control" autofocus>
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
                <option value="{{ $option }}" @selected($category === $option)>{{ $option }}</option>
            @endforeach
        </select>
    </x-field>
    <x-field label="Engine" for="engine" hint='Build the index once: php artisan fuzzy-search:rebuild "App\Models\Product"'>
        <select id="engine" name="engine" class="field-control">
            <option value="like" @selected($engine === 'like')>LIKE patterns</option>
            <option value="bm25" @selected($engine === 'bm25')>BM25 index → fuzzy fallback</option>
        </select>
    </x-field>
</x-search-form>

@if($query && count($attempts) > 1)
    <x-callout tone="warn" title="Fallback used" class="mb-8">
        <code class="code">{{ implode(' → ', $attempts) }}</code> —
        <strong>{{ $attempts[0] }}</strong> returned nothing{{ $attempts[0] === 'bm25' ? ' (no index match, or the products index is not built)' : '' }},
        so the same search was re-run with <strong>{{ last($attempts) }}</strong>{{ $category !== '' ? ', still filtered to “' . $category . '”' : '' }}.
    </x-callout>
@elseif($query && $engine === 'bm25' && count($attempts) === 1)
    <x-callout tone="success" class="mb-8">
        Answered by the <strong>BM25 index</strong>{{ $category !== '' ? ' with the “' . $category . '” filter applied before the page was cut' : '' }}; the fuzzy fallback was not needed.
    </x-callout>
@endif

@if($query)
    <x-results :count="$results->count()">
        @if($results->isEmpty())
            <x-empty icon="bag">
                No products match “{{ $query }}”{{ $category !== '' ? ' in “' . $category . '”' : '' }}.
                <x-slot:hint>Try the E-commerce preset, clear the category, or switch the engine.</x-slot:hint>
            </x-empty>
        @else
            <div class="grid gap-4 p-4 sm:p-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach($results as $product)
                    <article class="flex flex-col gap-2 rounded-xl border border-line bg-base/40 p-4 transition-colors duration-200 hover:border-accent/50">
                        <div class="flex items-center justify-between gap-2">
                            <span class="badge bg-raised text-muted">{{ $product->category }}</span>
                            @isset($product->_score)
                                <span class="badge bg-accent/15 text-accent" title="Relevance score">{{ number_format($product->_score, 1) }}</span>
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

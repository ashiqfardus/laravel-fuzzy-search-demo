@extends('layouts.app')

@section('title', 'CJK search — Fuzzy Search Demo')

@php
    $hints = ['東京', '烤鸭', 'ヘッドホン', '김치', 'tokyo'];
@endphp

@section('content')
<x-page-header eyebrow="Script-aware tokenizer · BM25 index" title="CJK search">
    Chinese, Japanese and Korean product names have no spaces between words, so the index cuts character bigrams for those runs instead — <span class="code">東京タワー</span> becomes <span class="code">東京</span>, <span class="code">京タ</span>, <span class="code">タワ</span>, <span class="code">ワー</span>. Every other script still splits on whitespace, byte-identical to before.
</x-page-header>

<x-search-form button="Search" cols="md:grid-cols-1">
    <x-field label="Search query" for="q">
        <input id="q" type="text" name="q" value="{{ $query }}" placeholder="Try: 東京, 烤鸭, ヘッドホン, 김치, tokyo…" class="field-control" autofocus>
    </x-field>
</x-search-form>

<div class="mb-8 flex flex-wrap gap-2">
    @foreach($hints as $hint)
        <a href="?q={{ urlencode($hint) }}" class="rounded-lg border border-line bg-base/40 px-3 py-1.5 font-mono text-sm text-ink transition-colors duration-200 hover:border-accent/50 hover:bg-raised">{{ $hint }}</a>
    @endforeach
</div>

@if($query)
    <x-results :count="$results->count()">
        @if($results->isEmpty())
            <x-empty icon="squares">
                No products match “{{ $query }}”.
                <x-slot:hint>Try one of the terms above, or rebuild the index: <span class="code">php artisan fuzzy-search:rebuild "App\Models\Product" --fresh</span></x-slot:hint>
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
                        <p class="text-sm text-muted">@fuzzyHighlight($product, 'description')</p>
                        <p class="mt-auto font-mono text-xs text-muted">{{ $product->brand }} · <span class="font-mono">{{ $product->sku }}</span></p>
                    </article>
                @endforeach
            </div>
        @endif
    </x-results>

    <details class="card mt-6 p-5">
        <summary class="cursor-pointer font-mono text-xs uppercase tracking-[0.15em] text-muted transition-colors duration-200 hover:text-ink">Index terms queried</summary>
        <pre class="mt-4 overflow-x-auto font-mono text-xs leading-relaxed text-link">{{ json_encode($indexTerms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </details>
@endif
@endsection

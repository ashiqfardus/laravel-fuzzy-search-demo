@extends('layouts.app')

@section('title', 'Article search — Fuzzy Search Demo')

@section('content')
<x-page-header eyebrow="Tokenization · blog preset · BM25 index" title="Article search">
    Multi-word queries are split into tokens. Match any token (OR) for recall, or require every token (AND) for precision — on the BM25 index too, which answers by default. The LIKE table scan runs the blog preset's fuzzy patterns over every article's title, body and excerpt instead; it is one click away when the index finds nothing.
</x-page-header>

<x-search-form button="Search articles" cols="md:grid-cols-4">
    <x-field label="Search query" for="q" class="md:col-span-2">
        <input id="q" type="text" name="q" value="{{ $query }}" placeholder="Try: laravel search fuzzy…" class="field-control" autofocus>
    </x-field>
    <x-field label="Match mode" for="match_mode">
        <select id="match_mode" name="match_mode" class="field-control">
            <option value="any" @selected($matchMode === 'any')>Match any token (OR)</option>
            <option value="all" @selected($matchMode === 'all')>Match all tokens (AND)</option>
        </select>
    </x-field>
    <x-engine-field :engine="$engine" model="App\Models\Article" />
</x-search-form>

@if($query && $engine === 'bm25' && !$useIndex)
    <x-index-not-built model="App\Models\Article" class="mb-8" />
@elseif($query && $useIndex && $results->isNotEmpty())
    <x-callout tone="success" class="mb-8">
        Answered by the <strong>BM25 index</strong>.
    </x-callout>
@endif

@if($query)
    <x-results :count="$results->count()">
        @if($results->isEmpty())
            <x-empty icon="document">
                No articles match “{{ $query }}”.
                @if($useIndex)
                    <x-slot:hint><x-index-miss /></x-slot:hint>
                @elseif($matchMode === 'all')
                    <x-slot:hint>Switch to “match any” to widen the search.</x-slot:hint>
                @else
                    <x-slot:hint>Check the spelling, or search the BM25 index, which forgives typos.</x-slot:hint>
                @endif
            </x-empty>
        @else
            <ul class="divide-y divide-line">
                @foreach($results as $article)
                    @php
                        $status = ['published' => 'bg-accent/15 text-accent', 'draft' => 'bg-amber-500/15 text-amber-300'][$article->status] ?? 'bg-raised text-muted';
                    @endphp
                    <li class="row flex-col items-stretch gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <span class="badge {{ $status }}">{{ ucfirst($article->status) }}</span>
                            @isset($article->_score)
                                <span class="badge bg-accent/15 text-accent" title="Relevance score"><span class="sr-only">Relevance score </span>{{ number_format($article->_score, 2) }}</span>
                            @endisset
                        </div>
                        <h3 class="text-lg font-medium text-ink">@fuzzyHighlight($article, 'title')</h3>
                        <p class="text-sm leading-relaxed text-muted">{{ Str::limit($article->excerpt ?? $article->body, 150) }}</p>
                        <p class="text-xs text-muted">By {{ $article->author?->name }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-results>
@endif
@endsection

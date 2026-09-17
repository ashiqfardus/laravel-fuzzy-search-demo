@extends('layouts.app')

@section('title', 'Article search — Fuzzy Search Demo')

@section('content')
<x-page-header eyebrow="Tokenization · blog preset" title="Article search">
    Multi-word queries are split into tokens. Match any token (OR) for recall, or require every token (AND) for precision.
</x-page-header>

<x-search-form button="Search articles" cols="md:grid-cols-3">
    <x-field label="Search query" for="q" class="md:col-span-2">
        <input id="q" type="text" name="q" value="{{ $query }}" placeholder="Try: laravel search fuzzy…" class="field-control" autofocus>
    </x-field>
    <x-field label="Match mode" for="match_mode">
        <select id="match_mode" name="match_mode" class="field-control">
            <option value="any" @selected($matchMode === 'any')>Match any token (OR)</option>
            <option value="all" @selected($matchMode === 'all')>Match all tokens (AND)</option>
        </select>
    </x-field>
</x-search-form>

@if($query)
    <x-results :count="$results->count()">
        @if($results->isEmpty())
            <x-empty icon="document">
                No articles match “{{ $query }}”.
                <x-slot:hint>Switch to “match any” to widen the search.</x-slot:hint>
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
                                <span class="badge bg-accent/15 text-accent" title="Relevance score">{{ number_format($article->_score, 2) }}</span>
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

@extends('layouts.app')

@section('title', 'Relationship search — Fuzzy Search Demo')

@section('content')
<x-page-header eyebrow="whereHas · dotted columns" title="Relationship search">
    Articles belong to an <span class="code">Author</span>. Adding <span class="code">'author.name'</span> to <span class="code">searchIn()</span> compiles to a <span class="code">whereHas</span>/EXISTS clause, and a match on the author's name is scored and highlighted under that dotted key — no join, no N+1.
</x-page-header>

<x-search-form button="Search articles" cols="md:grid-cols-1">
    <x-field label="Search query" for="q">
        <input id="q" type="text" name="q" value="{{ $query }}" placeholder="Try an article title, or an author's name…" class="field-control" autofocus>
    </x-field>
</x-search-form>

<x-callout tone="info" title="What ran" class="mb-8">
    <pre class="overflow-x-auto font-mono text-xs leading-relaxed">Article::search($query)
    ->searchIn(['title' => 10, 'excerpt' => 3, 'author.name' => 6])
    ->using('fuzzy')
    ->withRelevance()
    ->highlight('mark')
    ->paginate(10);</pre>
</x-callout>

@if($query)
    <x-results :count="$results->total()">
        @if($results->isEmpty())
            <x-empty icon="document">
                No articles match “{{ $query }}”, by title or by author.
                <x-slot:hint>Try a partial author name — it compiles to the same whereHas clause.</x-slot:hint>
            </x-empty>
        @else
            <ul class="divide-y divide-line">
                @foreach($results as $article)
                    <li class="row flex-col items-stretch gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <span class="badge bg-raised text-muted">Article</span>
                            @isset($article->_score)
                                <span class="badge bg-accent/15 text-accent" title="Relevance score">{{ number_format($article->_score, 2) }}</span>
                            @endisset
                        </div>
                        <h3 class="text-lg font-medium text-ink">@fuzzyHighlight($article, 'title')</h3>
                        <p class="text-sm leading-relaxed text-muted">{{ Str::limit($article->excerpt ?? $article->body, 150) }}</p>
                        <p class="text-xs text-muted">by @fuzzyHighlight($article, 'author.name')</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-results>
    <x-pager :paginator="$results" class="mt-4" />
@endif
@endsection

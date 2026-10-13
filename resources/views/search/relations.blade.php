@extends('layouts.app')

@section('title', 'Relationship search — Fuzzy Search Demo')

@section('content')
<x-page-header eyebrow="Dotted columns · BM25 index · whereHas" title="Relationship search">
    Articles belong to an <span class="code">Author</span>. Add <span class="code">'author.name'</span> to <span class="code">searchIn()</span> and a match on the author's name is scored and highlighted under that dotted key — no join, no N+1. The BM25 index holds the author's name with each article, so it answers by default; the LIKE table scan compiles the same column to a <span class="code">whereHas</span>/EXISTS clause.
</x-page-header>

<x-search-form button="Search articles" cols="md:grid-cols-3">
    <x-field label="Search query" for="q" class="md:col-span-2">
        <input id="q" type="text" name="q" value="{{ $query }}" placeholder="Try: laravel, smith…" class="field-control" autofocus>
    </x-field>
    <x-engine-field :engine="$engine" model="App\Models\Article" />
</x-search-form>

<x-callout tone="info" title="What ran" class="mb-8">
    <pre class="overflow-x-auto font-mono text-xs leading-relaxed">Article::search($query)
    ->searchIn(['title' => 10, 'excerpt' => 3, 'author.name' => 6])
    {{ $engine === 'bm25' ? "->useInvertedIndex()->fallback('fuzzy')" : "->using('fuzzy')" }}
    ->withRelevance()
    ->highlight('mark')
    ->paginate(10);</pre>
    @if($query && count($attempts) > 1)
        <p class="mt-2"><strong>Fallback used:</strong> <code class="code">{{ implode(' → ', $attempts) }}</code> — the index had no match (or the articles index is not built), so the fuzzy table scan answered.</p>
    @endif
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

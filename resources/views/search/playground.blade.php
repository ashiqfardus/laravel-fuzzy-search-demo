@extends('layouts.app')

@section('title', 'Query playground — Fuzzy Search Demo')

@section('content')
<x-page-header eyebrow="Extended syntax" title="Query playground">
    Fuse-style operators, compiled by the package's lexer → parser → SQL compiler. Type below for a live preview; submit for the full extended query and its parsed AST.
</x-page-header>

<div class="mb-6 flex flex-wrap gap-2 text-sm" aria-label="Operators">
    @foreach([['space', 'AND'], ['|', 'OR'], ['!word', 'NOT'], ['^word', 'prefix'], ['word$', 'suffix'], ['=word', 'exact field'], ["'word", 'include'], ['"a b"', 'phrase']] as [$op, $meaning])
        <span class="badge bg-raised py-1 text-muted"><span class="text-link">{{ $op }}</span> {{ $meaning }}</span>
    @endforeach
</div>

<form method="GET" class="card mb-8 p-5 sm:p-6" x-data="{
    query: @js($query),
    results: [],
    loading: false,
    controller: null,
    async suggest() {
        if (this.query.length < 2) { this.results = []; return; }
        this.loading = true;
        if (this.controller) this.controller.abort();
        this.controller = new AbortController();
        try {
            const resp = await fetch('/api/search/users?q=' + encodeURIComponent(this.query), { signal: this.controller.signal });
            this.results = await resp.json();
        } catch (e) {
            if (e.name !== 'AbortError') this.results = [];
        } finally {
            this.loading = false;
        }
    }
}" x-init="query.length >= 2 && suggest()">
    <label for="q" class="mb-1.5 block text-sm font-medium text-ink">Extended query</label>
    <div class="flex flex-col gap-3 sm:flex-row">
        <div class="relative flex-1">
            <input id="q" type="text" name="q" x-model="query" @input.debounce.400ms="suggest()"
                   class="field-control pr-10 font-mono" placeholder="^John Doe !banned" autocomplete="off">
            <span x-show="loading" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 text-accent" aria-hidden="true">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
            </span>
        </div>
        <button type="submit" class="btn-primary justify-center">Run query</button>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-muted">
        Examples:
        @foreach(['^John Doe', 'john | jane', '^John Doe !banned', '^Smith Doe$'] as $example)
            <a href="?q={{ urlencode($example) }}" class="code cursor-pointer transition-colors duration-200 hover:bg-line">{{ $example }}</a>
        @endforeach
    </div>

    <p x-show="!loading && results.length === 0 && query.length >= 2" x-cloak class="mt-4 text-sm text-muted">
        No live matches — run the query for the full extended search.
    </p>
    <ul x-show="results.length > 0" x-cloak class="mt-4 divide-y divide-line overflow-hidden rounded-xl border border-line" aria-label="Live preview">
        <template x-for="r in results" :key="r.id ?? r.name">
            <li class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm">
                <div class="min-w-0">
                    <p class="font-medium text-ink" x-text="r.name ?? r.title ?? r.full_name ?? '—'"></p>
                    <p class="truncate text-xs text-muted" x-text="r.email ?? ''"></p>
                </div>
                <span class="font-mono text-xs text-muted" x-text="r._score != null ? parseFloat(r._score).toFixed(4) : ''"></span>
            </li>
        </template>
    </ul>
</form>

@if($error)
    <x-callout tone="danger" title="Syntax error" class="mb-8">{{ $error }}</x-callout>
@endif

@if($ast)
    <details class="card mb-8 p-5">
        <summary class="cursor-pointer font-mono text-xs uppercase tracking-[0.15em] text-muted transition-colors duration-200 hover:text-ink">Parsed AST</summary>
        <pre class="mt-4 overflow-x-auto font-mono text-xs leading-relaxed text-link">{{ json_encode($ast, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </details>
@endif

@if($results->isNotEmpty())
    <x-results :count="$results->total()">
        <ul class="divide-y divide-line">
            @foreach($results as $r)
                <li class="row items-center">
                    <div class="min-w-0">
                        <p class="font-medium text-ink">@fuzzyHighlight($r, 'name')</p>
                        <p class="truncate text-sm text-muted">{{ $r->email }}</p>
                    </div>
                    <span class="font-mono text-xs tabular-nums text-muted">{{ number_format($r->_score ?? 0, 4) }}</span>
                </li>
            @endforeach
        </ul>
    </x-results>
    <x-pager :paginator="$results" class="mt-4" />
@elseif($query && !$error)
    <x-results :count="0">
        <x-empty icon="code">No rows matched the extended query.</x-empty>
    </x-results>
@endif
@endsection

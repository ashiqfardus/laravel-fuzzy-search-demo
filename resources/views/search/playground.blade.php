@extends('layouts.app')
@section('title', 'Extended Search Playground')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-5xl">
    <h1 class="text-3xl font-bold mb-2">Extended Search Playground</h1>
    <p class="text-gray-600 mb-3">
        Try Fuse-style operators. Whitespace&nbsp;=&nbsp;AND, <code>|</code>&nbsp;=&nbsp;OR, <code>!</code>&nbsp;=&nbsp;NOT,
        <code>^</code>&nbsp;=&nbsp;prefix, <code>$</code>&nbsp;=&nbsp;suffix,
        <code>=word</code>&nbsp;=&nbsp;<strong>exact</strong> (whole field must equal <em>word</em>), <code>'word</code>&nbsp;=&nbsp;include.
    </p>
    <p class="text-gray-500 text-sm mb-6">
        Examples:&nbsp;
        <a href="?q=^John+Doe" class="underline font-mono">^John Doe</a> &nbsp;·&nbsp;
        <a href="?q=john+|+jane" class="underline font-mono">john | jane</a> &nbsp;·&nbsp;
        <a href="?q=^John+Doe+!banned" class="underline font-mono">^John Doe !banned</a> &nbsp;·&nbsp;
        <a href="?q=^Smith+Doe$" class="underline font-mono">^Smith Doe$</a>
    </p>

    <form method="GET" x-data="{
    query: '{{ addslashes($query) }}',
    results: [],
    loading: false,
    controller: null,
    async suggest() {
        if (this.query.length < 2) { this.results = []; return; }
        this.loading = true;
        if (this.controller) this.controller.abort();
        this.controller = new AbortController();
        try {
            const resp = await fetch('/api/search/users?q=' + encodeURIComponent(this.query), {
                signal: this.controller.signal
            });
            this.results = await resp.json();
        } catch(e) {
            if (e.name !== 'AbortError') this.results = [];
        } finally {
            this.loading = false;
        }
    }
}" x-init="query.length >= 2 && suggest()">
    <div class="mb-4 flex gap-3">
        <input type="text"
               name="q"
               x-model="query"
               @input.debounce.400ms="suggest()"
               class="border rounded px-4 py-2 flex-1 font-mono"
               placeholder="^John Doe !banned — press Search or Enter for full results">
        <span x-show="loading" x-cloak class="self-center">
            <svg class="w-5 h-5 animate-spin text-indigo-600" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
        </span>
        <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-5 py-2 rounded-lg transition-colors">
            Search
        </button>
    </div>

    <div x-show="!loading && results.length === 0 && query.length >= 2" x-cloak
         class="text-gray-400 italic text-sm mb-4">No results — try pressing Search for the full extended query.</div>

    <ul x-show="results.length > 0" class="bg-white border rounded-lg overflow-hidden divide-y mb-4">
        <template x-for="r in results" :key="r.id ?? r.name">
            <li class="px-4 py-3 flex justify-between text-sm">
                <div>
                    <p class="font-medium" x-text="r.name ?? r.title ?? r.full_name ?? '—'"></p>
                    <p class="text-gray-500 text-xs" x-text="r.email ?? ''"></p>
                </div>
                <span class="text-gray-400 font-mono text-xs self-center"
                      x-text="r._score != null ? 'score: ' + parseFloat(r._score).toFixed(4) : ''"></span>
            </li>
        </template>
    </ul>
</form>

    @if($error)
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 mb-6">
            <strong>Error:</strong> {{ $error }}
        </div>
    @endif

    @if($ast)
        <details class="mb-6 bg-gray-50 border rounded-lg p-4">
            <summary class="cursor-pointer font-semibold">Show parsed AST</summary>
            <pre class="mt-3 text-xs overflow-x-auto">{{ json_encode($ast, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </details>
    @endif

    @if($results->isNotEmpty())
        <div class="bg-white border rounded-lg overflow-hidden">
            <div class="bg-gray-50 border-b px-4 py-3 font-semibold">{{ $results->count() }} results</div>
            <ul class="divide-y">
                @foreach($results as $r)
                <li class="px-4 py-3 flex justify-between text-sm">
                    <div>
                        <p class="font-medium">@fuzzyHighlight($r, 'name')</p>
                        <p class="text-gray-500">{{ $r->email }}</p>
                    </div>
                    <span class="text-gray-400 font-mono text-xs self-center">
                        score: {{ number_format($r->_score ?? 0, 4) }}
                    </span>
                </li>
                @endforeach
            </ul>
        </div>
    @elseif($query && !$error)
        <p class="text-gray-500 italic">No results matched the extended query.</p>
    @endif
</div>
@endsection

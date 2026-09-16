@extends('layouts.app')

@section('title', 'Compare algorithms — Fuzzy Search Demo')

@section('content')
<x-page-header eyebrow="Side by side" title="Compare algorithms">
    The same query through every algorithm at once. Scores are PHP-side relevance; higher is a better match.
</x-page-header>

<x-search-form button="Compare all" cols="md:grid-cols-1">
    <x-field label="Search term" for="q">
        <input id="q" type="text" name="q" value="{{ $term }}" placeholder="e.g. john, jhn, jon" class="field-control" autofocus>
    </x-field>
    <x-slot:actions>
        <a href="{{ route('search.capability-matrix') }}" class="btn-ghost">Reset</a>
    </x-slot:actions>
</x-search-form>

@if($term)
    <div class="mb-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach($algorithms as $algo)
            @php $data = $results[$algo]; @endphp
            <section class="card overflow-hidden">
                <header class="flex items-center justify-between gap-2 border-b border-line px-5 py-3.5">
                    <h2 class="font-mono text-sm font-semibold text-ink">{{ $algo }}</h2>
                    @if($data['error'])
                        <span class="badge bg-red-500/15 text-red-300">error</span>
                    @else
                        <div class="flex items-center gap-2">
                            <span class="badge bg-raised text-muted">{{ $data['rows']->count() }}</span>
                            <span class="badge bg-accent/15 text-accent">{{ $data['ms'] ?? '?' }} ms</span>
                        </div>
                    @endif
                </header>
                <div class="p-5">
                    @if($data['error'])
                        <p class="text-sm text-red-300">{{ $data['error'] }}</p>
                    @elseif($data['rows']->isEmpty())
                        <p class="text-sm text-muted">No results</p>
                        @if($algo === 'soundex')
                            <p class="mt-2 text-xs leading-relaxed text-muted">Soundex needs a true phonetic match (same sound code). Try <span class="code">john</span>, <span class="code">stephen</span> or <span class="code">steven</span>.</p>
                        @endif
                    @else
                        <ol class="space-y-2">
                            @foreach($data['rows'] as $rank => $row)
                                <li class="flex items-center justify-between gap-3 text-sm">
                                    <span class="flex items-center gap-3">
                                        <span class="w-4 text-right font-mono text-muted">{{ $rank + 1 }}</span>
                                        <span class="text-ink">{{ $row->name }}</span>
                                    </span>
                                    @isset($row->_score)
                                        <span class="font-mono text-xs tabular-nums text-muted">{{ number_format($row->_score, 1) }}</span>
                                    @endisset
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            </section>
        @endforeach
    </div>

    <x-callout tone="info" title="How to read this">
        Differences reveal how each algorithm handles typos, phonetics and substring matching. Zero Soundex results means no phonetically similar first name exists for that query — correct, not broken. The SQL each algorithm generates is documented in the
        <a href="https://github.com/ashiqfardus/laravel-fuzzy-search#readme" class="text-link underline" target="_blank" rel="noopener">package docs</a>.
    </x-callout>
@endif
@endsection

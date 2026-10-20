@extends('layouts.app')

@section('title', 'LIKE vs BM25 — Fuzzy Search Demo')

@section('content')
<x-page-header eyebrow="Latency · ordering" title="LIKE vs BM25">
    Same query, same dataset. LIKE fetches up to 1,000 candidates and re-scores them in PHP; BM25 reads the inverted index directly, so there is no full-table scan.
</x-page-header>

<x-search-form button="Run benchmark" cols="md:grid-cols-2">
    <x-field label="Search term" for="q">
        <input id="q" type="text" name="q" value="{{ $term }}" placeholder="e.g. john, laravel, smith" class="field-control" autofocus>
    </x-field>
    <x-field label="Typo tolerance on the index" for="typo" hint="0 = exact dictionary terms. Above 0 expands each term by edit distance.">
        <select id="typo" name="typo" class="field-control">
            @foreach([0, 1, 2, 3] as $level)
                <option value="{{ $level }}" @selected($typo === $level)>{{ $level === 0 ? 'Off (exact terms)' : $level.' edit'.($level === 1 ? '' : 's') }}</option>
            @endforeach
        </select>
    </x-field>
</x-search-form>

@if($term && !empty($results))
    <div class="mb-8 grid gap-4 md:grid-cols-2 @if(count($results) > 2) lg:grid-cols-3 @endif">
        @foreach($results as $key => $data)
            @php $rows = $data['rows'] ?? collect(); @endphp
            <section class="card overflow-hidden" data-bench-label="{{ $data['label'] }}" data-bench-ms="{{ $data['ms'] }}">
                <header class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-5 py-3.5">
                    <h2 class="font-mono text-sm font-semibold text-ink">{{ $data['label'] }}</h2>
                    <div class="flex items-center gap-2">
                        <span class="badge bg-raised text-muted">{{ $rows->count() }} rows</span>
                        <span class="badge bg-accent/15 text-accent">{{ $data['ms'] }} ms</span>
                    </div>
                </header>
                <div class="p-5">
                    @if(!empty($data['error']))
                        <x-callout tone="danger">{{ $data['error'] }}</x-callout>
                    @elseif($rows->isEmpty())
                        <p class="text-sm text-muted">No results</p>
                    @else
                        <ol class="space-y-2">
                            @foreach($rows as $i => $row)
                                <li class="flex items-center justify-between gap-3 text-sm">
                                    <span class="flex items-center gap-3">
                                        <span class="w-5 text-right font-mono text-muted">{{ $i + 1 }}</span>
                                        <span class="text-ink">{{ $row->name }}</span>
                                    </span>
                                    @isset($row->_score)
                                        <span class="font-mono text-xs tabular-nums text-muted">{{ number_format($row->_score, 4) }}</span>
                                    @endisset
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            </section>
        @endforeach
    </div>

    <x-callout tone="info" title="Reading this" class="mb-8">
        Each panel shows the latency measured for this term on this table; compare them, and the table size, before drawing a conclusion. The LIKE panel's scores are PHP-side relevance; the BM25 panel's are corpus-normalised. Turn typo tolerance up to see what dictionary expansion costs: the third panel runs the same index read with each term widened by edit distance.
    </x-callout>
@endif

{{-- Run history (Alpine + localStorage) --}}
<div x-data="benchmarkHistory()" x-init="load()" @bench-run-saved.window="load()" x-show="history.length > 0" x-cloak>
    <div class="mb-3 flex items-center justify-between">
        <h3 class="font-mono text-xs uppercase tracking-[0.15em] text-muted">Run history <span class="normal-case tracking-normal">(last 5)</span></h3>
        <button type="button" @click="clear()" class="inline-flex min-h-6 min-w-6 cursor-pointer items-center justify-center rounded-md px-3 py-1.5 text-xs text-muted transition-colors duration-200 hover:text-red-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/60">Clear</button>
    </div>
    <div class="card overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-line font-mono text-xs uppercase tracking-wider text-muted">
                <tr>
                    <th class="px-4 py-2.5 font-medium">Term</th>
                    <template x-for="label in labels" :key="label">
                        <th class="px-4 py-2.5 font-medium" x-text="label"></th>
                    </template>
                    <th class="px-4 py-2.5 font-medium">Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                <template x-for="run in history" :key="run.ts">
                    <tr>
                        <td class="px-4 py-2.5 font-mono text-ink" x-text="run.term"></td>
                        <template x-for="label in labels" :key="label">
                            <td class="px-4 py-2.5 font-mono text-accent" x-text="(run.ms[label] ?? '—') + (run.ms[label] != null ? ' ms' : '')"></td>
                        </template>
                        <td class="px-4 py-2.5 text-xs text-muted" x-text="new Date(run.ts).toLocaleTimeString()"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>

<script>
function benchmarkHistory() {
    return {
        history: [],
        labels:  ['LIKE Pattern Search', 'BM25 Inverted Index', 'BM25 + typo tolerance'],
        load() {
            try { this.history = JSON.parse(localStorage.getItem('fzs_bench_history') ?? '[]'); } catch { this.history = []; }
        },
        clear() {
            localStorage.removeItem('fzs_bench_history');
            this.history = [];
        },
    };
}

// Persist the current run after the page loads
document.addEventListener('DOMContentLoaded', () => {
    const term = new URLSearchParams(location.search).get('q');
    if (!term) return;

    const ms = {};
    document.querySelectorAll('[data-bench-label]').forEach(el => {
        const raw = parseFloat(el.dataset.benchMs);
        if (!isNaN(raw)) ms[el.dataset.benchLabel] = raw;
    });
    if (!Object.keys(ms).length) return;

    const history = JSON.parse(localStorage.getItem('fzs_bench_history') ?? '[]');
    history.unshift({ term, ms, ts: Date.now() });
    localStorage.setItem('fzs_bench_history', JSON.stringify(history.slice(0, 5)));
    window.dispatchEvent(new CustomEvent('bench-run-saved'));
});
</script>
@endsection

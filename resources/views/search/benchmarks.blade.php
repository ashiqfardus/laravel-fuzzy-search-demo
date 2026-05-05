@extends('layouts.app')

@section('title', 'LIKE vs BM25 Benchmark')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-6xl">

    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">LIKE vs BM25 Benchmark</h1>
        <p class="text-gray-600">Same query, same 100k dataset — compare LIKE pattern search against BM25 inverted index on latency and result ordering.</p>
    </div>

    <form method="GET" class="mb-8 flex gap-3 items-center">
        <input type="text" name="q" value="{{ $term }}"
               class="border border-gray-300 rounded-lg px-4 py-2 w-72 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
               placeholder="e.g. john, laravel, smith">
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-6 py-2 rounded-lg transition-colors">
            Run Benchmark
        </button>
    </form>

    @if($term && !empty($results))
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
        @foreach($results as $key => $data)
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="bg-gray-50 border-b px-4 py-3 flex justify-between items-center"
                 data-bench-label="{{ $data['label'] }}"
                 data-bench-ms="{{ $data['ms'] }}">
                <span class="font-semibold text-gray-800">{{ $data['label'] }}</span>
                <div class="flex gap-3 items-center">
                    <span class="text-gray-500 text-sm">{{ ($data['rows'] ?? collect())->count() }} results</span>
                    <span class="text-indigo-600 font-mono font-bold text-sm">{{ $data['ms'] }}ms</span>
                </div>
            </div>
            <div class="p-4">
                @if(!empty($data['error']))
                    <p class="text-red-500 text-sm">{{ $data['error'] }}</p>
                @elseif(($data['rows'] ?? collect())->isEmpty())
                    <p class="text-gray-400 italic text-sm">No results</p>
                @else
                    <ol class="space-y-2">
                        @foreach($data['rows'] as $i => $row)
                        <li class="flex items-center justify-between text-sm">
                            <span class="flex items-center gap-2">
                                <span class="text-gray-400 w-5 text-right">{{ $i+1 }}.</span>
                                <span class="text-gray-800">{{ $row->name }}</span>
                            </span>
                            @if(isset($row->_score))
                            <span class="text-gray-400 font-mono text-xs">{{ number_format($row->_score, 4) }}</span>
                            @endif
                        </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <div class="p-4 bg-blue-50 rounded-lg text-sm text-blue-800">
        <strong>Reading this:</strong>
        LIKE fetches up to 1,000 candidates from SQL, then re-scores in PHP.
        BM25 queries the inverted index directly — no full-table scan.
        On large tables BM25 is typically 5–20× faster with better relevance ordering.
    </div>
    @endif

    {{-- Run history table (Alpine + localStorage) --}}
    <div
        x-data="benchmarkHistory()"
        x-init="load()"
        class="mt-8"
        x-show="history.length > 0"
        x-cloak
    >
        <h3 class="font-semibold text-gray-800 mb-3">Run History <span class="text-gray-400 font-normal text-sm">(last 5 runs)</span></h3>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 border-b text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-2">Term</th>
                        <template x-for="label in labels" :key="label">
                            <th class="px-4 py-2" x-text="label"></th>
                        </template>
                        <th class="px-4 py-2">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="run in history" :key="run.ts">
                        <tr>
                            <td class="px-4 py-2 font-mono text-gray-800" x-text="run.term"></td>
                            <template x-for="label in labels" :key="label">
                                <td class="px-4 py-2 font-mono text-indigo-600"
                                    x-text="(run.ms[label] ?? '—') + (run.ms[label] != null ? 'ms' : '')"></td>
                            </template>
                            <td class="px-4 py-2 text-gray-400 text-xs"
                                x-text="new Date(run.ts).toLocaleTimeString()"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <button @click="clear()" class="mt-2 text-xs text-red-400 hover:text-red-600 transition-colors">
            Clear history
        </button>
    </div>

    <script>
    function benchmarkHistory() {
        return {
            history: [],
            labels:  ['LIKE Pattern Search', 'BM25 Inverted Index'],
            load() {
                try {
                    this.history = JSON.parse(localStorage.getItem('fzs_bench_history') ?? '[]');
                } catch { this.history = []; }
            },
            clear() {
                localStorage.removeItem('fzs_bench_history');
                this.history = [];
            },
        };
    }

    // Persist the current run to localStorage after the page loads
    document.addEventListener('DOMContentLoaded', () => {
        const term = new URLSearchParams(location.search).get('q');
        if (!term) return;

        const cards = document.querySelectorAll('[data-bench-label]');
        if (!cards.length) return;

        const ms = {};
        cards.forEach(el => {
            const raw = parseFloat(el.dataset.benchMs);
            if (!isNaN(raw)) ms[el.dataset.benchLabel] = raw;
        });

        if (!Object.keys(ms).length) return;

        const history = JSON.parse(localStorage.getItem('fzs_bench_history') ?? '[]');
        history.unshift({ term, ms, ts: Date.now() });
        localStorage.setItem('fzs_bench_history', JSON.stringify(history.slice(0, 5)));

        // Trigger Alpine to re-read history
        const el = document.querySelector('[x-data="benchmarkHistory()"]');
        if (el && el._x_dataStack) {
            el._x_dataStack[0].load();
        }
    });
    </script>

</div>
@endsection

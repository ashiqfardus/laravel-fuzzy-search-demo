@extends('layouts.app')

@section('title', 'Algorithm Comparison — Laravel Fuzzy Search Demo')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-7xl">

    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Algorithm Comparison</h1>
        <p class="text-gray-600">
            The same query run through every algorithm simultaneously.
            Scores are PHP-side relevance scores. Higher = better match.
        </p>
    </div>

    <form method="GET" class="mb-8 flex gap-3 items-center">
        <input type="text" name="q" value="{{ $term }}"
               class="border border-gray-300 rounded-lg px-4 py-2 w-72 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
               placeholder="Search term (e.g. john, jhn, jon)">
        <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2 rounded-lg transition-colors">
            Compare All Algorithms
        </button>
        <a href="{{ route('search.capability-matrix') }}"
           class="text-gray-500 hover:text-gray-700 text-sm">Reset</a>
    </form>

    @if($term)
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        @foreach($algorithms as $algo)
        @php $data = $results[$algo]; @endphp
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="bg-gray-50 border-b border-gray-200 px-4 py-3 flex items-center justify-between">
                <span class="font-semibold text-gray-800 font-mono">{{ $algo }}</span>
                @if($data['error'])
                    <span class="text-red-500 text-xs">error</span>
                @else
                    <div class="flex gap-2 items-center">
                        <span class="text-gray-500 text-xs">{{ $data['rows']->count() }} result(s)</span>
                        <span class="text-blue-500 text-xs font-mono">{{ $data['ms'] ?? '?' }}ms</span>
                    </div>
                @endif
            </div>
            <div class="p-4">
                @if($data['error'])
                    <p class="text-red-600 text-sm break-words">{{ $data['error'] }}</p>
                @elseif($data['rows']->isEmpty())
                    <p class="text-gray-400 text-sm italic">No results</p>
                    @if($algo === 'soundex')
                    <p class="text-gray-400 text-xs mt-1">
                        Soundex found no phonetically similar names. This is correct — soundex requires
                        a true phonetic match (same sound-code). Try searching <em>john</em>, <em>stephen</em>,
                        or <em>steven</em> to see it work.
                    </p>
                    @endif
                @else
                    <ol class="space-y-2">
                        @foreach($data['rows'] as $rank => $row)
                        <li class="flex items-center justify-between text-sm">
                            <span class="flex items-center gap-2">
                                <span class="text-gray-400 w-4 text-right">{{ $rank + 1 }}.</span>
                                <span class="text-gray-800">{{ $row->name }}</span>
                            </span>
                            @if(isset($row->_score))
                            <span class="text-xs text-gray-400 font-mono tabular-nums">
                                {{ number_format($row->_score, 1) }}
                            </span>
                            @endif
                        </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-8 p-4 bg-blue-50 rounded-lg text-sm text-blue-800">
        <strong>How to read this:</strong>
        Each card shows the same query run through a different algorithm.
        Differences reveal how each algorithm handles typos, phonetics, and substring matching.
        Scores are PHP-side relevance (higher = better).
        <strong>Soundex</strong> requires a true phonetic match — 0 results means no phonetically similar
        first names exist in the dataset for that query (correct, not broken). It works best on names like
        <em>john / jon / jean</em> or <em>steven / stephen</em>.
        The SQL each algorithm generates is documented in the
        <a href="https://github.com/ashiqfardus/laravel-fuzzy-search/blob/main/docs/CAPABILITY_MATRIX.md"
           class="underline" target="_blank">Capability Matrix</a>.
    </div>
    @endif

</div>
@endsection

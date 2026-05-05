@extends('layouts.app')

@section('title', 'Scout Driver Demo')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">

    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Scout Driver</h1>
        <p class="text-gray-600 mb-1">
            This package ships a <code class="bg-gray-100 px-1 rounded text-sm">SCOUT_DRIVER=fuzzy-search</code> engine bundled in core — no separate driver package needed.
        </p>
        <p class="text-gray-500 text-sm">
            The Scout driver wraps the same BM25 inverted index as <code class="bg-gray-100 px-1 rounded text-sm">->useInvertedIndex()</code>.
            Results include a <code class="bg-gray-100 px-1 rounded text-sm">_score</code> attribute.
        </p>
    </div>

    <form method="GET" class="mb-8 flex gap-3">
        <input type="text" name="q" value="{{ $term }}"
               class="border border-gray-300 rounded-lg px-4 py-2 w-72"
               placeholder="Search users...">
        <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-medium">Search</button>
    </form>

    @if($term)
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="bg-gray-50 border-b px-4 py-3 flex items-center justify-between">
            <span class="font-semibold text-gray-800">Results for "{{ $term }}"</span>
            <span class="text-gray-500 text-sm">{{ $results->count() }} results</span>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($results as $user)
            <div class="px-4 py-3 flex justify-between items-center text-sm">
                <div>
                    <p class="font-medium text-gray-800">{{ $user->name }}</p>
                    <p class="text-gray-500 text-xs">{{ $user->email }}</p>
                </div>
                @if(isset($user->_score))
                <span class="text-gray-400 font-mono text-xs">{{ number_format($user->_score, 4) }}</span>
                @endif
            </div>
            @empty
            <p class="px-4 py-8 text-center text-gray-400 italic">No results</p>
            @endforelse
        </div>
    </div>
    @endif

</div>
@endsection

@extends('layouts.app')
@section('title', 'Extended Search Playground')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-5xl">
    <h1 class="text-3xl font-bold mb-2">Extended Search Playground</h1>
    <p class="text-gray-600 mb-6">
        Try Fuse-style operators. Whitespace = AND, <code>|</code> = OR, <code>!</code> = NOT,
        <code>=</code> = exact, <code>^</code> = prefix, <code>$</code> = suffix.
    </p>

    <form method="GET" class="mb-6 flex gap-3">
        <input type="text" name="q" value="{{ $query }}"
               class="border rounded px-4 py-2 flex-1 font-mono"
               placeholder="=John ^Doe !banned">
        <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded">Run</button>
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
        <p class="text-gray-500 italic">No results for this query.</p>
    @endif
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">📝 Article Search</h1>
    <p class="text-gray-600">Test tokenization with multi-word search</p>
</div>

<!-- Search Form -->
<form method="GET" class="bg-white rounded-lg shadow p-6 mb-8">
    <div class="grid md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Search Query</label>
            <input type="text" name="q" value="{{ $query }}"
                   placeholder="Try: laravel search fuzzy..."
                   class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Match Mode</label>
            <select name="match_mode" class="w-full border rounded-lg px-4 py-2">
                <option value="any" {{ $matchMode === 'any' ? 'selected' : '' }}>Match Any (OR)</option>
                <option value="all" {{ $matchMode === 'all' ? 'selected' : '' }}>Match All (AND)</option>
            </select>
        </div>
    </div>
    <div class="mt-4">
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
            Search Articles
        </button>
    </div>
</form>

<!-- Results -->
@if($query)
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b">
        <h2 class="font-bold text-gray-800">Results ({{ $results->count() }})</h2>
    </div>

    @if($results->isEmpty())
        <div class="p-6 text-gray-500 text-center">No articles found for "{{ $query }}"</div>
    @else
        <div class="divide-y">
            @foreach($results as $article)
            <div class="p-6 hover:bg-gray-50">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-xs px-2 py-1 rounded {{ $article->status === 'published' ? 'bg-green-100 text-green-800' : ($article->status === 'draft' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-600') }}">
                        {{ ucfirst($article->status) }}
                    </span>
                    @if(isset($article->_score))
                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                        Score: {{ number_format($article->_score, 2) }}
                    </span>
                    @endif
                </div>
                <h3 class="font-bold text-gray-800 text-lg mb-2">@fuzzyHighlight($article, 'title')</h3>
                <p class="text-gray-600 text-sm mb-2">{{ Str::limit($article->excerpt ?? $article->body, 150) }}</p>
                <div class="text-xs text-gray-400">By {{ $article->author }}</div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endif
@endsection


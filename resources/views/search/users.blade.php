@extends('layouts.app')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">👤 User Search</h1>
    <p class="text-gray-600">Test different algorithms and typo tolerance settings</p>
</div>

<!-- Search Form -->
<form method="GET" class="bg-white rounded-lg shadow p-6 mb-8">
    <div class="grid md:grid-cols-4 gap-4">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Search Query</label>
            <input type="text" name="q" value="{{ $query }}"
                   placeholder="Try: jonh, jon, johnny..."
                   class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Algorithm</label>
            <select name="algorithm" class="w-full border rounded-lg px-4 py-2">
                <option value="fuzzy" {{ $algorithm === 'fuzzy' ? 'selected' : '' }}>Fuzzy</option>
                <option value="levenshtein" {{ $algorithm === 'levenshtein' ? 'selected' : '' }}>Levenshtein</option>
                <option value="soundex" {{ $algorithm === 'soundex' ? 'selected' : '' }}>Soundex</option>
                <option value="metaphone" {{ $algorithm === 'metaphone' ? 'selected' : '' }}>Metaphone</option>
                <option value="simple" {{ $algorithm === 'simple' ? 'selected' : '' }}>Simple (LIKE)</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Typo Tolerance</label>
            <select name="typo_tolerance" class="w-full border rounded-lg px-4 py-2">
                @for($i = 0; $i <= 5; $i++)
                    <option value="{{ $i }}" {{ $typoTolerance === $i ? 'selected' : '' }}>{{ $i }}</option>
                @endfor
            </select>
        </div>
    </div>
    <div class="mt-4">
        <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700">
            Search
        </button>
    </div>
</form>

<!-- Debug Info -->
@if($query && !empty($debugInfo))
<div class="bg-gray-800 text-gray-100 rounded-lg p-4 mb-8 text-sm overflow-x-auto">
    <div class="font-bold mb-2">Debug Info:</div>
    <pre>{{ json_encode($debugInfo, JSON_PRETTY_PRINT) }}</pre>
</div>
@endif

<!-- Results -->
@if($query)
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b">
        <h2 class="font-bold text-gray-800">Results ({{ $results->count() }})</h2>
    </div>

    @if($results->isEmpty())
        <div class="p-6 text-gray-500 text-center">No results found for "{{ $query }}"</div>
    @else
        <div class="divide-y">
            @foreach($results as $user)
            <div class="p-4 hover:bg-gray-50">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="font-medium text-gray-800">{!! $user->name !!}</div>
                        <div class="text-sm text-gray-500">{!! $user->email !!}</div>
                    </div>
                    @if(isset($user->_score))
                    <div class="bg-indigo-100 text-indigo-800 text-xs px-2 py-1 rounded">
                        Score: {{ number_format($user->_score, 2) }}
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endif
@endsection


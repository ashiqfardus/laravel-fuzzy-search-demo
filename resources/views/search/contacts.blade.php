@extends('layouts.app')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">📇 Contact Search</h1>
    <p class="text-gray-600">Test phonetic matching - find similar sounding names</p>
</div>

<!-- Search Form -->
<form method="GET" class="bg-white rounded-lg shadow p-6 mb-8">
    <div class="grid md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Search Query</label>
            <input type="text" name="q" value="{{ $query }}"
                   placeholder='Try: steven (finds Stephen), jon (finds John)...'
                   class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Algorithm</label>
            <select name="algorithm" class="w-full border rounded-lg px-4 py-2">
                <option value="soundex" {{ $algorithm === 'soundex' ? 'selected' : '' }}>Soundex</option>
                <option value="metaphone" {{ $algorithm === 'metaphone' ? 'selected' : '' }}>Metaphone</option>
                <option value="levenshtein" {{ $algorithm === 'levenshtein' ? 'selected' : '' }}>Levenshtein</option>
                <option value="fuzzy" {{ $algorithm === 'fuzzy' ? 'selected' : '' }}>Fuzzy</option>
            </select>
        </div>
    </div>
    <div class="mt-4">
        <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700">
            Search Contacts
        </button>
    </div>
</form>

<!-- Tips -->
<div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-8">
    <h3 class="font-medium text-purple-800 mb-2">💡 Phonetic Search Tips</h3>
    <ul class="text-sm text-purple-700 space-y-1">
        <li>• "steven" will find "Stephen", "Stefan"</li>
        <li>• "jon" will find "John", "Jonathan"</li>
        <li>• "katherine" will find "Catherine", "Kathryn"</li>
        <li>• "philip" will find "Phillip", "Filip"</li>
    </ul>
</div>

<!-- Results -->
@if($query)
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b">
        <h2 class="font-bold text-gray-800">Results ({{ $results->count() }})</h2>
    </div>

    @if($results->isEmpty())
        <div class="p-6 text-gray-500 text-center">No contacts found for "{{ $query }}"</div>
    @else
        <div class="grid md:grid-cols-2 gap-4 p-4">
            @foreach($results as $contact)
            <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-medium text-gray-800">
                            {!! $contact->first_name !!} {!! $contact->last_name !!}
                        </h3>
                        <div class="text-sm text-gray-500">{!! $contact->email !!}</div>
                        @if($contact->company)
                        <div class="text-sm text-gray-400">{{ $contact->company }}</div>
                        @endif
                    </div>
                    @if(isset($contact->_score))
                    <span class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded">
                        {{ number_format($contact->_score, 2) }}
                    </span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endif
@endsection


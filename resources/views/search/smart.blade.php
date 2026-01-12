@extends('layouts.app')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">🧠 Smart Search Features</h1>
    <p class="text-gray-600">Test Did You Mean, Suggestions, and Autocomplete</p>
</div>

<!-- Search Form -->
<form method="GET" class="bg-white rounded-lg shadow p-6 mb-8">
    <div class="flex gap-4">
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Search Query</label>
            <input type="text" name="q" value="{{ $query }}"
                   placeholder="Try misspellings: iphne, macbok, samsng..."
                   class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-pink-500 focus:border-pink-500"
                   id="searchInput">
        </div>
        <div class="flex items-end">
            <button type="submit" class="bg-pink-600 text-white px-6 py-2 rounded-lg hover:bg-pink-700">
                Smart Search
            </button>
        </div>
    </div>
</form>

<!-- Smart Features Display -->
@if($query)
<div class="grid md:grid-cols-3 gap-6 mb-8">

    <!-- Did You Mean -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
            <span class="text-2xl">💡</span> Did You Mean?
        </h3>
        @if(!empty($didYouMean) && count($didYouMean) > 0)
            <div class="space-y-2">
                @foreach($didYouMean as $suggestion)
                    @php
                        $term = is_array($suggestion) ? ($suggestion['term'] ?? '') : $suggestion;
                        $type = is_array($suggestion) ? ($suggestion['type'] ?? '') : '';
                    @endphp
                    <a href="?q={{ urlencode($term) }}"
                       class="block p-2 bg-yellow-50 border border-yellow-200 rounded hover:bg-yellow-100 transition">
                        <span class="font-medium text-yellow-800">{{ $term }}</span>
                        @if($type)
                            <span class="text-xs text-yellow-600 ml-2">({{ $type }})</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 text-sm">No spelling alternatives found</p>
        @endif
    </div>

    <!-- Suggestions -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
            <span class="text-2xl">✨</span> Suggestions
        </h3>
        @if(!empty($suggestions) && count($suggestions) > 0)
            <div class="space-y-2">
                @foreach($suggestions as $suggestion)
                    <a href="?q={{ urlencode(is_string($suggestion) ? $suggestion : ($suggestion['text'] ?? '')) }}"
                       class="block p-2 bg-blue-50 border border-blue-200 rounded hover:bg-blue-100 transition">
                        <span class="font-medium text-blue-800">
                            {{ is_string($suggestion) ? $suggestion : ($suggestion['text'] ?? json_encode($suggestion)) }}
                        </span>
                    </a>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 text-sm">No suggestions available</p>
        @endif
    </div>

    <!-- Autocomplete Preview -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
            <span class="text-2xl">⚡</span> Autocomplete
        </h3>
        @if(!empty($autocomplete) && count($autocomplete) > 0)
            <div class="space-y-2">
                @foreach($autocomplete as $item)
                    <a href="?q={{ urlencode($item) }}"
                       class="block p-2 bg-green-50 border border-green-200 rounded hover:bg-green-100 transition">
                        <span class="font-medium text-green-800">{{ $item }}</span>
                    </a>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 text-sm">No autocomplete matches</p>
        @endif
    </div>
</div>
@endif

<!-- Tips -->
<div class="bg-pink-50 border border-pink-200 rounded-lg p-4 mb-8">
    <h3 class="font-medium text-pink-800 mb-2">🧪 Test These Features</h3>
    <ul class="text-sm text-pink-700 space-y-1">
        <li><strong>Typo Correction:</strong> Search "volet" → finds "Violet VonRueden" (412.01 similarity score)</li>
        <li><strong>Did You Mean:</strong> Shows spelling correction alternatives</li>
        <li><strong>Suggestions:</strong> Shows similar terms from database</li>
        <li><strong>Autocomplete:</strong> Matches as you type with proper scoring</li>
        <li><strong>Scoring:</strong> All results ranked by relevance (from package algorithm)</li>
    </ul>
</div>

<!-- Results -->
@if($query)
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b">
        <h2 class="font-bold text-gray-800">Search Results ({{ $results->count() }})</h2>
    </div>

    @if($results->isEmpty())
        <div class="p-6 text-gray-500 text-center">
            <p>No results found for "{{ $query }}"</p>
            @if(!empty($didYouMean) && count($didYouMean) > 0)
                <p class="mt-2">
                    Try:
                    @foreach($didYouMean as $suggestion)
                        <a href="?q={{ urlencode(is_array($suggestion) ? ($suggestion['term'] ?? '') : $suggestion) }}"
                           class="text-pink-600 hover:underline">
                            {{ is_array($suggestion) ? ($suggestion['term'] ?? '') : $suggestion }}
                        </a>{{ !$loop->last ? ', ' : '' }}
                    @endforeach
                </p>
            @endif
        </div>
    @else
        <div class="divide-y">
            @foreach($results as $result)
                @php
                    $type = $result['type'];
                    $item = $result['item'];
                    $similarity = $result['similarity'] ?? 0;
                    $colors = [
                        'Product' => 'green',
                        'User' => 'indigo',
                        'Article' => 'blue',
                        'Contact' => 'purple',
                    ];
                    $color = $colors[$type] ?? 'gray';
                @endphp
                <div class="p-4 hover:bg-gray-50">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs bg-{{ $color }}-100 text-{{ $color }}-800 px-2 py-1 rounded font-medium">
                                    {{ $type }}
                                </span>
                                <span class="text-xs bg-pink-100 text-pink-800 px-2 py-1 rounded">
                                    Match: {{ number_format($similarity, 0) }}%
                                </span>
                            </div>
                            <div class="font-medium text-gray-800">
                                @if($type === 'Product')
                                    {!! $item->name !!}
                                    <span class="text-gray-500 text-sm ml-2">{{ $item->brand }} • ${{ number_format($item->price, 2) }}</span>
                                @elseif($type === 'User')
                                    {!! $item->name !!}
                                    <span class="text-gray-500 text-sm ml-2">{{ $item->email }}</span>
                                @elseif($type === 'Article')
                                    {!! $item->title !!}
                                    <span class="text-gray-500 text-sm ml-2">by {{ $item->author }}</span>
                                @elseif($type === 'Contact')
                                    {!! $item->first_name !!} {!! $item->last_name !!}
                                    <span class="text-gray-500 text-sm ml-2">{{ $item->email }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endif
@endsection


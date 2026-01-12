@extends('layouts.app')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">🔗 Federated Search</h1>
    <p class="text-gray-600">Search across all models at once</p>
</div>

<!-- Search Form -->
<form method="GET" class="bg-white rounded-lg shadow p-6 mb-8">
    <div class="flex gap-4">
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Search Query</label>
            <input type="text" name="q" value="{{ $query }}"
                   placeholder="Search users, products, articles, contacts..."
                   class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
        </div>
        <div class="flex items-end">
            <button type="submit" class="bg-orange-600 text-white px-6 py-2 rounded-lg hover:bg-orange-700">
                Search All
            </button>
        </div>
    </div>
</form>

<!-- Results -->
@if($query)
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b">
        <h2 class="font-bold text-gray-800">Results across all models ({{ $results->count() }})</h2>
    </div>

    @if($results->isEmpty())
        <div class="p-6 text-gray-500 text-center">No results found for "{{ $query }}"</div>
    @else
        <div class="divide-y">
            @foreach($results as $item)
            <div class="p-4 hover:bg-gray-50">
                <div class="flex justify-between items-start">
                    <div>
                        @php
                            $modelType = class_basename($item);
                            $colors = [
                                'User' => 'indigo',
                                'Product' => 'green',
                                'Article' => 'blue',
                                'Contact' => 'purple',
                            ];
                            $color = $colors[$modelType] ?? 'gray';
                        @endphp
                        <span class="text-xs bg-{{ $color }}-100 text-{{ $color }}-800 px-2 py-1 rounded mb-2 inline-block">
                            {{ $modelType }}
                        </span>
                        <div class="font-medium text-gray-800">
                            @if($modelType === 'User')
                                {{ $item->name }} - {{ $item->email }}
                            @elseif($modelType === 'Product')
                                {{ $item->name }} ({{ $item->brand }}) - ${{ number_format($item->price, 2) }}
                            @elseif($modelType === 'Article')
                                {{ $item->title }} by {{ $item->author }}
                            @elseif($modelType === 'Contact')
                                {{ $item->first_name }} {{ $item->last_name }} - {{ $item->email }}
                            @else
                                {{ $item->name ?? $item->title ?? 'Unknown' }}
                            @endif
                        </div>
                    </div>
                    @if(isset($item->_score))
                    <span class="text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded">
                        {{ number_format($item->_score, 2) }}
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


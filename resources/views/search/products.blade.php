@extends('layouts.app')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">🛒 Product Search</h1>
    <p class="text-gray-600">Test config presets for e-commerce search</p>
</div>

<!-- Search Form -->
<form method="GET" class="bg-white rounded-lg shadow p-6 mb-8">
    <div class="grid md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Search Query</label>
            <input type="text" name="q" value="{{ $query }}"
                   placeholder="Try: iphone, macbok, samsng..."
                   class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Preset</label>
            <select name="preset" class="w-full border rounded-lg px-4 py-2">
                <option value="ecommerce" {{ $preset === 'ecommerce' ? 'selected' : '' }}>E-commerce (typo tolerant)</option>
                <option value="exact" {{ $preset === 'exact' ? 'selected' : '' }}>Exact (no typos)</option>
            </select>
        </div>
    </div>
    <div class="mt-4">
        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
            Search Products
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
        <div class="p-6 text-gray-500 text-center">No products found for "{{ $query }}"</div>
    @else
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 p-4">
            @foreach($results as $product)
            <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">{{ $product->category }}</span>
                    @if(isset($product->_score))
                    <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">
                        {{ number_format($product->_score, 1) }}
                    </span>
                    @endif
                </div>
                <h3 class="font-medium text-gray-800 mb-1">{!! $product->name !!}</h3>
                <div class="text-sm text-gray-500 mb-2">{{ $product->brand }} • {{ $product->sku }}</div>
                <div class="text-lg font-bold text-green-600">${{ number_format($product->price, 2) }}</div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endif
@endsection


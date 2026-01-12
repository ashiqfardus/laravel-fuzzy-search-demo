@extends('layouts.app')

@section('content')
<div class="text-center mb-12">
    <h1 class="text-4xl font-bold text-gray-800 mb-4">Laravel Fuzzy Search Demo</h1>
    <p class="text-gray-600 text-lg">Test the fuzzy search package with real data</p>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12">
    <div class="bg-white rounded-lg shadow p-6 text-center">
        <div class="text-3xl font-bold text-indigo-600">{{ $stats['users'] }}</div>
        <div class="text-gray-500">Users</div>
    </div>
    <div class="bg-white rounded-lg shadow p-6 text-center">
        <div class="text-3xl font-bold text-green-600">{{ $stats['products'] }}</div>
        <div class="text-gray-500">Products</div>
    </div>
    <div class="bg-white rounded-lg shadow p-6 text-center">
        <div class="text-3xl font-bold text-blue-600">{{ $stats['articles'] }}</div>
        <div class="text-gray-500">Articles</div>
    </div>
    <div class="bg-white rounded-lg shadow p-6 text-center">
        <div class="text-3xl font-bold text-purple-600">{{ $stats['contacts'] }}</div>
        <div class="text-gray-500">Contacts</div>
    </div>
</div>

<!-- Search Types -->
<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
    <a href="{{ route('search.users') }}" class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-shadow">
        <h3 class="text-xl font-bold text-gray-800 mb-2">👤 User Search</h3>
        <p class="text-gray-600 mb-4">Test different algorithms and typo tolerance settings</p>
        <div class="text-sm text-indigo-600">Algorithms: fuzzy, levenshtein, soundex →</div>
    </a>

    <a href="{{ route('search.products') }}" class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-shadow">
        <h3 class="text-xl font-bold text-gray-800 mb-2">🛒 Product Search</h3>
        <p class="text-gray-600 mb-4">Test config presets (ecommerce, exact)</p>
        <div class="text-sm text-green-600">Presets: ecommerce, exact →</div>
    </a>

    <a href="{{ route('search.articles') }}" class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-shadow">
        <h3 class="text-xl font-bold text-gray-800 mb-2">📝 Article Search</h3>
        <p class="text-gray-600 mb-4">Test tokenization with matchAll/matchAny</p>
        <div class="text-sm text-blue-600">Blog preset + token matching →</div>
    </a>

    <a href="{{ route('search.contacts') }}" class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-shadow">
        <h3 class="text-xl font-bold text-gray-800 mb-2">📇 Contact Search</h3>
        <p class="text-gray-600 mb-4">Test phonetic matching (soundex, metaphone)</p>
        <div class="text-sm text-purple-600">Try: "steven" finds "Stephen" →</div>
    </a>

    <a href="{{ route('search.federated') }}" class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-shadow">
        <h3 class="text-xl font-bold text-gray-800 mb-2">🔗 Federated Search</h3>
        <p class="text-gray-600 mb-4">Search across all models at once</p>
        <div class="text-sm text-orange-600">Multi-model search →</div>
    </a>

    <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
        <h3 class="text-xl font-bold mb-2">✨ Features to Test</h3>
        <ul class="text-sm space-y-1 opacity-90">
            <li>• Typo tolerance (jonh → john)</li>
            <li>• Phonetic matching (steven → stephen)</li>
            <li>• Relevance scoring (_score)</li>
            <li>• Result highlighting</li>
            <li>• Config presets</li>
        </ul>
    </div>
</div>
@endsection


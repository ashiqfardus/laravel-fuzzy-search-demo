<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Fuzzy Search Demo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        mark { background-color: #fef08a; padding: 0 2px; border-radius: 2px; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-indigo-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <a href="{{ route('search.index') }}" class="text-xl font-bold">🔍 Fuzzy Search Demo</a>
                <div class="flex space-x-4 text-sm">
                    <a href="{{ route('search.users') }}" class="hover:text-indigo-200">Users</a>
                    <a href="{{ route('search.products') }}" class="hover:text-indigo-200">Products</a>
                    <a href="{{ route('search.articles') }}" class="hover:text-indigo-200">Articles</a>
                    <a href="{{ route('search.contacts') }}" class="hover:text-indigo-200">Contacts</a>
                    <a href="{{ route('search.federated') }}" class="hover:text-indigo-200">Federated</a>
                    <a href="{{ route('search.smart') }}" class="hover:text-indigo-200">Smart</a>
                    <a href="{{ route('search.capability-matrix') }}" class="hover:text-indigo-200 font-semibold border border-indigo-400 rounded px-2 py-0.5">⚡ Benchmark</a>
                    <a href="{{ route('search.benchmarks') }}" class="hover:text-indigo-200">BM25</a>
                    <a href="{{ route('search.scout-demo') }}" class="hover:text-indigo-200">Scout</a>
                    <a href="{{ route('search.playground') }}" class="hover:text-indigo-200 font-semibold">🎮 Playground</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-8">
        @yield('content')
    </main>

    <footer class="text-center text-gray-500 py-4 text-sm">
        Laravel Fuzzy Search v2.0.0-alpha.1 | Package Demo
    </footer>
</body>
</html>


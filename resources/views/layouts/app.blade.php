<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Laravel Fuzzy Search Demo')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600&family=Fira+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $nav = [
        ['search.users',             'Users'],
        ['search.products',          'Products'],
        ['search.relations',         'Relations'],
        ['search.articles',          'Articles'],
        ['search.contacts',          'Contacts'],
        ['search.federated',         'Federated'],
        ['search.smart',             'Smart'],
        ['search.capability-matrix', 'Compare'],
        ['search.benchmarks',        'BM25'],
        ['search.scout-demo',        'Scout'],
        ['search.playground',        'Playground'],
    ];
@endphp
<body class="min-h-full bg-base font-sans text-ink antialiased" x-data="{ tour: fuzzyTour(), menu: false }" x-init="tour.restore()">
    <header class="sticky top-0 z-50 border-b border-line bg-base/90 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center gap-6 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('search.index') }}" class="flex shrink-0 items-center gap-2 font-mono text-sm font-semibold text-ink">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-accent text-slate-950">
                    <x-icon name="search" class="h-4 w-4" />
                </span>
                fuzzy-search
                <span class="badge bg-raised text-muted">v2.1</span>
            </a>

            <nav class="hidden min-w-0 flex-1 items-center gap-1 overflow-x-auto md:flex" aria-label="Primary">
                @foreach($nav as [$route, $label])
                    <a href="{{ route($route) }}"
                       @class([
                           'rounded-md px-3 py-1.5 text-sm whitespace-nowrap transition-colors duration-200 hover:bg-raised hover:text-ink',
                           'bg-raised text-ink' => request()->routeIs($route),
                           'text-muted'         => !request()->routeIs($route),
                       ])
                       @if(request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>

            <a href="https://github.com/ashiqfardus/laravel-fuzzy-search" target="_blank" rel="noopener"
               class="ml-auto hidden items-center gap-2 text-sm text-muted transition-colors duration-200 hover:text-ink md:flex">
                <x-icon name="github" class="h-5 w-5" /> GitHub
            </a>

            <button type="button" class="btn-ghost ml-auto md:hidden" @click="menu = !menu" :aria-expanded="menu" aria-controls="mobile-nav" aria-label="Toggle navigation">
                <x-icon name="menu" class="h-5 w-5" />
            </button>
        </div>

        <nav id="mobile-nav" x-show="menu" x-cloak @click.outside="menu = false" class="border-t border-line bg-base px-4 py-3 md:hidden" aria-label="Primary">
            <div class="grid grid-cols-2 gap-1">
                @foreach($nav as [$route, $label])
                    <a href="{{ route($route) }}"
                       @class(['rounded-md px-3 py-2 text-sm', 'bg-raised text-ink' => request()->routeIs($route), 'text-muted' => !request()->routeIs($route)])>{{ $label }}</a>
                @endforeach
                <a href="https://github.com/ashiqfardus/laravel-fuzzy-search" class="rounded-md px-3 py-2 text-sm text-muted">GitHub</a>
            </div>
        </nav>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        @yield('content')
    </main>

    <footer class="mx-auto max-w-7xl px-4 py-8 text-sm text-muted sm:px-6 lg:px-8">
        <div class="flex flex-col items-start gap-2 border-t border-line pt-6 sm:flex-row sm:items-center sm:justify-between">
            <span class="font-mono break-all">ashiqfardus/laravel-fuzzy-search</span>
            <span>MIT · <a href="https://github.com/ashiqfardus/laravel-fuzzy-search" class="text-link hover:underline">Source</a></span>
        </div>
    </footer>

    @include('partials.tour-overlay')
</body>
</html>

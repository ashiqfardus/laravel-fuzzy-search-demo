@extends('layouts.app')

@php
    $pages = [
        ['search.users',             'users',    'User search',        'The BM25 index by default, or five LIKE algorithms with typo tolerance and a fallback chain that only runs when the primary finds nothing.', 'jonh · simple → fuzzy'],
        ['search.products',          'bag',      'Product search',     'Config presets, weighted BM25 (name over description), a category filter honoured on the index path, and an as-you-type box on the dictionary.',            'macbok · type macb'],
        ['search.relations',         'link',     'Relationship search', 'Search through Eloquent relations with dotted columns — author.name on the BM25 index, or whereHas/EXISTS on the table scan.', 'smith'],
        ['search.articles',          'document', 'Article search',     'Multi-word tokenization with match-all and match-any.',                                                                'laravel search fuzzy'],
        ['search.contacts',          'card',     'Contact search',     'Phonetic matching with Soundex: names that sound alike and start with the same letter.',                                  'steven → Stefan'],
        ['search.federated',         'squares',  'Federated search',   'One query across users, products, articles and contacts, merged into a single ranking.',                              'john'],
        ['search.smart',             'sparkles', 'Smart search',       'Did-you-mean from the index dictionary, suggestions and autocomplete across four models.',                              'iphne → iPhone'],
        ['search.capability-matrix', 'table',    'Compare algorithms', 'The same term through every algorithm, side by side, with timings.',                                                   'jhn'],
        ['search.benchmarks',        'chart',    'LIKE vs BM25',       'Latency and ordering: pattern search against the inverted index, with a typo-tolerance toggle for the index read.',                                       'jonh · typo 2'],
        ['search.scout-demo',        'bolt',     'Scout driver',       'SCOUT_DRIVER=fuzzy-search wires the package in as a Scout engine.',                                                     'john'],
        ['search.playground',        'code',     'Query playground',   'Extended syntax: ^prefix, word$, =exact, !not, | or, ~typo, field:scope, quoted phrases.',                                                  '^John Doe !banned'],
        ['search.analytics',         'chart',    'Search analytics',   'Popular and zero-result searches, latency by path and daily volume from fuzzy_search_logs.',                                       'run a few searches first'],
        ['search.i18n',              'squares',  'CJK search',         'Chinese, Japanese and Korean product names through the script-aware n-gram tokenizer.',                                            '東京'],
    ];
    $tiles = [
        ['users',    'Users',    'users'],
        ['products', 'Products', 'bag'],
        ['articles', 'Articles', 'document'],
        ['contacts', 'Contacts', 'card'],
    ];
    $new = [
        ['<span class="code">fallback()</span> runs the next algorithm when the primary finds nothing'],
        ['BM25 honours <span class="code">filter()</span>, wheres and scopes before cutting the page'],
        ['Bengali, Hindi, Thai and accented terms handled per character'],
        ['Accessor-backed searchable fields reindex via <span class="code">reindex_on</span>'],
        ['CI green on SQLite, MySQL, MariaDB, PostgreSQL and SQL Server'],
        ['Bounded retries for index jobs and an eager-loading rebuild hook'],
        ['Column weights on the BM25 index'],
        ['Relationship search through dotted columns (<span class="code">author.name</span>)'],
        ['Typo-tolerant, as-you-type BM25 index'],
        ['Extended syntax: <span class="code">~word</span> typo terms and <span class="code">field:</span> scopes'],
        ['Persisted search analytics and dictionary-backed autocomplete'],
        ['Script-aware tokenizer: character bigrams for Chinese, Japanese and Korean'],
        ['JSON resources: FuzzySearchCollection with scores, highlights and meta'],
    ];
@endphp

@section('content')
{{-- Hero --}}
<section class="card relative mb-10 overflow-hidden px-6 py-10 sm:px-10 sm:py-14">
    <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-accent/10 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -bottom-32 -left-16 h-72 w-72 rounded-full bg-link/10 blur-3xl" aria-hidden="true"></div>

    <div class="relative grid gap-8 lg:grid-cols-[1.3fr_1fr] lg:items-end">
        <div>
            <p class="mb-3 font-mono text-xs uppercase tracking-[0.2em] text-accent">ashiqfardus/laravel-fuzzy-search</p>
            <h1 class="font-mono text-3xl font-semibold tracking-tight sm:text-5xl sm:leading-[1.1]">Typo-tolerant search, straight from your database.</h1>
            <p class="mt-4 max-w-xl text-lg leading-relaxed text-muted">Seven algorithms, a BM25 inverted index, extended query syntax and a Scout driver. Every page here runs the real package against seeded data.</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <button type="button" @click="tour.start()" class="btn-primary">
                    <x-icon name="play" class="h-4 w-4" />
                    Take the tour
                    <span class="text-sm text-slate-950/70">8 stops · 3 min</span>
                </button>
                <a href="{{ route('search.users') }}" class="btn-ghost">
                    Start with users <x-icon name="arrow" class="h-4 w-4" />
                </a>
            </div>
        </div>

        <div class="rounded-xl border border-line bg-base/60 p-5 font-mono text-sm leading-relaxed">
            <p class="mb-3 text-xs uppercase tracking-[0.15em] text-muted">Quick start</p>
<pre class="overflow-x-auto text-link"><code>composer require ashiqfardus/laravel-fuzzy-search

User::search('jonh')
    -&gt;typoTolerance(2)
    -&gt;fallback('soundex')
    -&gt;get();   <span class="text-muted">// finds "John"</span></code></pre>
        </div>
    </div>
</section>

{{-- Stats --}}
<section class="mb-10 grid grid-cols-2 gap-4 md:grid-cols-4" aria-label="Seeded data">
    @foreach($tiles as [$key, $label, $icon])
        <div class="card flex items-center gap-4 p-5">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-raised text-accent">
                <x-icon :name="$icon" class="h-5 w-5" />
            </span>
            <div>
                <p class="font-mono text-2xl font-semibold tabular-nums text-ink">{{ number_format($stats[$key]) }}</p>
                <p class="text-sm text-muted">{{ $label }}</p>
            </div>
        </div>
    @endforeach
</section>

{{-- Pages --}}
<section class="mb-10" aria-labelledby="pages-heading">
    <div class="mb-4 flex items-end justify-between gap-4">
        <h2 id="pages-heading" class="font-mono text-xs uppercase tracking-[0.15em] text-muted">Explore the features</h2>
        <span class="text-sm text-muted">{{ count($pages) }} pages</span>
    </div>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach($pages as [$route, $icon, $title, $description, $try])
            <a href="{{ route($route) }}"
               class="group card flex cursor-pointer flex-col gap-3 p-5 transition-colors duration-200 hover:border-accent/60 hover:bg-raised/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/60">
                <div class="flex items-center justify-between">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-raised text-accent transition-colors duration-200 group-hover:bg-accent group-hover:text-slate-950">
                        <x-icon :name="$icon" class="h-5 w-5" />
                    </span>
                    <x-icon name="arrow" class="h-4 w-4 text-muted transition-transform duration-200 group-hover:translate-x-0.5 group-hover:text-ink" />
                </div>
                <div>
                    <h3 class="font-semibold text-ink">{{ $title }}</h3>
                    <p class="mt-1 text-sm leading-relaxed text-muted">{{ $description }}</p>
                </div>
                <p class="mt-auto font-mono text-xs text-muted">try: <span class="text-link">{{ $try }}</span></p>
            </a>
        @endforeach
    </div>
</section>

{{-- New in 2.1 --}}
<section class="card p-6" aria-labelledby="new-heading">
    <div class="flex items-center gap-2">
        <span class="badge bg-accent/15 text-accent">v2.1</span>
        <h2 id="new-heading" class="font-semibold text-ink">What this demo exercises from the next release</h2>
    </div>
    <ul class="mt-4 grid gap-x-8 gap-y-2 text-sm text-muted sm:grid-cols-2">
        @foreach($new as [$item])
            <li class="flex gap-2">
                <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-accent" />
                <span>{!! $item !!}</span>
            </li>
        @endforeach
    </ul>
</section>
@endsection

@extends('layouts.app')

@section('title', 'Smart search — Fuzzy Search Demo')

@php
    $tones = [
        'User'    => 'bg-sky-500/15 text-sky-300',
        'Product' => 'bg-accent/15 text-accent',
        'Article' => 'bg-violet-500/15 text-violet-300',
        'Contact' => 'bg-amber-500/15 text-amber-300',
    ];
    $panels = [
        ['Did you mean?', 'sparkles', collect($didYouMean)->map(fn ($s) => is_array($s) ? ($s['term'] ?? '') : $s)->filter()->values(),  'No spelling alternatives found'],
        ['Suggestions',   'search',   collect($suggestions)->map(fn ($s) => is_string($s) ? $s : ($s['text'] ?? ''))->filter()->values(), 'No suggestions available'],
        ['Autocomplete',  'bolt',     collect($autocomplete)->filter()->values(),                                                          'No autocomplete matches'],
    ];
@endphp

@section('content')
<x-page-header eyebrow="Did you mean · suggestions · autocomplete" title="Smart search">
    Misspell something on purpose. Every result is scored, and the same pass produces spelling alternatives and completions.
</x-page-header>

<x-search-form button="Smart search" cols="md:grid-cols-1">
    <x-field label="Search query" for="q">
        <input id="q" type="text" name="q" value="{{ $query }}" placeholder="Try misspellings: iphne, macbok, samsng…" class="field-control" autofocus>
    </x-field>
</x-search-form>

@if($query)
    <div class="mb-8 grid gap-4 md:grid-cols-3">
        @foreach($panels as [$title, $icon, $items, $emptyText])
            <section class="card p-5">
                <h3 class="mb-4 flex items-center gap-2 font-mono text-xs uppercase tracking-[0.15em] text-muted">
                    <x-icon :name="$icon" class="h-4 w-4 text-accent" /> {{ $title }}
                </h3>
                @if($items->isEmpty())
                    <p class="text-sm text-muted">{{ $emptyText }}</p>
                @else
                    <ul class="space-y-1.5">
                        @foreach($items as $term)
                            <li>
                                <a href="?q={{ urlencode($term) }}" class="block cursor-pointer rounded-lg border border-line bg-base/40 px-3 py-2 text-sm text-ink transition-colors duration-200 hover:border-accent/50 hover:bg-raised">{{ $term }}</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach
    </div>
@else
    <x-callout tone="info" title="What to try" class="mb-8">
        <ul class="grid gap-1 sm:grid-cols-2">
            <li><span class="code">volet</span> → Violet VonRueden (typo correction)</li>
            <li><span class="code">iphne</span> → iPhone (did you mean)</li>
            <li><span class="code">mac</span> → completions from product names</li>
            <li>Every result is ranked by the package's relevance score</li>
        </ul>
    </x-callout>
@endif

@if($query)
    <x-results :count="$results->count()">
        @if($results->isEmpty())
            <x-empty icon="sparkles">
                No results for “{{ $query }}”.
                @if($panels[0][2]->isNotEmpty())
                    <x-slot:hint>Try: {{ $panels[0][2]->implode(', ') }}</x-slot:hint>
                @endif
            </x-empty>
        @else
            <ul class="divide-y divide-line">
                @foreach($results as $result)
                    @php $type = $result['type']; $item = $result['item']; @endphp
                    <li class="row">
                        <div class="min-w-0">
                            <div class="mb-1.5 flex flex-wrap items-center gap-2">
                                <span class="badge {{ $tones[$type] ?? 'bg-raised text-muted' }}">{{ $type }}</span>
                                <span class="badge bg-raised text-muted">match {{ number_format($result['similarity'] ?? 0) }}%</span>
                            </div>
                            <p class="font-medium text-ink">
                                @if($type === 'Product')
                                    @fuzzyHighlight($item, 'name') <span class="font-normal text-muted">· {{ $item->brand }} · ${{ number_format($item->price, 2) }}</span>
                                @elseif($type === 'User')
                                    @fuzzyHighlight($item, 'name') <span class="font-normal text-muted">· {{ $item->email }}</span>
                                @elseif($type === 'Article')
                                    @fuzzyHighlight($item, 'title') <span class="font-normal text-muted">· by {{ $item->author?->name }}</span>
                                @elseif($type === 'Contact')
                                    @fuzzyHighlight($item, 'first_name') @fuzzyHighlight($item, 'last_name') <span class="font-normal text-muted">· {{ $item->email }}</span>
                                @endif
                            </p>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-results>
@endif
@endsection

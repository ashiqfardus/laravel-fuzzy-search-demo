@extends('layouts.app')

@section('title', 'Federated search — Fuzzy Search Demo')

@php
    $tones = [
        'User'    => 'bg-sky-500/15 text-sky-300',
        'Product' => 'bg-accent/15 text-accent',
        'Article' => 'bg-violet-500/15 text-violet-300',
        'Contact' => 'bg-amber-500/15 text-amber-300',
    ];
@endphp

@section('content')
<x-page-header eyebrow="Four models · one ranking" title="Federated search">
    Users, products, articles and contacts are searched together and merged into a single list ranked by a normalised score. Each model is searched through its BM25 index (<span class="code">useInvertedIndex()</span>) on its own configured columns. Until every model's index is built, each runs its own LIKE search instead, on the short name columns only — <span class="code">name</span>, <span class="code">title</span>, <span class="code">first_name</span>, <span class="code">last_name</span> — to stay fast on large tables.
</x-page-header>

<x-search-form button="Search everything" cols="md:grid-cols-1">
    <x-field label="Search query" for="q">
        <input id="q" type="text" name="q" value="{{ $query }}" placeholder="Search users, products, articles, contacts…" class="field-control" autofocus>
    </x-field>
</x-search-form>

@if($query && $missing !== [])
    <x-callout tone="warn" title="Index not built" class="mb-8">
        Not every model has a BM25 index yet, so each model's LIKE table scan answered, on the short name columns. Build the missing ones once:
        @foreach($missing as $model)
            <span class="code">php artisan fuzzy-search:rebuild "{{ $model }}"</span>
        @endforeach
    </x-callout>
@endif

@if($query)
    <x-results title="Across all models" :count="$results->total()">
        @if($results->isEmpty() && $results->currentPage() > 1)
            <x-past-end :paginator="$results" />
        @elseif($results->isEmpty())
            <x-empty icon="squares">
                Nothing matches “{{ $query }}” in any model.
                @if($missing === [])
                    <x-slot:hint>The index found no word close enough in any model.</x-slot:hint>
                @endif
            </x-empty>
        @else
            <ul class="divide-y divide-line">
                @foreach($results as $item)
                    @php $type = class_basename($item); @endphp
                    <li class="row">
                        <div class="min-w-0">
                            <span class="badge mb-1.5 {{ $tones[$type] ?? 'bg-raised text-muted' }}">{{ $type }}</span>
                            <p class="font-medium text-ink">
                                @if($type === 'User')
                                    {{ $item->name }} <span class="font-normal text-muted">· {{ $item->email }}</span>
                                @elseif($type === 'Product')
                                    {{ $item->name }} <span class="font-normal text-muted">· {{ $item->brand }} · ${{ number_format($item->price, 2) }}</span>
                                @elseif($type === 'Article')
                                    {{ $item->title }} <span class="font-normal text-muted">· by {{ $item->author?->name }}</span>
                                @elseif($type === 'Contact')
                                    {{ $item->first_name }} {{ $item->last_name }} <span class="font-normal text-muted">· {{ $item->email }}</span>
                                @else
                                    {{ $item->name ?? $item->title ?? 'Unknown' }}
                                @endif
                            </p>
                        </div>
                        @isset($item->_score)
                            <span class="badge bg-accent/15 text-accent" title="Normalised score"><span class="sr-only">Normalised score </span>{{ number_format($item->_score, 2) }}</span>
                        @endisset
                    </li>
                @endforeach
            </ul>
        @endif
    </x-results>
    <x-pager :paginator="$results" class="mt-4" />
@endif
@endsection

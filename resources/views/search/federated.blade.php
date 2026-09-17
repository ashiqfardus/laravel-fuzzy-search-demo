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
    Users, products, articles and contacts are searched together and merged into a single list ranked by a normalised score.
</x-page-header>

<x-search-form button="Search everything" cols="md:grid-cols-1">
    <x-field label="Search query" for="q">
        <input id="q" type="text" name="q" value="{{ $query }}" placeholder="Search users, products, articles, contacts…" class="field-control" autofocus>
    </x-field>
</x-search-form>

@if($query)
    <x-results title="Across all models" :count="$results->total()">
        @if($results->isEmpty())
            <x-empty icon="squares">Nothing matches “{{ $query }}” in any model.</x-empty>
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
                            <span class="badge bg-accent/15 text-accent" title="Normalised score">{{ number_format($item->_score, 2) }}</span>
                        @endisset
                    </li>
                @endforeach
            </ul>
        @endif
    </x-results>
    <x-pager :paginator="$results" class="mt-4" />
@endif
@endsection

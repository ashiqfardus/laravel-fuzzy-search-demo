@extends('layouts.app')

@section('title', 'Scout driver — Fuzzy Search Demo')

@section('content')
<x-page-header eyebrow="Laravel Scout" title="Scout driver">
    Set <span class="code">SCOUT_DRIVER=fuzzy-search</span> and the package becomes a Scout engine once <span class="code">laravel/scout</span> is installed; no separate driver package. It wraps the same BM25 inverted index as <span class="code">->useInvertedIndex()</span> and returns a <span class="code">_score</span> on every model.
</x-page-header>

<x-search-form button="Search" cols="md:grid-cols-1">
    <x-field label="Search users" for="q">
        <input id="q" type="text" name="q" value="{{ $term }}" placeholder="Search users…" class="field-control" autofocus>
    </x-field>
</x-search-form>

@if($term)
    <x-results title="Results for “{{ $term }}”" :count="$results->count()">
        @if($results->isEmpty())
            <x-empty icon="bolt">
                No results.
                <x-slot:hint>If the index is empty, run php artisan fuzzy-search:rebuild "App\Models\User".</x-slot:hint>
            </x-empty>
        @else
            <ul class="divide-y divide-line">
                @foreach($results as $user)
                    <li class="row items-center">
                        <div class="min-w-0">
                            <p class="font-medium text-ink">{{ $user->name }}</p>
                            <p class="truncate text-sm text-muted">{{ $user->email }}</p>
                        </div>
                        @isset($user->_score)
                            <span class="font-mono text-xs tabular-nums text-muted">{{ number_format($user->_score, 4) }}</span>
                        @endisset
                    </li>
                @endforeach
            </ul>
        @endif
    </x-results>
@endif
@endsection

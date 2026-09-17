@extends('layouts.app')

@section('title', 'Search analytics — Fuzzy Search Demo')

@section('content')
<x-page-header eyebrow="fuzzy_search_logs · last 30 days" title="Search analytics">
    Every search fires a <span class="code">FuzzySearchExecuted</span> event; with analytics enabled, the listener persists term, path, result count and latency. This page reads it back through the <span class="code">SearchAnalytics</span> facade.
</x-page-header>

<div class="mb-6 grid gap-6 lg:grid-cols-2">
    <x-results title="Popular searches" :count="count($popular)">
        @if(empty($popular))
            <x-empty icon="chart">No searches recorded yet.
                <x-slot:hint>Run a few searches first.</x-slot:hint>
            </x-empty>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-line font-mono text-xs uppercase tracking-wider text-muted">
                        <tr>
                            <th class="px-5 py-2.5 font-medium">Term</th>
                            <th class="px-5 py-2.5 font-medium">Searches</th>
                            <th class="px-5 py-2.5 font-medium">Avg results</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($popular as $row)
                            <tr>
                                <td class="px-5 py-2.5 font-mono text-ink">{{ $row['term'] }}</td>
                                <td class="px-5 py-2.5 tabular-nums text-muted">{{ $row['searches'] }}</td>
                                <td class="px-5 py-2.5 tabular-nums text-muted">{{ number_format($row['avg_results'], 1) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-results>

    <x-results title="Zero-result searches" :count="count($zero)">
        @if(empty($zero))
            <x-empty icon="search">No zero-result searches in this window.</x-empty>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-line font-mono text-xs uppercase tracking-wider text-muted">
                        <tr>
                            <th class="px-5 py-2.5 font-medium">Term</th>
                            <th class="px-5 py-2.5 font-medium">Searches</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($zero as $row)
                            <tr>
                                <td class="px-5 py-2.5 font-mono text-ink">{{ $row['term'] }}</td>
                                <td class="px-5 py-2.5 tabular-nums text-muted">{{ $row['searches'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-results>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <x-results title="Latency by path" :count="count($latency)">
        @if(empty($latency))
            <x-empty icon="bolt">No latency samples yet.</x-empty>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-line font-mono text-xs uppercase tracking-wider text-muted">
                        <tr>
                            <th class="px-5 py-2.5 font-medium">Path</th>
                            <th class="px-5 py-2.5 font-medium">Avg ms</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($latency as $path => $ms)
                            <tr>
                                <td class="px-5 py-2.5 font-mono text-ink">{{ $path }}</td>
                                <td class="px-5 py-2.5 tabular-nums text-muted">{{ number_format($ms, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-results>

    <x-results title="Searches per day" :count="count($volume)">
        @if(empty($volume))
            <x-empty icon="chart">No searches recorded yet.</x-empty>
        @else
            <ul class="divide-y divide-line">
                @foreach($volume as $day => $count)
                    <li class="row items-center">
                        <span class="font-mono text-sm text-ink">{{ $day }}</span>
                        <span class="font-mono text-xs tabular-nums text-muted">{{ $count }} {{ \Illuminate\Support\Str::plural('search', $count) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-results>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Too many requests — Fuzzy Search Demo')

@section('content')
<x-page-header eyebrow="429 · rate limit" title="Slow down a little">
    The demo allows 30 page loads a minute from one address, and 10 of them may be LIKE table scans (the LIKE engine and the benchmarks page). Try again in a minute; the as-you-type boxes count separately and will keep working.
</x-page-header>
<a href="{{ route('search.index') }}" class="btn-primary">Back to the dashboard</a>
@endsection

@extends('layouts.app')

@section('title', 'Something went wrong — Fuzzy Search Demo')

@section('content')
{{-- Static on purpose: this page must render when the database is the thing that failed. --}}
<x-page-header eyebrow="500 · server error" title="Something went wrong">
    The demo could not answer that request. Try again in a moment; if it keeps failing, the database may be down or unmigrated.
</x-page-header>
<a href="{{ route('search.index') }}" class="btn-primary">Back to the dashboard</a>
@endsection

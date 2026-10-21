@extends('layouts.app')

@section('title', 'Page not found — Fuzzy Search Demo')

@section('content')
<x-page-header eyebrow="404 · not found" title="No such page">
    That address is not part of the demo. The dashboard lists every search page.
</x-page-header>
<a href="{{ route('search.index') }}" class="btn-primary">Back to the dashboard</a>
@endsection

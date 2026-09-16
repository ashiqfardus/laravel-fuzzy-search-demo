@extends('layouts.app')

@section('title', 'Contact search — Fuzzy Search Demo')

@section('content')
<x-page-header eyebrow="Phonetic matching" title="Contact search">
    Soundex and Metaphone match names that sound alike, so “steven” finds Stephen and “jon” finds John.
</x-page-header>

<x-search-form button="Search contacts" cols="md:grid-cols-3">
    <x-field label="Search query" for="q" class="md:col-span-2">
        <input id="q" type="text" name="q" value="{{ $query }}" placeholder="Try: steven, jon, katherine…" class="field-control" autofocus>
    </x-field>
    <x-field label="Algorithm" for="algorithm">
        <select id="algorithm" name="algorithm" class="field-control">
            @foreach(['soundex' => 'Soundex', 'levenshtein' => 'Levenshtein', 'fuzzy' => 'Fuzzy', 'similar_text' => 'Similar text'] as $value => $label)
                <option value="{{ $value }}" @selected($algorithm === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </x-field>
</x-search-form>

@unless($query)
    <x-callout tone="info" title="Try a sound-alike" class="mb-8">
        <ul class="grid gap-1 sm:grid-cols-2">
            <li><span class="code">steven</span> finds Stephen, Stefan</li>
            <li><span class="code">jon</span> finds John, Jonathan</li>
            <li><span class="code">katherine</span> finds Catherine, Kathryn</li>
            <li><span class="code">philip</span> finds Phillip, Filip</li>
        </ul>
    </x-callout>
@endunless

@if($query)
    <x-results :count="$results->count()">
        @if($results->isEmpty())
            <x-empty icon="card">
                No contacts sound like “{{ $query }}”.
                <x-slot:hint>Soundex needs a true phonetic match; try Levenshtein for typo-style differences.</x-slot:hint>
            </x-empty>
        @else
            <div class="grid gap-4 p-4 sm:p-5 md:grid-cols-2">
                @foreach($results as $contact)
                    <article class="flex items-start justify-between gap-3 rounded-xl border border-line bg-base/40 p-4 transition-colors duration-200 hover:border-accent/50">
                        <div class="min-w-0">
                            <h3 class="font-medium text-ink">@fuzzyHighlight($contact, 'first_name') @fuzzyHighlight($contact, 'last_name')</h3>
                            <p class="truncate text-sm text-muted">{{ $contact->email }}</p>
                            @if($contact->company)
                                <p class="text-sm text-muted">{{ $contact->company }}</p>
                            @endif
                        </div>
                        @isset($contact->_score)
                            <span class="badge bg-accent/15 text-accent" title="Relevance score">{{ number_format($contact->_score, 2) }}</span>
                        @endisset
                    </article>
                @endforeach
            </div>
        @endif
    </x-results>
@endif
@endsection

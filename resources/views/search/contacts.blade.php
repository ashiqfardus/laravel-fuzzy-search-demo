@extends('layouts.app')

@section('title', 'Contact search — Fuzzy Search Demo')

@section('content')
@php($algorithmLabels = ['soundex' => 'Soundex', 'levenshtein' => 'Levenshtein', 'fuzzy' => 'Fuzzy', 'similar_text' => 'Similar text'])
<x-page-header eyebrow="Phonetic matching" title="Contact search">
    @if($nativeSoundex)
        Soundex matches names that sound alike, so “steven” finds Stefan and “jon” finds Johnny. It keeps the first letter, so “philip” never finds Filip and “katherine” never finds Catherine. MySQL, MariaDB and SQL Server each runs SOUNDEX() natively, and SQLite a soundex function the package registers; PostgreSQL uses the package's phonetic pattern fallback unless fuzzystrmatch is installed, which matches differently.
    @else
        On this database (PostgreSQL without fuzzystrmatch) Soundex runs the package's phonetic pattern fallback: it matches the term, its first three letters, its consonant skeleton and a few spelling swaps (ph→f, ck→k, s→z). So “steven” finds Stefan and Stephen and “philip” finds Filip, but “jon” never finds John (try “john”), and “katherine” never finds Catherine. On MySQL, MariaDB, SQL Server and SQLite it matches by sound instead.
    @endif
</x-page-header>

<x-search-form button="Search contacts" cols="md:grid-cols-3">
    <x-field label="Search query" for="q" class="md:col-span-2">
        <input id="q" type="text" name="q" value="{{ $query }}" placeholder="Try: steven, {{ $nativeSoundex ? 'jon' : 'john' }}, katherine…" class="field-control" autofocus>
    </x-field>
    <x-field label="Algorithm" for="algorithm">
        <select id="algorithm" name="algorithm" class="field-control">
            @foreach($algorithmLabels as $value => $label)
                <option value="{{ $value }}" @selected($algorithm === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </x-field>
</x-search-form>

@unless($query)
    <x-callout tone="info" title="Try a sound-alike" class="mb-8">
        <ul class="grid gap-1 sm:grid-cols-2">
            <li><span class="code">steven</span> finds Stefan, Stephen</li>
            @if($nativeSoundex)
                <li><span class="code">jon</span> finds John, Johnny</li>
            @else
                <li><span class="code">john</span> finds Johnny</li>
            @endif
            <li><span class="code">katherine</span> finds Kathryn</li>
            <li><span class="code">eric</span> finds Erik, Erich</li>
        </ul>
    </x-callout>
@endunless

@if($query)
    <x-results :count="$results->total()">
        @if($results->isEmpty() && $results->currentPage() > 1)
            <x-past-end :paginator="$results" />
        @elseif($results->isEmpty())
            <x-empty icon="card">
                @if($algorithm === 'soundex')
                    No contacts sound like “{{ $query }}”.
                    <x-slot:hint>Soundex needs a true phonetic match that starts with the same letter; try Levenshtein for typo-style differences.</x-slot:hint>
                @else
                    No contacts match “{{ $query }}” with {{ $algorithmLabels[$algorithm] ?? $algorithm }}.
                    <x-slot:hint>Check the spelling, or pick another algorithm from the list.</x-slot:hint>
                @endif
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
                            <span class="badge bg-accent/15 text-accent" title="Relevance score"><span class="sr-only">Relevance score </span>{{ number_format($contact->_score, 2) }}</span>
                        @endisset
                    </article>
                @endforeach
            </div>
        @endif
    </x-results>
    <x-pager :paginator="$results" class="mt-4" />
@endif
@endsection

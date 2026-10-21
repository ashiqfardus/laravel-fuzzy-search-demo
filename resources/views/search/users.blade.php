@extends('layouts.app')

@section('title', 'User search — Fuzzy Search Demo')

@section('content')
<x-page-header eyebrow="BM25 index · algorithms · typo tolerance · fallback" title="User search">
    Choose how many typos to forgive. The BM25 index answers by default; switch the engine to the LIKE table scan to pick an algorithm and optionally chain a fallback that only runs when the primary finds nothing. The demo does not chain <span class="code">fallback()</span> on the index: a fallback would run a LIKE scan on a miss.
</x-page-header>

<x-search-form button="Search users" cols="md:grid-cols-6" :engine="$engine">
    <x-field label="Search query" for="q" class="md:col-span-2">
        <input id="q" type="text" name="q" value="{{ $query }}" placeholder="Try: jonh, jon, johnny…" class="field-control" autofocus>
    </x-field>
    <x-engine-field :engine="$engine" index="BM25 index" model="App\Models\User" scan="Each LIKE algorithm scans the table: about 2–4 s at 1M users, and a fallback can run two scans." />
    <x-field label="Algorithm" for="algorithm" hint="Table scan only.">
        <select id="algorithm" name="algorithm" class="field-control" :disabled="engine === 'bm25'">
            @foreach(['fuzzy' => 'Fuzzy', 'levenshtein' => 'Levenshtein', 'soundex' => 'Soundex', 'metaphone' => 'Metaphone', 'simple' => 'Simple (LIKE)'] as $value => $label)
                <option value="{{ $value }}" @selected($algorithm === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </x-field>
    <x-field label="Typo tolerance" for="typo_tolerance">
        <select id="typo_tolerance" name="typo_tolerance" class="field-control">
            @for($i = 0; $i <= 5; $i++)
                <option value="{{ $i }}" @selected($typoTolerance === $i)>{{ $i }}</option>
            @endfor
        </select>
    </x-field>
    <x-field label="Fallback" for="fallback" hint="Table scan only: runs if the primary finds nothing. Try Simple + “jonh” with Fuzzy.">
        <select id="fallback" name="fallback" class="field-control" :disabled="engine === 'bm25'">
            <option value="" @selected($fallback === '')>None</option>
            @foreach(['fuzzy' => 'Fuzzy', 'levenshtein' => 'Levenshtein', 'soundex' => 'Soundex', 'metaphone' => 'Metaphone', 'simple' => 'Simple (LIKE)'] as $value => $label)
                <option value="{{ $value }}" @selected($fallback === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </x-field>
    <x-slot:actions>
        <label class="inline-flex items-center gap-2 text-sm text-muted">
            <input type="checkbox" name="verified_only" value="1" class="accent-accent" @checked($verifiedOnly)>
            Verified users only
        </label>
    </x-slot:actions>
</x-search-form>

@if($verifiedOnly)
    <x-callout tone="info" class="mb-8">
        <code class="code">->whereNotNull('email_verified_at')</code> was chained straight onto the search builder before <code class="code">get()</code>.
    </x-callout>
@endif

@if($query && count($attempts) > 1)
    <x-callout tone="warn" title="Fallback used" class="mb-8">
        <code class="code">{{ implode(' → ', $attempts) }}</code> —
        <strong>{{ $attempts[0] }}</strong> returned nothing, so the same search was re-run with <strong>{{ last($attempts) }}</strong>.
    </x-callout>
@elseif($query && $engine === 'bm25' && !$useIndex)
    <x-index-not-built model="App\Models\User" class="mb-8" />
@elseif($query && $useIndex && $results->isNotEmpty())
    <x-callout tone="success" class="mb-8">
        Answered by the <strong>BM25 index</strong>. The algorithm and the fallback apply to the LIKE table scan.
    </x-callout>
@elseif($query && $fallback !== '' && count($attempts) === 1)
    <x-callout tone="success" class="mb-8">
        Answered by <strong>{{ $attempts[0] }}</strong>; the <strong>{{ $fallback }}</strong> fallback was not needed.
    </x-callout>
@endif

@if($query)
    <x-results :count="$results->total()">
        @if($results->isEmpty() && $results->currentPage() > 1)
            <x-past-end :paginator="$results" />
        @elseif($results->isEmpty())
            <x-empty icon="search">
                No users match “{{ $query }}”.
                @if($useIndex && $verifiedOnly)
                    <x-slot:hint>No verified user matches. <a href="{{ request()->fullUrlWithQuery(['verified_only' => null]) }}" class="text-link underline">Include unverified users</a>.</x-slot:hint>
                @elseif($useIndex)
                    <x-slot:hint>
                        <x-index-miss />
                        The index matches whole words; for prefixes, see the <a href="{{ route('search.products') }}" class="text-link underline">as-you-type box on product search</a>.
                    </x-slot:hint>
                @elseif(($attempts[0] ?? null) === 'metaphone')
                    <x-slot:hint>Metaphone matches the sound of the whole name, so one word of it finds nothing: try “jane smith”.</x-slot:hint>
                @else
                    <x-slot:hint>Raise the typo tolerance, switch algorithm, or add a fallback.</x-slot:hint>
                @endif
            </x-empty>
        @else
            <ul class="divide-y divide-line">
                @foreach($results as $user)
                    <li class="row">
                        <div class="min-w-0">
                            <p class="font-medium text-ink">@fuzzyHighlight($user, 'name')</p>
                            <p class="truncate text-sm text-muted">{{ $user->email }}</p>
                        </div>
                        @isset($user->_score)
                            <span class="badge bg-accent/15 text-accent" title="Relevance score"><span class="sr-only">Relevance score </span>{{ number_format($user->_score, 2) }}</span>
                        @endisset
                    </li>
                @endforeach
            </ul>
        @endif
    </x-results>
    <x-pager :paginator="$results" class="mt-4" />

    @if(!empty($debugInfo))
        <details class="card mt-6 p-5">
            <summary class="cursor-pointer font-mono text-xs uppercase tracking-[0.15em] text-muted transition-colors duration-200 hover:text-ink">Debug info</summary>
            <pre class="mt-4 overflow-x-auto font-mono text-xs leading-relaxed text-link">{{ json_encode($debugInfo, JSON_PRETTY_PRINT) }}</pre>
        </details>
    @endif
@endif
@endsection

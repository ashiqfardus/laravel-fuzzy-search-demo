{{-- The empty-state hint of a BM25 search that found nothing: the index never falls back to a table scan on its own (a miss on a large table would cost seconds), so the scan is one click away instead. --}}
The BM25 index found no word close enough.
<a href="{{ request()->fullUrlWithQuery(['engine' => 'like', 'page' => null]) }}" class="text-link underline">Try the LIKE table scan</a>:
it finds substrings and fuzzy near misses the index does not, but reads every row, so it is slow on large tables.

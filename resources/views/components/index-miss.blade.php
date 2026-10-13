{{-- The empty-state hint of a BM25 search that found nothing: the index never falls back to a table scan on its own (a miss on a large table would cost seconds), so the scan is one click away instead. --}}
The index found no word close enough.
Try the same search on the <a href="{{ request()->fullUrlWithQuery(['engine' => 'like', 'page' => null]) }}" class="text-link underline">LIKE table scan</a> (slow on large tables).

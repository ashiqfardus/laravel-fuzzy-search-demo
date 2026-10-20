@props(['paginator'])
<x-empty icon="inbox">
    No results on page {{ $paginator->currentPage() }}.
    <x-slot:hint><a href="{{ $paginator->url(1) }}" class="text-link hover:underline">Back to page 1</a>@if($paginator->total() > 0) of {{ $paginator->lastPage() }}@endif.</x-slot:hint>
</x-empty>

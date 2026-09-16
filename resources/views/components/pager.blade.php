@props(['paginator'])
@if($paginator->hasPages())
<div {{ $attributes->merge(['class' => 'flex items-center justify-between gap-4']) }}>
    @if($paginator->onFirstPage())
        <span class="btn-ghost pointer-events-none opacity-50">Previous</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" class="btn-ghost">Previous</a>
    @endif

    @if($paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
        <span class="font-mono text-xs text-muted">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }} · {{ $paginator->total() }} results</span>
    @else
        <span class="font-mono text-xs text-muted">Page {{ $paginator->currentPage() }}</span>
    @endif

    @if($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" class="btn-ghost">Next</a>
    @else
        <span class="btn-ghost pointer-events-none opacity-50">Next</span>
    @endif
</div>
@endif

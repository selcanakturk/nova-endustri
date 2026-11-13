@props(['paginator'])
<div class="admin-pagination">
    <span>{{ $paginator->total() }} kayıttan {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} gösteriliyor</span>
    @if($paginator->hasPages())
        <nav aria-label="Liste sayfaları">
            @if($paginator->onFirstPage())<span aria-disabled="true">← Önceki</span>@else<a href="{{ $paginator->previousPageUrl() }}" rel="prev">← Önceki</a>@endif
            <span>{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
            @if($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next">Sonraki →</a>@else<span aria-disabled="true">Sonraki →</span>@endif
        </nav>
    @endif
</div>

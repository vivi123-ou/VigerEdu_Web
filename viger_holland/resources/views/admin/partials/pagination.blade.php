@if ($paginator->hasPages())
    <nav class="pager" aria-label="Phân trang">
        @if ($paginator->onFirstPage())
            <span class="pager__item is-disabled">← Trước</span>
        @else
            <a class="pager__item" href="{{ $paginator->previousPageUrl() }}" rel="prev">← Trước</a>
        @endif

        <span class="pager__info">Trang {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a class="pager__item" href="{{ $paginator->nextPageUrl() }}" rel="next">Sau →</a>
        @else
            <span class="pager__item is-disabled">Sau →</span>
        @endif
    </nav>
@endif

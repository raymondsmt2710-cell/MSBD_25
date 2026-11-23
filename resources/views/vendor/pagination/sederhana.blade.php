@if ($paginator->hasPages())
    <nav style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;font-size:13px">
        @if ($paginator->onFirstPage())
            <span class="btn abu" style="opacity:.5;cursor:default">&laquo; Sebelumnya</span>
        @else
            <a class="btn abu" href="{{ $paginator->previousPageUrl() }}" style="text-decoration:none">&laquo; Sebelumnya</a>
        @endif

        <span style="color:#6b7280;padding:0 4px">
            Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}
        </span>

        @if ($paginator->hasMorePages())
            <a class="btn abu" href="{{ $paginator->nextPageUrl() }}" style="text-decoration:none">Berikutnya &raquo;</a>
        @else
            <span class="btn abu" style="opacity:.5;cursor:default">Berikutnya &raquo;</span>
        @endif
    </nav>
@endif

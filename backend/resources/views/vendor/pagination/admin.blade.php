@if ($paginator->hasPages())
    <div class="pagination-wrap">
        <p class="pagination-info">
            @if ($paginator->firstItem())
                {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} из {{ $paginator->total() }}
            @endif
        </p>
        <div class="pagination" role="navigation" aria-label="Pagination">
            @if ($paginator->onFirstPage())
                <span class="disabled" aria-disabled="true">‹</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Предыдущая">‹</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="disabled">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="active" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Следующая">›</a>
            @else
                <span class="disabled" aria-disabled="true">›</span>
            @endif
        </div>
    </div>
@endif

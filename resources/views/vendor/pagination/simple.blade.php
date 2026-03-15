@if ($paginator->hasPages())
<nav class="lantaka-pagination">
    {{-- Previous --}}
    @if ($paginator->onFirstPage())
        <span class="page-btn page-btn--disabled">&lsaquo;</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" class="page-btn">&lsaquo;</a>
    @endif

    {{-- Page numbers --}}
    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="page-btn page-btn--dots">{{ $element }}</span>
        @endif

        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="page-btn page-btn--active">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    {{-- Next --}}
    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" class="page-btn">&rsaquo;</a>
    @else
        <span class="page-btn page-btn--disabled">&rsaquo;</span>
    @endif
</nav>

<style>
.lantaka-pagination {
    display: flex;
    align-items: center;
    gap: 4px;
    justify-content: flex-end;
    padding: 12px 0;
    flex-wrap: wrap;
}

.page-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    padding: 0 10px;
    border-radius: 6px;
    font-size: 0.82rem;
    font-weight: 500;
    text-decoration: none;
    border: 1px solid #e5e7eb;
    background: #fff;
    color: #374151;
    cursor: pointer;
    transition: background 0.15s, color 0.15s, border-color 0.15s;
}

.page-btn:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}

.page-btn--active {
    background: #1e3a5f;
    color: #fff;
    border-color: #1e3a5f;
    cursor: default;
    font-weight: 700;
}

.page-btn--disabled {
    color: #d1d5db;
    border-color: #f3f4f6;
    background: #fafafa;
    cursor: not-allowed;
}

.page-btn--dots {
    border: none;
    background: none;
    cursor: default;
    color: #9ca3af;
}
</style>
@endif

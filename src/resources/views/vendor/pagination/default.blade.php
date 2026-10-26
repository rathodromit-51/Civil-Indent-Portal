@if ($paginator->hasPages())
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; width: 100%;">
        <div style="color: #64748b; font-size: 0.825rem;">
            Showing <strong>{{ $paginator->firstItem() }}</strong> to <strong>{{ $paginator->lastItem() }}</strong> of <strong>{{ $paginator->total() }}</strong> {{ $itemLabel ?? 'results' }}
        </div>

        <div style="display: inline-flex; align-items: center; gap: 6px;">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span style="display: inline-flex; align-items: center; padding: 6px 12px; font-size: 0.8rem; border: 1px solid #e2e8f0; border-radius: 6px; color: #94a3b8; background: #f8fafc; cursor: not-allowed; user-select: none;">
                    &lsaquo; Prev
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" style="display: inline-flex; align-items: center; padding: 6px 12px; font-size: 0.8rem; border: 1px solid #cbd5e1; border-radius: 6px; color: #0f172a; background: #ffffff; text-decoration: none; font-weight: 500;">
                    &lsaquo; Prev
                </a>
            @endif

            {{-- Numeric Page Links --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span style="padding: 6px 10px; font-size: 0.8rem; color: #94a3b8;">{{ $element }}</span>
                @elseif (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 32px; height: 32px; padding: 0 10px; font-size: 0.8rem; border: 1px solid #2563eb; border-radius: 6px; color: #ffffff; background: #2563eb; font-weight: 600;">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" style="display: inline-flex; align-items: center; justify-content: center; min-width: 32px; height: 32px; padding: 0 10px; font-size: 0.8rem; border: 1px solid #cbd5e1; border-radius: 6px; color: #0f172a; background: #ffffff; text-decoration: none;">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" style="display: inline-flex; align-items: center; padding: 6px 12px; font-size: 0.8rem; border: 1px solid #cbd5e1; border-radius: 6px; color: #0f172a; background: #ffffff; text-decoration: none; font-weight: 500;">
                    Next &rsaquo;
                </a>
            @else
                <span style="display: inline-flex; align-items: center; padding: 6px 12px; font-size: 0.8rem; border: 1px solid #e2e8f0; border-radius: 6px; color: #94a3b8; background: #f8fafc; cursor: not-allowed; user-select: none;">
                    Next &rsaquo;
                </span>
            @endif
        </div>
    </div>
@endif
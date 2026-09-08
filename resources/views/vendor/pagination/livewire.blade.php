@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Pagination">
        <div class="pagination-summary">
            Showing <strong>{{ $paginator->firstItem() }}</strong> to <strong>{{ $paginator->lastItem() }}</strong> of <strong>{{ $paginator->total() }}</strong>
        </div>
        <div class="pagination-controls">
            @if ($paginator->onFirstPage())
                <span class="pagination-button is-disabled" aria-disabled="true">Previous</span>
            @else
                <button class="pagination-button" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled">Previous</button>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pagination-ellipsis">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pagination-button is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <button class="pagination-button pagination-number" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" wire:loading.attr="disabled">{{ $page }}</button>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <button class="pagination-button" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled">Next</button>
            @else
                <span class="pagination-button is-disabled" aria-disabled="true">Next</span>
            @endif
        </div>
    </nav>
@endif

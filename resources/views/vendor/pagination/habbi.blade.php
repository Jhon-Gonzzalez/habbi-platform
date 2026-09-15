@if ($paginator->hasPages())
    <nav class="hb-pager" role="navigation" aria-label="Paginación">

        @if ($paginator->onFirstPage())
            <span class="hb-pager__item is-disabled" aria-disabled="true">Anterior</span>
        @else
            <a class="hb-pager__item" href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>
        @endif

        <div class="hb-pager__pages">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="hb-pager__gap">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="hb-pager__item is-active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="hb-pager__item" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        @if ($paginator->hasMorePages())
            <a class="hb-pager__item" href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente</a>
        @else
            <span class="hb-pager__item is-disabled" aria-disabled="true">Siguiente</span>
        @endif

    </nav>
@endif

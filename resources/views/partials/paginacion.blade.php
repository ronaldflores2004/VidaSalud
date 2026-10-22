@if ($paginator->hasPages())
<nav class="paginacion">
    <span>Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }}</span>
    <div>
        @if ($paginator->onFirstPage())<span class="btn btn-ghost btn-sm off">Anterior</span>
        @else<a class="btn btn-ghost btn-sm" href="{{ $paginator->previousPageUrl() }}">Anterior</a>@endif
        @if ($paginator->hasMorePages())<a class="btn btn-ghost btn-sm" href="{{ $paginator->nextPageUrl() }}">Siguiente</a>
        @else<span class="btn btn-ghost btn-sm off">Siguiente</span>@endif
    </div>
</nav>
@endif

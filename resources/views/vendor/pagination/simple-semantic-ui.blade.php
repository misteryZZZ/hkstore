@if ($paginator->hasPages())
<div class="ui pagination menu mobile-only shadowless" role="navigation">
  {{-- Previous Page Link --}}
  @if ($paginator->onFirstPage())
  <a class="item disabled" aria-disabled="true">
    @lang('pagination.previous')
  </a>
  @else
  <a href="{{ $paginator->previousPageUrl() }}" class="item">
    @lang('pagination.previous')
  </a>
  @endif
  
  {{-- Next Page Link --}}
  @if ($paginator->hasMorePages())
  <a class="item" href="{{ $paginator->nextPageUrl() }}" rel="next">
    @lang('pagination.next')
  </a>
  @else
  <a class="item disabled" aria-disabled="true">
    @lang('pagination.next')
  </a>
  @endif
</div>
@endif

@props(['paginator'])

@if (method_exists($paginator, 'hasPages') && $paginator->hasPages())
    <div class="mt-4">
        {{ $paginator->withQueryString()->links() }}
    </div>
@endif

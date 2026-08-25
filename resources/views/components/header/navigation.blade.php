@props(['menuSlug' => 'primary'])

@php
    $menu = app(\App\Services\LayoutResolverService::class)->primaryMenu();
    if ($menuSlug !== 'primary') {
        $menu = \App\Models\Menu::query()->with('items.page')->where('slug', $menuSlug)->first() ?? $menu;
    }
@endphp

@if ($menu)
    <ul class="site-nav" role="list">
        @foreach ($menu->items as $item)
            <li>
                <a href="{{ $item->url ?: ($item->page ? url('/'.$item->page->slug) : '#') }}">
                    {{ $item->title }}
                </a>
            </li>
        @endforeach
    </ul>
@endif

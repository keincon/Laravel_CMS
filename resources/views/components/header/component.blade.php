@props(['component'])

@php
    $type = $component['type'] ?? 'text';
    $settings = $component['settings'] ?? [];
@endphp

@switch($type)
    @case('logo')
        <a href="{{ url('/') }}" style="text-decoration:none;color:inherit;font-weight:700;font-size:1.15rem">
            {{ $settings['text'] ?? app(\App\Services\LayoutResolverService::class)->siteName() }}
        </a>
        @break

    @case('navigation')
        <x-header.navigation :menu-slug="$settings['menu'] ?? 'primary'" />
        @break

    @case('search')
        <form class="site-search" action="{{ url('/blog') }}" method="GET" role="search">
            <input type="search" name="search" placeholder="Search…" aria-label="Search">
        </form>
        @break

    @case('button')
        <a class="cta-btn" href="{{ $settings['url'] ?? '#' }}">{{ $settings['label'] ?? 'Button' }}</a>
        @break

    @case('social')
        <x-header.social :links="$settings['links'] ?? []" />
        @break

    @case('language')
        <span class="small">{{ strtoupper(app()->getLocale()) }}</span>
        @break

    @case('login')
        @auth
            <a href="{{ route('admin.dashboard') }}">Account</a>
        @else
            <a href="{{ route('login') }}">Login</a>
        @endauth
        @break

    @case('html')
        {!! $settings['html'] ?? '' !!}
        @break

    @default
        <span>{{ $settings['text'] ?? '' }}</span>
@endswitch

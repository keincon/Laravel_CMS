@props([
    'page' => null,
    'post' => null,
    'context' => 'page',
    'seoPath' => null,
    'seoMeta' => null,
    'dynamicConfig' => null,
    'breadcrumbs' => [],
    'fullBleed' => false,
])

@php
    $themeManager = app(\App\Services\Themes\ThemeManager::class);
    $activeThemeSlug = $themeManager->activeSlug();
    $themeStylesheets = $themeManager->stylesheetUrls('aoyama');
    $themeScripts = $themeManager->scriptUrls('aoyama');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-seo :page="$page" :post="$post" :path="$seoPath" :meta="$seoMeta" />
    <x-ogp :page="$page" :post="$post" :path="$seoPath" :meta="$seoMeta" />
    <x-json-ld :page="$page" :post="$post" />
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=noto-sans-jp:400,500,700,800&display=swap" rel="stylesheet">
    {{-- Public aoyama theme is self-contained; skip CMS UI framework (Tailwind/Bootstrap) to avoid Preflight conflicts. --}}
    <x-theme />
    @foreach ($themeStylesheets as $href)
        <link rel="stylesheet" href="{{ $href }}">
    @endforeach
    <x-custom-code position="head" :page="$page" :post="$post" />
    @stack('head')
</head>
<body class="theme-aoyama ao-site theme-{{ $activeThemeSlug }}">
    <x-custom-code position="body_open" :page="$page" :post="$post" />
    <x-admin-bar :page="$page" :post="$post" />

    {{-- Theme-owned chrome: skip LayoutResolver CMS header/footer --}}
    @include('themes.aoyama.partials.header')

    <main class="ao-main {{ $fullBleed ? 'is-full-bleed' : '' }}">
        @if (! $fullBleed)
            <div class="ao-container">
                @if (! empty($breadcrumbs))
                    <nav aria-label="Breadcrumb">
                        <ol class="ao-breadcrumb">
                            @foreach ($breadcrumbs as $item)
                                <li>
                                    @if (! empty($item['url']))
                                        <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                                    @else
                                        <span aria-current="page">{{ $item['label'] }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                @endif
                {{ $slot }}
            </div>
        @else
            {{ $slot }}
        @endif
    </main>

    @include('themes.aoyama.partials.footer')

    <button type="button" class="ao-back-top" data-ao-back-top aria-label="ページ上部へ">↑</button>

    @foreach ($themeScripts as $src)
        <script src="{{ $src }}" defer></script>
    @endforeach
    @stack('scripts')
    <x-custom-code position="body_close" :page="$page" :post="$post" />
</body>
</html>

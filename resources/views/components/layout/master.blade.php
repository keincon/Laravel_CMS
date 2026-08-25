@props([
    'page' => null,
    'post' => null,
    'context' => 'page',
    'seoPath' => null,
])

@php
    $content = $page ?? $post;
    $layouts = app(\App\Services\LayoutResolverService::class);
    $header = $layouts->resolveHeader($content, $context);
    $footer = $layouts->resolveFooter($content, $context);
    $sidebar = $layouts->resolveSidebar($content, $context);
    $dims = $layouts->dimensionCss();
    $ui = app(\App\Services\UIFrameworkService::class);
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-seo :page="$page" :post="$post" :path="$seoPath" />
    <x-ogp :page="$page" :post="$post" :path="$seoPath" />
    <x-json-ld :page="$page" :post="$post" />
    @foreach ($ui->stylesheetUrls() as $href)
        <link rel="stylesheet" href="{{ $href }}">
    @endforeach
    <x-theme />
    <style>{!! $dims['css'] !!}</style>
    <style>
        body { margin: 0; background: var(--color-background); color: var(--color-text); font-family: "Segoe UI", system-ui, sans-serif; }
        a { color: var(--color-primary); }
        .site-container { width: min(100% - 2rem, var(--site-container-width)); margin-inline: auto; }
        .site-content { width: min(100%, var(--site-content-width)); }
        .site-main-grid { display: grid; gap: 2rem; }
        .site-main-grid.has-sidebar-right { grid-template-columns: minmax(0, 1fr) var(--site-sidebar-width); }
        .site-main-grid.has-sidebar-left { grid-template-columns: var(--site-sidebar-width) minmax(0, 1fr); }
        @media (max-width: 900px) {
            .site-main-grid.has-sidebar-right,
            .site-main-grid.has-sidebar-left { grid-template-columns: 1fr; }
        }
        .site-header { background: var(--color-surface); color: var(--color-text); border-bottom: 1px solid color-mix(in srgb, var(--color-text) 10%, transparent); }
        .site-header.is-sticky { position: sticky; top: 0; z-index: 50; }
        .site-header-row { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; padding: .75rem 0; }
        .site-header-row.main { min-height: 64px; justify-content: space-between; }
        .site-nav { display: flex; gap: 1rem; flex-wrap: wrap; list-style: none; margin: 0; padding: 0; }
        .site-nav a { text-decoration: none; color: inherit; }
        .site-footer { background: var(--color-surface); border-top: 1px solid color-mix(in srgb, var(--color-text) 10%, transparent); margin-top: 3rem; padding: 2.5rem 0 1.5rem; }
        .footer-grid { display: grid; gap: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); }
        .footer-bottom { margin-top: 2rem; padding-top: 1rem; border-top: 1px solid color-mix(in srgb, var(--color-text) 10%, transparent); font-size: .9rem; opacity: .8; }
        .mobile-toggle { display: none; background: transparent; border: 1px solid currentColor; border-radius: .5rem; padding: .4rem .7rem; }
        @media (max-width: 768px) {
            .desktop-only { display: none !important; }
            .mobile-toggle { display: inline-flex; }
        }
        .site-search input { border: 1px solid color-mix(in srgb, var(--color-text) 20%, transparent); border-radius: .5rem; padding: .4rem .7rem; background: var(--color-background); color: inherit; }
        .cta-btn { display: inline-flex; align-items: center; padding: .5rem 1rem; border-radius: .5rem; background: var(--color-primary); color: #fff !important; text-decoration: none; font-weight: 600; }
    </style>
    @stack('head')
</head>
<body>
    @if ($header['show'] && $header['structure'])
        <x-header.master :structure="$header['structure']" />
    @endif

    <main class="site-container" style="padding: 2rem 0;">
        <div class="site-main-grid {{ $sidebar === 'right' ? 'has-sidebar-right' : ($sidebar === 'left' ? 'has-sidebar-left' : '') }}">
            @if ($sidebar === 'left')
                <aside>
                    <x-layout.sidebar :context="$context" />
                </aside>
            @endif

            <div class="{{ in_array($sidebar, ['left', 'right'], true) ? '' : 'site-content' }}" style="{{ $sidebar === 'none' ? 'margin-inline:auto' : '' }}">
                {{ $slot }}
            </div>

            @if ($sidebar === 'right')
                <aside>
                    <x-layout.sidebar :context="$context" />
                </aside>
            @endif
        </div>
    </main>

    @if ($footer['show'] && $footer['structure'])
        <x-footer.master :structure="$footer['structure']" />
    @endif

    @foreach ($ui->scriptUrls() as $src)
        <script src="{{ $src }}" defer></script>
    @endforeach
    @stack('scripts')
</body>
</html>

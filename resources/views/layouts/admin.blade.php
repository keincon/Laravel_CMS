<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — {{ config('cms.name') }}</title>
    @php $ui = app(\App\Services\UIFrameworkService::class); @endphp
    @foreach ($ui->stylesheetUrls() as $href)
        <link rel="stylesheet" href="{{ $href }}">
    @endforeach
    <x-theme />
    <style>
        body { margin: 0; background: var(--color-background); color: var(--color-text); font-family: "Segoe UI", system-ui, sans-serif; }
        .admin-shell { display: grid; grid-template-columns: 240px 1fr; min-height: 100vh; }
        .admin-nav { background: var(--color-surface); border-right: 1px solid #e2e8f0; padding: 1.25rem; }
        .admin-nav a { display: block; padding: .55rem .75rem; border-radius: .5rem; color: inherit; text-decoration: none; margin-bottom: .25rem; }
        .admin-nav a:hover, .admin-nav a.active { background: color-mix(in srgb, var(--color-primary) 12%, transparent); color: var(--color-primary); }
        .admin-main { padding: 1.5rem; }
        .admin-brand { font-weight: 700; margin-bottom: 1.25rem; color: var(--color-primary); }
        .btn-primary, .bg-primary { background-color: var(--color-primary) !important; border-color: var(--color-primary) !important; }
        .text-primary { color: var(--color-primary) !important; }
        @media (max-width: 900px) { .admin-shell { grid-template-columns: 1fr; } .admin-nav { border-right: 0; border-bottom: 1px solid #e2e8f0; } }
    </style>
    @stack('head')
</head>
<body>
<div class="admin-shell">
    <aside class="admin-nav">
        <div class="admin-brand">{{ config('cms.name') }}</div>
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
        <div class="small text-muted mt-3 mb-1 px-2">Content</div>
        <a href="{{ route('admin.pages.index') }}" class="{{ request()->routeIs('admin.pages.*') ? 'active' : '' }}">Static Pages</a>
        <a href="{{ route('admin.posts.index') }}" class="{{ request()->routeIs('admin.posts.*') ? 'active' : '' }}">Posts</a>
        <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">Categories</a>
        <a href="{{ route('admin.tags.index') }}" class="{{ request()->routeIs('admin.tags.*') ? 'active' : '' }}">Tags</a>
        <div class="small text-muted mt-3 mb-1 px-2">Appearance</div>
        <a href="{{ route('admin.headers.index') }}" class="{{ request()->routeIs('admin.headers.*') ? 'active' : '' }}">Header</a>
        <a href="{{ route('admin.footers.index') }}" class="{{ request()->routeIs('admin.footers.*') ? 'active' : '' }}">Footer</a>
        <a href="{{ route('admin.appearance.layout') }}" class="{{ request()->routeIs('admin.appearance.layout') ? 'active' : '' }}">Master Layout</a>
        <a href="{{ route('admin.appearance.dynamic-pages.index') }}" class="{{ request()->routeIs('admin.appearance.dynamic-pages.*') ? 'active' : '' }}">Dynamic Pages</a>
        <a href="{{ route('admin.appearance.colors') }}" class="{{ request()->routeIs('admin.appearance.colors') ? 'active' : '' }}">Theme Colors</a>
        <a href="{{ route('admin.appearance.mode') }}" class="{{ request()->routeIs('admin.appearance.mode') ? 'active' : '' }}">Color Mode</a>
        <div class="small text-muted mt-3 mb-1 px-2">Settings</div>
        <a href="{{ route('admin.settings.seo') }}" class="{{ request()->routeIs('admin.settings.seo') ? 'active' : '' }}">SEO</a>
        <a href="{{ route('admin.settings.seo.templates') }}" class="{{ request()->routeIs('admin.settings.seo.templates*') ? 'active' : '' }}">SEO Templates</a>
        <a href="{{ route('admin.settings.ogp') }}" class="{{ request()->routeIs('admin.settings.ogp') ? 'active' : '' }}">Social / OGP</a>
        <a href="{{ route('admin.settings.permalinks') }}" class="{{ request()->routeIs('admin.settings.permalinks') ? 'active' : '' }}">Permalinks</a>
        <a href="{{ route('admin.settings.reading') }}" class="{{ request()->routeIs('admin.settings.reading*') ? 'active' : '' }}">Reading</a>
        <a href="{{ route('admin.settings.api') }}" class="{{ request()->routeIs('admin.settings.api') ? 'active' : '' }}">API</a>
        <div class="small text-muted mt-3 mb-1 px-2">Users</div>
        <a href="{{ route('admin.users.tokens') }}" class="{{ request()->routeIs('admin.users.tokens*') ? 'active' : '' }}">API Tokens</a>
        <div class="mt-4 px-2">
            <a href="{{ route('home') }}">← Website</a>
            @auth
                <form method="POST" action="{{ route('logout') }}" class="mt-2">@csrf
                    <button class="btn btn-sm btn-outline-secondary" type="submit">Log out</button>
                </form>
            @endauth
        </div>
    </aside>
    <main class="admin-main">
        @if (session('success'))
            <x-ui.alert type="success">{{ session('success') }}</x-ui.alert>
        @endif
        @if (session('error'))
            <x-ui.alert type="danger">{{ session('error') }}</x-ui.alert>
        @endif
        @yield('content')
    </main>
</div>
@foreach ($ui->scriptUrls() as $src)
    <script src="{{ $src }}" defer></script>
@endforeach
@stack('scripts')
</body>
</html>

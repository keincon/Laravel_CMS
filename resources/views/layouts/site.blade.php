<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-seo :page="$page ?? null" :post="$post ?? null" :path="$seoPath ?? null" />
    <x-ogp :page="$page ?? null" :post="$post ?? null" :path="$seoPath ?? null" />
    <x-json-ld :page="$page ?? null" :post="$post ?? null" />
    @php $ui = app(\App\Services\UIFrameworkService::class); @endphp
    @foreach ($ui->stylesheetUrls() as $href)
        <link rel="stylesheet" href="{{ $href }}">
    @endforeach
    <x-theme />
    <style>
        body { margin: 0; background: var(--color-background); color: var(--color-text); font-family: "Segoe UI", system-ui, sans-serif; }
        a { color: var(--color-primary); }
        .site-wrap { max-width: 960px; margin: 0 auto; padding: 2rem 1.25rem; }
        .site-nav a { margin-right: 1rem; text-decoration: none; }
        .btn-primary { background: var(--color-primary); border-color: var(--color-primary); color: #fff; }
    </style>
    @stack('head')
</head>
<body>
    <div class="site-wrap">
        <nav class="site-nav mb-4">
            <a href="{{ url('/') }}">Home</a>
            <a href="{{ url('/about') }}">About</a>
            <a href="{{ url('/blog') }}">Blog</a>
            <a href="{{ url('/contact') }}">Contact</a>
            <a href="{{ route('admin.dashboard') }}">Admin</a>
        </nav>
        @yield('content')
    </div>
</body>
</html>

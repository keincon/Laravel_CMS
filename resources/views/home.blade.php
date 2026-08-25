<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ \App\Models\CmsSetting::getValue('site_name', config('app.name')) }}</title>
    @php $ui = app(\App\Services\UIFrameworkService::class); @endphp
    @foreach ($ui->stylesheetUrls() as $href)
        <link rel="stylesheet" href="{{ $href }}">
    @endforeach
    <style>
        body { min-height: 100vh; margin: 0; background: #f8fafc; color: #0f172a; font-family: "Segoe UI", system-ui, sans-serif; }
        .wrap { max-width: 900px; margin: 0 auto; padding: 3rem 1.25rem; }
        nav a { margin-right: 1rem; color: #0369a1; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrap">
        <nav class="mb-4">
            <a href="{{ url('/') }}">Home</a>
            <a href="{{ url('/about') }}">About</a>
            <a href="{{ url('/blog') }}">Blog</a>
            <a href="{{ url('/contact') }}">Contact</a>
            <a href="{{ route('admin.dashboard') }}">Admin</a>
        </nav>
        <h1>{{ \App\Models\CmsSetting::getValue('site_name', 'My Website') }}</h1>
        <p>{{ \App\Models\CmsSetting::getValue('site_description', 'Welcome to your new website.') }}</p>
        <p class="text-muted">Frontend content management arrives in later phases. Your installation and defaults are ready.</p>
    </div>
</body>
</html>

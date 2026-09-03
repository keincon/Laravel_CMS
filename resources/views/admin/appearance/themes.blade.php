@extends('layouts.admin')

@section('title', 'Themes')

@section('content')
<div class="admin-page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
    <div>
        <h1>Themes</h1>
        <p class="muted mb-0">
            A <strong>theme</strong> is the live folder under <code>resources/views/themes/{slug}</code>
            (screens, CSS, JS, colors). A <strong>pack</strong> is that theme zipped for download/import.
        </p>
    </div>
    <form method="POST" action="{{ route('admin.appearance.themes.rebuild') }}">
        @csrf
        <button type="submit" class="btn btn-outline-secondary btn-sm">Rebuild download packs</button>
    </form>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="panel mb-4">
    <h2 class="h6">Theme vs pack</h2>
    <ul class="mb-0 small">
        <li><strong>Theme</strong> — installed on disk: Blade <em>screens</em> (<code>pages/</code>, <code>dynamic/</code>), <code>assets/*.css</code>, <code>assets/*.js</code>, images, and <code>theme.json</code>.</li>
        <li><strong>Pack</strong> — ZIP export of a theme for sharing or reinstalling. Import unpacks it back into a theme folder.</li>
        <li>Missing screens fall back to the <code>default</code> theme. Raw PHP is blocked; <code>.blade.php</code> screens are allowed.</li>
    </ul>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="panel h-100">
            <h2 class="h5">Create theme</h2>
            <p class="page-intro">Scaffolds screens, CSS, and JS stubs you can edit on disk.</p>
            <form method="POST" action="{{ route('admin.appearance.themes.scaffold') }}" class="d-grid gap-3">
                @csrf
                <div>
                    <label class="form-label" for="scaffold_slug">Slug</label>
                    <input type="text" name="slug" id="scaffold_slug" class="form-control" placeholder="my-store" required pattern="[a-z0-9\-]+" maxlength="64">
                </div>
                <div>
                    <label class="form-label" for="scaffold_name">Display name</label>
                    <input type="text" name="name" id="scaffold_name" class="form-control" placeholder="My Store" maxlength="120">
                </div>
                <label class="form-check">
                    <input type="checkbox" name="activate" value="1" class="form-check-input">
                    <span class="form-check-label">Activate after create</span>
                </label>
                <div>
                    <button type="submit" class="btn btn-primary">Scaffold theme</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="panel h-100">
            <h2 class="h5">What you can put in a theme</h2>
            <pre class="small bg-dark text-white p-3 rounded mb-0" style="white-space:pre-wrap">themes/{slug}/
  theme.json          # name, colors, stylesheets[], scripts[]
  pages/*.blade.php   # page screens/templates
  dynamic/*.blade.php # blog, post, archive, search, 404…
  partials/           # @@include('themes.{slug}.partials.name')
  assets/theme.css    # styles (also extra *.css)
  assets/theme.js     # scripts (also extra *.js)
  assets/images/…     # images, fonts, etc.</pre>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    @foreach ($themes as $slug => $theme)
        @php
            $colors = $theme['colors'] ?? [];
            $isActive = $slug === $active;
            $screenCount = count($theme['screens'] ?? []);
            $cssCount = count($theme['stylesheets'] ?? []);
            $jsCount = count($theme['scripts'] ?? []);
        @endphp
        <div class="col-md-6 col-xl-4">
            <div class="panel h-100" style="border: 1px solid {{ $isActive ? 'var(--bs-primary, #0d6efd)' : 'color-mix(in srgb, currentColor 12%, transparent)' }};">
                <div class="d-flex gap-2 mb-3" aria-hidden="true">
                    @foreach (['primary_color','secondary_color','accent_color','background_color','surface_color','text_color'] as $key)
                        <span style="width:1.25rem;height:1.25rem;border-radius:.35rem;background:{{ $colors[$key] ?? '#ccc' }};border:1px solid rgba(0,0,0,.08);"></span>
                    @endforeach
                </div>
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div>
                        <h2 class="h5 mb-1">{{ $theme['name'] ?? $slug }}</h2>
                        <div class="text-muted small">{{ $slug }} · v{{ $theme['version'] ?? '1.0.0' }}</div>
                    </div>
                    @if ($isActive)
                        <span class="badge text-bg-primary">Active</span>
                    @endif
                </div>
                <p class="small mb-2">{{ $theme['description'] ?? 'No description.' }}</p>
                <p class="small text-muted mb-3">{{ $screenCount }} screens · {{ $cssCount }} CSS · {{ $jsCount }} JS</p>
                <div class="d-flex flex-wrap gap-2">
                    @unless ($isActive)
                        <form method="POST" action="{{ route('admin.appearance.themes.activate', $slug) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">Activate</button>
                        </form>
                    @endunless
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.appearance.themes.export', $slug) }}">Export theme ZIP</a>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.appearance.themes.pack', $slug) }}">Download pack</a>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="panel">
            <h2 class="h5">Import theme (from pack)</h2>
            <p class="page-intro">Upload a ZIP with <code>theme.json</code>, optional Blade screens, and <code>assets/</code>. Raw PHP/executables are rejected; <code>.blade.php</code> is allowed.</p>
            <form method="POST" action="{{ route('admin.appearance.themes.import') }}" enctype="multipart/form-data" class="d-grid gap-3">
                @csrf
                <div>
                    <label class="form-label" for="package">Theme pack ZIP</label>
                    <input type="file" name="package" id="package" class="form-control" accept=".zip,application/zip" required>
                </div>
                <label class="form-check">
                    <input type="checkbox" name="activate" value="1" class="form-check-input">
                    <span class="form-check-label">Activate after import</span>
                </label>
                <div>
                    <button type="submit" class="btn btn-primary">Import pack → theme</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel">
            <h2 class="h5">Downloadable packs</h2>
            <p class="page-intro">Prebuilt ZIPs in <code>storage/app/theme-packs/</code> — same content as each theme folder.</p>
            <ul class="list-unstyled mb-0">
                @forelse ($packs as $pack)
                    <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span>{{ $pack['name'] }} <span class="text-muted small">({{ number_format($pack['size'] / 1024, 1) }} KB)</span></span>
                        <a href="{{ route('admin.appearance.themes.pack', $pack['slug']) }}">Download pack</a>
                    </li>
                @empty
                    <li class="text-muted">No packs yet — click “Rebuild download packs”.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection

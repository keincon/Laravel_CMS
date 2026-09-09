@extends('layouts.admin')

@section('title', __('admin.nav.themes'))

@section('content')
<div class="admin-page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
    <div>
        <h1>{{ __('admin.nav.themes') }}</h1>
        <p class="muted mb-0">{!! __('admin.appearance.themes_intro_html') !!}</p>
    </div>
    <form method="POST" action="{{ route('admin.appearance.themes.rebuild') }}">
        @csrf
        <button type="submit" class="btn btn-outline-secondary btn-sm">{{ __('admin.appearance.themes_rebuild') }}</button>
    </form>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<x-admin.help-next context="themes" />

<div class="panel mb-4">
    <h2 class="h6">{{ __('admin.appearance.themes_vs_pack') }}</h2>
    <ul class="mb-0 small">
        <li><strong>{{ __('admin.appearance.themes_vs_theme_label') }}</strong> — {{ __('admin.appearance.themes_vs_theme') }}</li>
        <li><strong>{{ __('admin.appearance.themes_vs_pack_label') }}</strong> — {{ __('admin.appearance.themes_vs_pack_item') }}</li>
        <li>{{ __('admin.appearance.themes_vs_fallback') }}</li>
    </ul>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="panel h-100">
            <h2 class="h5">{{ __('admin.appearance.create_theme') }}</h2>
            <p class="page-intro">{{ __('admin.appearance.themes_scaffold_help') }}</p>
            <form method="POST" action="{{ route('admin.appearance.themes.scaffold') }}" class="d-grid gap-3">
                @csrf
                <div>
                    <label class="form-label" for="scaffold_slug">{{ __('admin.appearance.themes_slug') }}</label>
                    <input type="text" name="slug" id="scaffold_slug" class="form-control" placeholder="my-store" required pattern="[a-z0-9\-]+" maxlength="64">
                </div>
                <div>
                    <label class="form-label" for="scaffold_name">{{ __('admin.appearance.themes_display_name') }}</label>
                    <input type="text" name="name" id="scaffold_name" class="form-control" placeholder="My Store" maxlength="120">
                </div>
                <label class="form-check">
                    <input type="checkbox" name="activate" value="1" class="form-check-input">
                    <span class="form-check-label">{{ __('admin.appearance.themes_activate_after_create') }}</span>
                </label>
                <div>
                    <button type="submit" class="btn btn-primary">{{ __('admin.appearance.themes_scaffold_btn') }}</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="panel h-100">
            <h2 class="h5">{{ __('admin.appearance.themes_structure_title') }}</h2>
            <pre class="small bg-dark text-white p-3 rounded mb-0" style="white-space:pre-wrap">{{ __('admin.appearance.themes_structure') }}</pre>
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
                        <span class="badge text-bg-primary">{{ __('admin.appearance.themes_active') }}</span>
                    @endif
                </div>
                <p class="small mb-2">{{ $theme['description'] ?? __('admin.appearance.themes_no_description') }}</p>
                <p class="small text-muted mb-3">{{ __('admin.appearance.themes_counts', ['screens' => $screenCount, 'css' => $cssCount, 'js' => $jsCount]) }}</p>
                <div class="d-flex flex-wrap gap-2">
                    @unless ($isActive)
                        <form method="POST" action="{{ route('admin.appearance.themes.activate', $slug) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.appearance.themes_activate') }}</button>
                        </form>
                    @endunless
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.appearance.themes.export', $slug) }}">{{ __('admin.appearance.themes_export') }}</a>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.appearance.themes.pack', $slug) }}">{{ __('admin.appearance.themes_download_pack') }}</a>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="panel">
            <h2 class="h5">{{ __('admin.appearance.themes_import_title') }}</h2>
            <p class="page-intro">{{ __('admin.appearance.themes_import_help') }}</p>
            <form method="POST" action="{{ route('admin.appearance.themes.import') }}" enctype="multipart/form-data" class="d-grid gap-3">
                @csrf
                <div>
                    <label class="form-label" for="package">{{ __('admin.appearance.themes_pack_zip') }}</label>
                    <input type="file" name="package" id="package" class="form-control" accept=".zip,application/zip" required>
                </div>
                <label class="form-check">
                    <input type="checkbox" name="activate" value="1" class="form-check-input">
                    <span class="form-check-label">{{ __('admin.appearance.themes_activate_after_import') }}</span>
                </label>
                <div>
                    <button type="submit" class="btn btn-primary">{{ __('admin.appearance.themes_import_btn') }}</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel">
            <h2 class="h5">{{ __('admin.appearance.themes_packs_title') }}</h2>
            <p class="page-intro">{{ __('admin.appearance.themes_packs_help') }}</p>
            <ul class="list-unstyled mb-0">
                @forelse ($packs as $pack)
                    <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span>{{ $pack['name'] }} <span class="text-muted small">({{ number_format($pack['size'] / 1024, 1) }} KB)</span></span>
                        <a href="{{ route('admin.appearance.themes.pack', $pack['slug']) }}">{{ __('admin.appearance.themes_download_pack') }}</a>
                    </li>
                @empty
                    <li class="text-muted">{{ __('admin.appearance.themes_packs_empty') }}</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection

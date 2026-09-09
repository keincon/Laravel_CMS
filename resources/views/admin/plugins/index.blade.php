@extends('layouts.admin')

@section('title', __('admin.plugins.title'))

@section('content')
@php
    $nativeActive = collect($plugins)->where('is_active', true)->count();
    $nativeTotal = count($plugins);
@endphp

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">{{ __('admin.plugins.title') }}</h1>
        <p class="page-intro mb-0">{!! __('admin.plugins.intro') !!}</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if ($tab === 'native')
            <a class="btn btn-sm btn-primary" href="#add-plugin">{{ __('admin.plugins.add_heading') }}</a>
        @endif
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.modules.index') }}">{{ __('admin.plugins.link_modules') }}</a>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<x-admin.help-next context="plugins" />

<div class="panel mb-3">
    <h2 class="h6 mb-2">{{ __('admin.plugins.vs_title') }}</h2>
    <ul class="mb-0 small">
        <li><strong>{{ __('admin.plugins.vs_plugin_label') }}</strong> — {!! __('admin.plugins.vs_plugin') !!}</li>
        <li><strong>{{ __('admin.plugins.vs_module_label') }}</strong> — {!! __('admin.plugins.vs_module') !!}</li>
        <li>{!! __('admin.plugins.vs_wp') !!}</li>
    </ul>
</div>

<div class="users-role-tabs mb-3">
    <a href="{{ route('admin.plugins.index', ['tab' => 'native']) }}" class="{{ $tab === 'native' ? 'is-active' : '' }}">
        {{ __('admin.plugins.tab_native') }}
        <span>({{ $nativeTotal }})</span>
    </a>
    <a href="{{ route('admin.plugins.index', ['tab' => 'wordpress']) }}" class="{{ $tab === 'wordpress' ? 'is-active' : '' }}">
        {{ __('admin.plugins.tab_wordpress') }}
    </a>
</div>

@if ($tab === 'native')
    <div
        class="mb-4"
        x-data="{
            q: '',
            match(name, slug, description) {
                const needle = this.q.trim().toLowerCase();
                if (!needle) return true;
                return [name, slug, description].join(' ').toLowerCase().includes(needle);
            }
        }"
    >
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div class="d-flex flex-wrap gap-2 small">
                <span class="admin-chip">{{ __('admin.plugins.stat_total', ['count' => $nativeTotal]) }}</span>
                <span class="admin-chip">{{ __('admin.plugins.stat_active', ['count' => $nativeActive]) }}</span>
                <span class="admin-chip"><code>plugins/</code></span>
            </div>
            @if ($nativeTotal > 0)
                <input
                    type="search"
                    class="form-control form-control-sm"
                    style="max-width:16rem"
                    placeholder="{{ __('admin.plugins.filter_placeholder') }}"
                    x-model="q"
                    aria-label="{{ __('admin.plugins.filter_placeholder') }}"
                >
            @endif
        </div>

        <h2 class="h5 mb-3">{{ __('admin.plugins.installed_heading') }}</h2>

        @if ($nativeTotal === 0)
            <div class="panel mb-4">
                <div class="empty-state py-5 text-center">
                    <h3 class="h5 mb-2">{{ __('admin.plugins.empty_title') }}</h3>
                    <p class="text-muted mb-3 mx-auto" style="max-width:36rem">{!! __('admin.plugins.empty_body') !!}</p>
                    <a class="btn btn-primary" href="#add-plugin">{{ __('admin.plugins.empty_cta') }}</a>
                </div>
            </div>
        @else
            <div class="row g-3 mb-2">
                @foreach ($plugins as $slug => $plugin)
                    @php
                        $active = ! empty($plugin['is_active']);
                        $name = $plugin['name'] ?? $slug;
                        $description = (string) ($plugin['description'] ?? '');
                        $isSample = strcasecmp((string) $slug, 'hello-laravelpress') === 0;
                    @endphp
                    <div
                        class="col-md-6 col-xl-4"
                        x-show="match(@js($name), @js($slug), @js($description))"
                        x-cloak
                    >
                        <div
                            class="panel h-100 d-flex flex-column"
                            style="border:1px solid {{ $active ? 'var(--bs-success, #198754)' : 'color-mix(in srgb, currentColor 12%, transparent)' }};"
                        >
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <h3 class="h6 mb-1">{{ $name }}</h3>
                                    <div class="small text-muted"><code>{{ $slug }}</code> · v{{ $plugin['version'] ?? '1.0.0' }}</div>
                                </div>
                                @if ($active)
                                    <span class="badge text-bg-success">{{ __('admin.plugins.active') }}</span>
                                @else
                                    <span class="badge text-bg-secondary">{{ __('admin.plugins.inactive') }}</span>
                                @endif
                            </div>

                            @if ($description !== '')
                                <p class="small mb-2 flex-grow-1">{{ \Illuminate\Support\Str::limit($description, 140) }}</p>
                            @else
                                <p class="small text-muted mb-2 flex-grow-1">{{ __('admin.plugins.no_description') }}</p>
                            @endif

                            @if ($isSample)
                                <p class="small text-muted mb-3">{{ __('admin.plugins.sample_note') }}</p>
                            @endif

                            <div class="d-flex flex-wrap gap-2 mt-auto pt-1">
                                @if ($active)
                                    <form
                                        method="POST"
                                        action="{{ route('admin.plugins.deactivate', $slug) }}"
                                        onsubmit="return confirm(@js(__('admin.plugins.confirm_deactivate', ['name' => $name])));"
                                    >
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger" type="submit">{{ __('admin.plugins.deactivate') }}</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.plugins.activate', $slug) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-primary" type="submit">{{ __('admin.plugins.activate') }}</button>
                                    </form>
                                @endif
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.plugins.export', $slug) }}">{{ __('admin.plugins.export_zip') }}</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="small text-muted mb-4" x-show="q.trim() !== ''" x-cloak>{{ __('admin.plugins.filter_hint') }}</p>
        @endif
    </div>

    <div id="add-plugin" class="pt-1">
        <h2 class="h5 mb-1">{{ __('admin.plugins.add_heading') }}</h2>
        <p class="page-intro small mb-3">{!! __('admin.plugins.add_intro') !!}</p>

        <div class="row g-3 mb-2">
            <div class="col-lg-5">
                <div class="panel h-100">
                    <h3 class="h6 mb-1">{{ __('admin.plugins.scaffold') }}</h3>
                    <p class="small text-muted mb-3">{!! __('admin.plugins.scaffold_help') !!}</p>
                    <form method="POST" action="{{ route('admin.plugins.scaffold') }}" class="d-grid gap-3">
                        @csrf
                        <div>
                            <label class="form-label" for="slug">{{ __('admin.plugins.slug') }}</label>
                            <input type="text" name="slug" id="slug" class="form-control" required pattern="[a-z0-9\-]+" maxlength="64" placeholder="my-plugin" autocomplete="off" value="{{ old('slug') }}">
                            <div class="form-text">{{ __('admin.plugins.slug_hint') }}</div>
                            @error('slug')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label" for="name">{{ __('admin.plugins.display_name') }}</label>
                            <input type="text" name="name" id="name" class="form-control" maxlength="120" value="{{ old('name') }}" placeholder="{{ __('admin.plugins.display_name_placeholder') }}">
                        </div>
                        <label class="form-check">
                            <input type="checkbox" name="activate" value="1" class="form-check-input" @checked(old('activate'))>
                            <span class="form-check-label">{{ __('admin.plugins.activate_after_create') }}</span>
                        </label>
                        <button type="submit" class="btn btn-primary">{{ __('admin.plugins.scaffold_btn') }}</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="panel h-100">
                    <h3 class="h6 mb-1">{{ __('admin.plugins.import_zip') }}</h3>
                    <p class="small text-muted mb-3">{!! __('admin.plugins.import_help') !!}</p>
                    <form method="POST" action="{{ route('admin.plugins.import') }}" enctype="multipart/form-data" class="d-grid gap-3">
                        @csrf
                        <div>
                            <label class="form-label" for="package">{{ __('admin.plugins.plugin_zip') }}</label>
                            <input id="package" type="file" name="package" class="form-control" accept=".zip,application/zip" required>
                            @error('package')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <label class="form-check">
                            <input type="checkbox" name="activate" value="1" class="form-check-input">
                            <span class="form-check-label">{{ __('admin.plugins.activate_after_import') }}</span>
                        </label>
                        <button type="submit" class="btn btn-primary">{{ __('admin.plugins.import') }}</button>
                    </form>
                    <hr class="my-3">
                    <form method="POST" action="{{ route('admin.plugins.rebuild') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">{{ __('admin.plugins.rebuild_packs') }}</button>
                        <div class="form-text mt-2">{{ __('admin.plugins.rebuild_help') }}</div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="panel mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h2 class="h5 mb-2">{{ __('admin.plugins.wp_connection') }}</h2>
                <p class="small text-muted mb-2">{!! __('admin.plugins.wp_info') !!}</p>
                <p class="mb-0">
                    {{ __('admin.plugins.status_label') }}
                    @if ($wordpress['reachable'])
                        <span class="badge text-bg-success">{{ __('admin.plugins.connected') }}</span>
                        @if ($wordpress['wordpress_version'])
                            <span class="small text-muted">WP {{ $wordpress['wordpress_version'] }}</span>
                        @endif
                    @elseif ($wordpress['enabled'])
                        <span class="badge text-bg-danger">{{ __('admin.plugins.unreachable') }}</span>
                        <span class="small text-muted">— {{ $wordpress['message'] }}</span>
                    @else
                        <span class="badge text-bg-secondary">{{ __('admin.plugins.disabled') }}</span>
                        <span class="small text-muted">— {{ $wordpress['message'] }}</span>
                    @endif
                </p>
            </div>
            @if ($wordpress['base_url'])
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-sm btn-outline-secondary" href="{{ $wordpress['base_url'] }}" target="_blank" rel="noopener">{{ __('admin.plugins.open_wp_site') }}</a>
                    <a class="btn btn-sm btn-outline-primary" href="{{ $wordpress['admin_url'] }}" target="_blank" rel="noopener">{{ __('admin.plugins.open_wp_admin') }}</a>
                </div>
            @endif
        </div>
    </div>

    @if ($wordpress['reachable'])
        <div class="panel mb-4">
            <h2 class="h5 mb-1">{{ __('admin.plugins.upload_wp') }}</h2>
            <p class="small text-muted mb-3">{{ __('admin.plugins.upload_wp_help') }}</p>
            <form method="POST" action="{{ route('admin.plugins.wp.upload') }}" enctype="multipart/form-data" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-6">
                    <label class="form-label" for="wp_package">{{ __('admin.plugins.plugin_zip') }}</label>
                    <input type="file" name="package" id="wp_package" class="form-control" accept=".zip,application/zip" required>
                </div>
                <div class="col-md-3">
                    <label class="form-check mb-0">
                        <input type="checkbox" name="activate" value="1" class="form-check-input">
                        <span class="form-check-label">{{ __('admin.plugins.activate_after_install') }}</span>
                    </label>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">{{ __('admin.plugins.upload_to_wp') }}</button>
                </div>
            </form>
        </div>

        <h2 class="h5 mb-3">{{ __('admin.plugins.wp_installed_heading') }}</h2>

        @if (count($wpPlugins) === 0)
            <div class="panel">
                <div class="empty-state py-4 text-center">
                    <h3 class="h5 mb-2">{{ __('admin.plugins.wp_empty_title') }}</h3>
                    <p class="text-muted mb-0">{{ __('admin.plugins.wp_empty_body') }}</p>
                </div>
            </div>
        @else
            <div class="row g-3">
                @foreach ($wpPlugins as $wp)
                    @php $active = ! empty($wp['active']); @endphp
                    <div class="col-md-6 col-xl-4">
                        <div class="panel h-100 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <h3 class="h6 mb-1">{{ $wp['name'] ?? $wp['file'] }}</h3>
                                    <div class="small text-muted">{{ $wp['file'] ?? '' }} · {{ $wp['version'] ?? '—' }}</div>
                                </div>
                                @if ($active)
                                    <span class="badge text-bg-success">{{ __('admin.plugins.active') }}</span>
                                @else
                                    <span class="badge text-bg-secondary">{{ __('admin.plugins.inactive') }}</span>
                                @endif
                            </div>
                            <p class="small text-muted flex-grow-1">{{ \Illuminate\Support\Str::limit($wp['description'] ?? '', 120) }}</p>
                            <div class="mt-auto">
                                @if ($active)
                                    <form method="POST" action="{{ route('admin.plugins.wp.deactivate') }}">
                                        @csrf
                                        <input type="hidden" name="plugin" value="{{ $wp['file'] }}">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">{{ __('admin.plugins.deactivate') }}</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.plugins.wp.activate') }}">
                                        @csrf
                                        <input type="hidden" name="plugin" value="{{ $wp['file'] }}">
                                        <button class="btn btn-sm btn-primary" type="submit">{{ __('admin.plugins.activate') }}</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @else
        <div class="panel">
            <div class="empty-state py-4 text-center">
                <h3 class="h5 mb-2">{{ __('admin.plugins.wp_offline_title') }}</h3>
                <p class="text-muted mb-0 mx-auto" style="max-width:36rem">{!! __('admin.plugins.wp_offline_body') !!}</p>
            </div>
        </div>
    @endif
@endif
@endsection

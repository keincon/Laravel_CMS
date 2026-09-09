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
    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.modules.index') }}">{{ __('admin.plugins.link_modules') }}</a>
</div>

<div class="alert alert-info small mb-3" role="status">
    {!! __('admin.plugins.help_html', ['modules' => route('admin.modules.index')]) !!}
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

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
    <div class="d-flex flex-wrap gap-2 mb-3 small text-muted">
        <span class="admin-chip">{{ __('admin.plugins.stat_total', ['count' => $nativeTotal]) }}</span>
        <span class="admin-chip">{{ __('admin.plugins.stat_active', ['count' => $nativeActive]) }}</span>
        <span class="admin-chip"><code>plugins/</code></span>
    </div>

    <h2 class="h5 mb-2">{{ __('admin.plugins.installed_heading') }}</h2>

    @if ($nativeTotal === 0)
        <div class="panel mb-4">
            <div class="empty-state py-5 text-center">
                <h3 class="h5 mb-2">{{ __('admin.plugins.empty_title') }}</h3>
                <p class="text-muted mb-0 mx-auto" style="max-width:36rem">{!! __('admin.plugins.empty_body') !!}</p>
            </div>
        </div>
    @else
        <div class="admin-table-wrap panel p-0 overflow-hidden mb-4">
            <table class="admin-table mb-0">
                <thead>
                    <tr>
                        <th>{{ __('admin.plugins.plugin') }}</th>
                        <th>{{ __('admin.plugins.version') }}</th>
                        <th>{{ __('admin.plugins.status') }}</th>
                        <th>{{ __('admin.plugins.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($plugins as $slug => $plugin)
                        @php
                            $active = ! empty($plugin['is_active']);
                            $name = $plugin['name'] ?? $slug;
                            $isSample = strcasecmp((string) $slug, 'hello-laravelpress') === 0;
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $name }}</strong>
                                <div class="small text-muted"><code>{{ $slug }}</code></div>
                                @if (! empty($plugin['description']))
                                    <div class="small mt-1">{{ $plugin['description'] }}</div>
                                @endif
                                @if ($isSample)
                                    <div class="small text-muted mt-1">{{ __('admin.plugins.sample_note') }}</div>
                                @endif
                            </td>
                            <td class="text-nowrap">{{ $plugin['version'] ?? '1.0.0' }}</td>
                            <td>
                                @if ($active)
                                    <span class="badge text-bg-success">{{ __('admin.plugins.active') }}</span>
                                @else
                                    <span class="badge text-bg-secondary">{{ __('admin.plugins.inactive') }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
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
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

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
                        <input type="text" name="slug" id="slug" class="form-control" required pattern="[a-z0-9\-]+" maxlength="64" placeholder="my-plugin" autocomplete="off">
                        <div class="form-text">{{ __('admin.plugins.slug_hint') }}</div>
                    </div>
                    <div>
                        <label class="form-label" for="name">{{ __('admin.plugins.display_name') }}</label>
                        <input type="text" name="name" id="name" class="form-control" maxlength="120">
                    </div>
                    <label class="form-check">
                        <input type="checkbox" name="activate" value="1" class="form-check-input">
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
@else
    <div class="alert alert-info small mb-3" role="status">
        {!! __('admin.plugins.wp_info') !!}
    </div>

    <div class="panel mb-4">
        <h2 class="h5 mb-2">{{ __('admin.plugins.wp_connection') }}</h2>
        <p class="mb-1">
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
        @if ($wordpress['base_url'])
            <p class="mb-0 small">
                {{ __('admin.plugins.site') }}
                <a href="{{ $wordpress['base_url'] }}" target="_blank" rel="noopener">{{ $wordpress['base_url'] }}</a>
                ·
                <a href="{{ $wordpress['admin_url'] }}" target="_blank" rel="noopener">{{ __('admin.plugins.open_wp_admin') }}</a>
            </p>
        @endif
    </div>

    @if ($wordpress['reachable'])
        <div class="panel mb-4">
            <h2 class="h5 mb-3">{{ __('admin.plugins.upload_wp') }}</h2>
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

        @if (count($wpPlugins) === 0)
            <div class="panel">
                <div class="empty-state py-4 text-center">
                    <h3 class="h5 mb-2">{{ __('admin.plugins.wp_empty_title') }}</h3>
                    <p class="text-muted mb-0">{{ __('admin.plugins.wp_empty_body') }}</p>
                </div>
            </div>
        @else
            <div class="admin-table-wrap panel p-0 overflow-hidden">
                <table class="admin-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('admin.plugins.plugin') }}</th>
                            <th>{{ __('admin.plugins.version') }}</th>
                            <th>{{ __('admin.plugins.status') }}</th>
                            <th>{{ __('admin.plugins.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($wpPlugins as $wp)
                            @php $active = ! empty($wp['active']); @endphp
                            <tr>
                                <td>
                                    <strong>{{ $wp['name'] ?? $wp['file'] }}</strong>
                                    <div class="small text-muted">{{ $wp['file'] ?? '' }}</div>
                                    <div class="small">{{ \Illuminate\Support\Str::limit($wp['description'] ?? '', 120) }}</div>
                                </td>
                                <td class="text-nowrap">{{ $wp['version'] ?? '—' }}</td>
                                <td>
                                    @if ($active)
                                        <span class="badge text-bg-success">{{ __('admin.plugins.active') }}</span>
                                    @else
                                        <span class="badge text-bg-secondary">{{ __('admin.plugins.inactive') }}</span>
                                    @endif
                                </td>
                                <td>
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
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
@endif
@endsection

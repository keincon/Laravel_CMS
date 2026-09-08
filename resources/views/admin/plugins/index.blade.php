@extends('layouts.admin')

@section('title', __('admin.plugins.title'))

@section('content')
<div class="admin-page-header d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
    <div>
        <h1>{{ __('admin.plugins.title') }}</h1>
        <p class="muted mb-0">{!! __('admin.plugins.intro', ['modules' => route('admin.modules.index')]) !!}</p>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'native' ? 'active' : '' }}" href="{{ route('admin.plugins.index', ['tab' => 'native']) }}">{{ __('admin.plugins.tab_native') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'wordpress' ? 'active' : '' }}" href="{{ route('admin.plugins.index', ['tab' => 'wordpress']) }}">{{ __('admin.plugins.tab_wordpress') }}</a>
    </li>
</ul>

@if ($tab === 'native')
    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="panel h-100">
                <h2 class="h5">{{ __('admin.plugins.scaffold') }}</h2>
                <form method="POST" action="{{ route('admin.plugins.scaffold') }}" class="d-grid gap-3">
                    @csrf
                    <div>
                        <label class="form-label" for="slug">{{ __('admin.plugins.slug') }}</label>
                        <input type="text" name="slug" id="slug" class="form-control" required pattern="[a-z0-9\-]+" maxlength="64" placeholder="my-plugin">
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
                <h2 class="h5">{{ __('admin.plugins.import_zip') }}</h2>
                <p class="page-intro">{!! __('admin.plugins.import_help') !!}</p>
                <form method="POST" action="{{ route('admin.plugins.import') }}" enctype="multipart/form-data" class="d-grid gap-3">
                    @csrf
                    <input type="file" name="package" class="form-control" accept=".zip,application/zip" required>
                    <label class="form-check">
                        <input type="checkbox" name="activate" value="1" class="form-check-input">
                        <span class="form-check-label">{{ __('admin.plugins.activate_after_import') }}</span>
                    </label>
                    <button type="submit" class="btn btn-primary">{{ __('admin.plugins.import') }}</button>
                </form>
                <form method="POST" action="{{ route('admin.plugins.rebuild') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">{{ __('admin.plugins.rebuild_packs') }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>{{ __('admin.plugins.plugin') }}</th>
                    <th>{{ __('admin.plugins.version') }}</th>
                    <th>{{ __('admin.plugins.status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($plugins as $slug => $plugin)
                    <tr>
                        <td>
                            <strong>{{ $plugin['name'] ?? $slug }}</strong>
                            <div class="small text-muted">{{ $slug }}</div>
                            <div class="small">{{ $plugin['description'] ?? '' }}</div>
                        </td>
                        <td>{{ $plugin['version'] ?? '1.0.0' }}</td>
                        <td>{{ !empty($plugin['is_active']) ? __('admin.plugins.active') : __('admin.plugins.inactive') }}</td>
                        <td class="d-flex flex-wrap gap-2">
                            @if (!empty($plugin['is_active']))
                                <form method="POST" action="{{ route('admin.plugins.deactivate', $slug) }}">@csrf
                                    <button class="btn btn-sm btn-outline-danger" type="submit">{{ __('admin.plugins.deactivate') }}</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.plugins.activate', $slug) }}">@csrf
                                    <button class="btn btn-sm btn-primary" type="submit">{{ __('admin.plugins.activate') }}</button>
                                </form>
                            @endif
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.plugins.export', $slug) }}">{{ __('admin.plugins.export_zip') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">{!! __('admin.plugins.none_native') !!}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@else
    <div class="alert alert-info">
        {!! __('admin.plugins.wp_info') !!}
    </div>

    <div class="panel mb-4">
        <h2 class="h5">{{ __('admin.plugins.wp_connection') }}</h2>
        <p class="mb-1">
            {{ __('admin.plugins.status_label') }}
            @if ($wordpress['reachable'])
                <span class="text-success">{{ __('admin.plugins.connected') }}</span>
                @if ($wordpress['wordpress_version'])
                    (WP {{ $wordpress['wordpress_version'] }})
                @endif
            @elseif ($wordpress['enabled'])
                <span class="text-danger">{{ __('admin.plugins.unreachable') }}</span> — {{ $wordpress['message'] }}
            @else
                <span class="text-muted">{{ __('admin.plugins.disabled') }}</span> — {{ $wordpress['message'] }}
            @endif
        </p>
        @if ($wordpress['base_url'])
            <p class="mb-0 small">
                {{ __('admin.plugins.site') }} <a href="{{ $wordpress['base_url'] }}" target="_blank" rel="noopener">{{ $wordpress['base_url'] }}</a>
                ·
                <a href="{{ $wordpress['admin_url'] }}" target="_blank" rel="noopener">{{ __('admin.plugins.open_wp_admin') }}</a>
            </p>
        @endif
    </div>

    @if ($wordpress['reachable'])
        <div class="panel mb-4">
            <h2 class="h5">{{ __('admin.plugins.upload_wp') }}</h2>
            <form method="POST" action="{{ route('admin.plugins.wp.upload') }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-3 align-items-end">
                @csrf
                <div>
                    <label class="form-label" for="wp_package">{{ __('admin.plugins.plugin_zip') }}</label>
                    <input type="file" name="package" id="wp_package" class="form-control" accept=".zip,application/zip" required>
                </div>
                <label class="form-check mb-2">
                    <input type="checkbox" name="activate" value="1" class="form-check-input">
                    <span class="form-check-label">{{ __('admin.plugins.activate_after_install') }}</span>
                </label>
                <button type="submit" class="btn btn-primary">{{ __('admin.plugins.upload_to_wp') }}</button>
            </form>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>{{ __('admin.plugins.plugin') }}</th>
                        <th>{{ __('admin.plugins.version') }}</th>
                        <th>{{ __('admin.plugins.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($wpPlugins as $wp)
                        <tr>
                            <td>
                                <strong>{{ $wp['name'] ?? $wp['file'] }}</strong>
                                <div class="small text-muted">{{ $wp['file'] ?? '' }}</div>
                                <div class="small">{{ \Illuminate\Support\Str::limit($wp['description'] ?? '', 120) }}</div>
                            </td>
                            <td>{{ $wp['version'] ?? '' }}</td>
                            <td>{{ !empty($wp['active']) ? __('admin.plugins.active') : __('admin.plugins.inactive') }}</td>
                            <td>
                                @if (!empty($wp['active']))
                                    <form method="POST" action="{{ route('admin.plugins.wp.deactivate') }}">@csrf
                                        <input type="hidden" name="plugin" value="{{ $wp['file'] }}">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">{{ __('admin.plugins.deactivate') }}</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.plugins.wp.activate') }}">@csrf
                                        <input type="hidden" name="plugin" value="{{ $wp['file'] }}">
                                        <button class="btn btn-sm btn-primary" type="submit">{{ __('admin.plugins.activate') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">{{ __('admin.plugins.none_wp') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
@endif
@endsection

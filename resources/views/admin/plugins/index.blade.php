@extends('layouts.admin')

@section('title', 'Plugins')

@section('content')
<div class="admin-page-header d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
    <div>
        <h1>Plugins</h1>
        <p class="muted mb-0">
            Installable LaravelPress plugins live under <code>plugins/</code>.
            WordPress plugins run inside the embedded WordPress site (not in Laravel PHP).
            Developer modules remain under <a href="{{ route('admin.modules.index') }}">Settings → Modules</a>.
        </p>
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
        <a class="nav-link {{ $tab === 'native' ? 'active' : '' }}" href="{{ route('admin.plugins.index', ['tab' => 'native']) }}">LaravelPress</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'wordpress' ? 'active' : '' }}" href="{{ route('admin.plugins.index', ['tab' => 'wordpress']) }}">WordPress</a>
    </li>
</ul>

@if ($tab === 'native')
    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="panel h-100">
                <h2 class="h5">Scaffold plugin</h2>
                <form method="POST" action="{{ route('admin.plugins.scaffold') }}" class="d-grid gap-3">
                    @csrf
                    <div>
                        <label class="form-label" for="slug">Slug</label>
                        <input type="text" name="slug" id="slug" class="form-control" required pattern="[a-z0-9\-]+" maxlength="64" placeholder="my-plugin">
                    </div>
                    <div>
                        <label class="form-label" for="name">Display name</label>
                        <input type="text" name="name" id="name" class="form-control" maxlength="120">
                    </div>
                    <label class="form-check">
                        <input type="checkbox" name="activate" value="1" class="form-check-input">
                        <span class="form-check-label">Activate after create</span>
                    </label>
                    <button type="submit" class="btn btn-primary">Scaffold</button>
                </form>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="panel h-100">
                <h2 class="h5">Import plugin ZIP</h2>
                <p class="page-intro">ZIP must include <code>plugin.json</code>. PHP is allowed; shells/phar are rejected. Code runs only after you activate.</p>
                <form method="POST" action="{{ route('admin.plugins.import') }}" enctype="multipart/form-data" class="d-grid gap-3">
                    @csrf
                    <input type="file" name="package" class="form-control" accept=".zip,application/zip" required>
                    <label class="form-check">
                        <input type="checkbox" name="activate" value="1" class="form-check-input">
                        <span class="form-check-label">Activate after import</span>
                    </label>
                    <button type="submit" class="btn btn-primary">Import</button>
                </form>
                <form method="POST" action="{{ route('admin.plugins.rebuild') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Rebuild download packs</button>
                </form>
            </div>
        </div>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Plugin</th>
                    <th>Version</th>
                    <th>Status</th>
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
                        <td>{{ !empty($plugin['is_active']) ? 'Active' : 'Inactive' }}</td>
                        <td class="d-flex flex-wrap gap-2">
                            @if (!empty($plugin['is_active']))
                                <form method="POST" action="{{ route('admin.plugins.deactivate', $slug) }}">@csrf
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Deactivate</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.plugins.activate', $slug) }}">@csrf
                                    <button class="btn btn-sm btn-primary" type="submit">Activate</button>
                                </form>
                            @endif
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.plugins.export', $slug) }}">Export ZIP</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">No plugins discovered under <code>plugins/</code>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@else
    <div class="alert alert-info">
        WordPress plugins execute inside the embedded WordPress runtime, not inside Laravel.
        Configure <code>WP_EMBED_ENABLED</code>, <code>WP_EMBED_URL</code>, and <code>WP_BRIDGE_TOKEN</code>.
    </div>

    <div class="panel mb-4">
        <h2 class="h5">WordPress connection</h2>
        <p class="mb-1">
            Status:
            @if ($wordpress['reachable'])
                <span class="text-success">Connected</span>
                @if ($wordpress['wordpress_version'])
                    (WP {{ $wordpress['wordpress_version'] }})
                @endif
            @elseif ($wordpress['enabled'])
                <span class="text-danger">Unreachable</span> — {{ $wordpress['message'] }}
            @else
                <span class="text-muted">Disabled</span> — {{ $wordpress['message'] }}
            @endif
        </p>
        @if ($wordpress['base_url'])
            <p class="mb-0 small">
                Site: <a href="{{ $wordpress['base_url'] }}" target="_blank" rel="noopener">{{ $wordpress['base_url'] }}</a>
                ·
                <a href="{{ $wordpress['admin_url'] }}" target="_blank" rel="noopener">Open WP Admin</a>
            </p>
        @endif
    </div>

    @if ($wordpress['reachable'])
        <div class="panel mb-4">
            <h2 class="h5">Upload WordPress plugin ZIP</h2>
            <form method="POST" action="{{ route('admin.plugins.wp.upload') }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-3 align-items-end">
                @csrf
                <div>
                    <label class="form-label" for="wp_package">Plugin ZIP</label>
                    <input type="file" name="package" id="wp_package" class="form-control" accept=".zip,application/zip" required>
                </div>
                <label class="form-check mb-2">
                    <input type="checkbox" name="activate" value="1" class="form-check-input">
                    <span class="form-check-label">Activate after install</span>
                </label>
                <button type="submit" class="btn btn-primary">Upload to WordPress</button>
            </form>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Plugin</th>
                        <th>Version</th>
                        <th>Status</th>
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
                            <td>{{ !empty($wp['active']) ? 'Active' : 'Inactive' }}</td>
                            <td>
                                @if (!empty($wp['active']))
                                    <form method="POST" action="{{ route('admin.plugins.wp.deactivate') }}">@csrf
                                        <input type="hidden" name="plugin" value="{{ $wp['file'] }}">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Deactivate</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.plugins.wp.activate') }}">@csrf
                                        <input type="hidden" name="plugin" value="{{ $wp['file'] }}">
                                        <button class="btn btn-sm btn-primary" type="submit">Activate</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No WordPress plugins found (or bridge returned an empty list).</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
@endif
@endsection

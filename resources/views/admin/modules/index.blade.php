@extends('layouts.admin')

@section('title', __('admin.modules.title'))

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">{{ __('admin.modules.title') }}</h1>
        <p class="page-intro mb-0">{!! __('admin.modules.intro') !!}</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.plugins.index') }}">{{ __('admin.modules.link_plugins') }}</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.settings.seo') }}">{{ __('admin.modules.link_seo') }}</a>
    </div>
</div>

<div class="alert alert-info small mb-3" role="status">
    {!! __('admin.modules.help_html', [
        'plugins' => route('admin.plugins.index'),
        'seo' => route('admin.settings.seo'),
    ]) !!}
</div>

@php
    $enabledCount = collect($modules)->where('enabled', true)->count();
    $totalCount = count($modules);
@endphp

<div class="d-flex flex-wrap gap-2 mb-3 small text-muted">
    <span class="admin-chip">{{ __('admin.modules.stat_total', ['count' => $totalCount]) }}</span>
    <span class="admin-chip">{{ __('admin.modules.stat_enabled', ['count' => $enabledCount]) }}</span>
    <span class="admin-chip"><code>modules/</code></span>
</div>

@if ($totalCount === 0)
    <div class="panel">
        <div class="empty-state py-5 text-center">
            <h2 class="h5 mb-2">{{ __('admin.modules.empty_title') }}</h2>
            <p class="text-muted mb-3 mx-auto" style="max-width:36rem">{{ __('admin.modules.empty_body') }}</p>
            <pre class="d-inline-block text-start small p-3 border rounded bg-light mb-0"><code>modules/MyModule/
  module.json
  MyModuleServiceProvider.php</code></pre>
        </div>
    </div>
@else
    <div class="admin-table-wrap panel p-0 overflow-hidden">
        <table class="admin-table mb-0">
            <thead>
                <tr>
                    <th>{{ __('admin.modules.col_name') }}</th>
                    <th>{{ __('admin.modules.col_version') }}</th>
                    <th>{{ __('admin.modules.col_description') }}</th>
                    <th>{{ __('admin.modules.col_status') }}</th>
                    <th>{{ __('admin.modules.col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($modules as $name => $module)
                    @php
                        $enabled = ! empty($module['enabled']);
                        $provider = is_string($module['provider'] ?? null) ? $module['provider'] : null;
                        $providerOk = $provider && class_exists($provider);
                        $isSeoSample = strcasecmp((string) $name, 'SEO') === 0;
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $name }}</strong>
                            @if ($provider)
                                <div class="small text-muted text-break">{{ $provider }}</div>
                            @endif
                            @if ($provider && ! $providerOk)
                                <div class="small text-danger">{{ __('admin.modules.provider_missing') }}</div>
                            @endif
                        </td>
                        <td class="text-nowrap">{{ $module['version'] ?? '—' }}</td>
                        <td>
                            <div>{{ $module['description'] ?? '—' }}</div>
                            @if ($isSeoSample)
                                <div class="small text-muted mt-1">{!! __('admin.modules.seo_note_html', ['seo' => route('admin.settings.seo')]) !!}</div>
                            @endif
                        </td>
                        <td>
                            @if ($enabled)
                                <span class="badge text-bg-success">{{ __('admin.modules.enabled') }}</span>
                            @else
                                <span class="badge text-bg-secondary">{{ __('admin.modules.disabled') }}</span>
                            @endif
                        </td>
                        <td>
                            <form
                                method="POST"
                                action="{{ route('admin.modules.update', $name) }}"
                                @if ($enabled)
                                    onsubmit="return confirm(@js(__('admin.modules.confirm_disable', ['name' => $name])));"
                                @endif
                            >
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="enabled" value="{{ $enabled ? 0 : 1 }}">
                                <button
                                    class="btn btn-sm {{ $enabled ? 'btn-outline-danger' : 'btn-primary' }}"
                                    type="submit"
                                >
                                    {{ $enabled ? __('admin.modules.disable') : __('admin.modules.enable') }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection

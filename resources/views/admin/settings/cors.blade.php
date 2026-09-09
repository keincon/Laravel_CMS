@extends('layouts.admin')

@section('title', __('admin.nav.cors'))

@section('content')
<div class="admin-page-header">
    <h1>{{ __('admin.nav.cors') }}</h1>
    <p class="muted mb-0">{!! __('admin.settings.cors_intro', ['api' => route('admin.settings.api')]) !!}</p>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.settings.cors.update') }}" class="settings-form panel" x-data="{ allMethods: {{ in_array('*', $settings['allowed_methods'], true) ? 'true' : 'false' }} }">
    @csrf
    @method('PUT')

    <label class="capability-item mb-4">
        <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $settings['enabled']))>
        <span>
            <strong>{{ __('admin.settings.cors_enabled') }}</strong>
            <small>{{ __('admin.settings.cors_enabled_hint') }}</small>
        </span>
    </label>

    <div class="mb-3">
        <label class="form-label" for="paths">{{ __('admin.settings.cors_paths') }}</label>
        <textarea name="paths" id="paths" class="form-control font-monospace" rows="3" placeholder="api/*">{{ old('paths', $pathsText) }}</textarea>
        <div class="form-text">{!! __('admin.settings.cors_paths_hint') !!}</div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="allowed_origins">{{ __('admin.settings.cors_origins') }}</label>
        <textarea name="allowed_origins" id="allowed_origins" class="form-control font-monospace" rows="4" placeholder="*">{{ old('allowed_origins', $originsText) }}</textarea>
        <div class="form-text">{!! __('admin.settings.cors_origins_hint') !!}</div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="allowed_origins_patterns">{{ __('admin.settings.cors_patterns') }}</label>
        <textarea name="allowed_origins_patterns" id="allowed_origins_patterns" class="form-control font-monospace" rows="2" placeholder="#^https://.*\.example\.com$#">{{ old('allowed_origins_patterns', $patternsText) }}</textarea>
        <div class="form-text">{{ __('admin.settings.cors_patterns_hint') }}</div>
    </div>

    <div class="mb-3">
        <label class="form-label">{{ __('admin.settings.cors_methods') }}</label>
        <label class="capability-item mb-2">
            <input type="checkbox" name="allow_all_methods" value="1" x-model="allMethods">
            <span><strong>{{ __('admin.settings.cors_all_methods') }}</strong></span>
        </label>
        <div class="d-flex flex-wrap gap-3" x-show="!allMethods">
            @foreach ($methodChoices as $method)
                <label class="form-check">
                    <input
                        type="checkbox"
                        class="form-check-input"
                        name="allowed_methods[]"
                        value="{{ $method }}"
                        @checked(in_array($method, old('allowed_methods', in_array('*', $settings['allowed_methods'], true) ? [] : $settings['allowed_methods']), true))
                    >
                    <span class="form-check-label">{{ $method }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="allowed_headers">{{ __('admin.settings.cors_headers') }}</label>
        <textarea name="allowed_headers" id="allowed_headers" class="form-control font-monospace" rows="2">{{ old('allowed_headers', $headersText) }}</textarea>
        <div class="form-text">{!! __('admin.settings.cors_headers_hint') !!}</div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="exposed_headers">{{ __('admin.settings.cors_exposed') }}</label>
        <textarea name="exposed_headers" id="exposed_headers" class="form-control font-monospace" rows="2">{{ old('exposed_headers', $exposedText) }}</textarea>
        <div class="form-text">{{ __('admin.settings.cors_exposed_hint') }}</div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="max_age">{{ __('admin.settings.cors_max_age') }}</label>
        <input type="number" name="max_age" id="max_age" class="form-control" min="0" max="86400" value="{{ old('max_age', $settings['max_age']) }}">
        <div class="form-text">{{ __('admin.settings.cors_max_age_hint') }}</div>
    </div>

    <label class="capability-item mb-4">
        <input type="checkbox" name="supports_credentials" value="1" @checked(old('supports_credentials', $settings['supports_credentials']))>
        <span>
            <strong>{{ __('admin.settings.cors_credentials') }}</strong>
            <small>{!! __('admin.settings.cors_credentials_hint') !!}</small>
        </span>
    </label>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">{{ __('admin.settings.cors_save') }}</button>
    </div>
</form>
@endsection

@extends('layouts.admin')
@section('title', __('admin.settings.general'))
@section('content')
<p class="page-intro mb-4">{{ __('admin.settings.general_intro') }}</p>

<form method="POST" action="{{ route('admin.settings.general.update') }}" class="settings-form" x-data="{ framework: @js(old('ui_framework', $current)) }">
    @csrf @method('PUT')

    <section class="panel mb-3">
        <header class="panel-head">
            <h2 class="h6 mb-0">{{ __('admin.settings.site_identity') }}</h2>
            <p class="panel-desc mb-0">{{ __('admin.settings.site_identity_desc') }}</p>
        </header>
        <div class="panel-body">
            <div class="mb-3">
                <label class="form-label" for="site_name">{{ __('admin.settings.site_name') }}</label>
                <input id="site_name" name="site_name" class="form-control" value="{{ old('site_name', $siteName) }}" required maxlength="255">
            </div>
            <div class="mb-3">
                <label class="form-label" for="site_description">{{ __('admin.settings.site_description') }}</label>
                <textarea id="site_description" name="site_description" class="form-control" rows="3" maxlength="500">{{ old('site_description', $siteDescription) }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('admin.settings.admin_logo') }}</label>
                <p class="form-text mb-2">{{ __('admin.settings.admin_logo_help') }}</p>
                <x-admin.media-picker
                    name="site_logo_media_id"
                    :value="old('site_logo_media_id', $logoMediaId ?? null)"
                    :label="__('admin.settings.choose_logo')"
                />
            </div>
            <div>
                <label class="form-label">{{ __('admin.settings.favicon') }}</label>
                <p class="form-text mb-2">{{ __('admin.settings.favicon_help') }}</p>
                <x-admin.media-picker
                    name="site_favicon_media_id"
                    :value="old('site_favicon_media_id', $faviconMediaId)"
                    :label="__('admin.settings.choose_favicon')"
                />
            </div>
        </div>
    </section>

    <section class="panel mb-3">
        <header class="panel-head">
            <h2 class="h6 mb-0">{{ __('admin.settings.ui_framework') }}</h2>
            <p class="panel-desc mb-0">{{ __('admin.settings.ui_framework_desc') }}</p>
        </header>
        <div class="panel-body">
            <div class="framework-grid">
                @foreach ($frameworks as $key => $meta)
                    <label class="framework-card" :class="{ 'is-selected': framework === '{{ $key }}' }">
                        <input type="radio" name="ui_framework" value="{{ $key }}" x-model="framework" class="framework-radio" @checked(old('ui_framework', $current) === $key) required>
                        <div class="framework-card-inner">
                            <div class="framework-card-top">
                                <span class="framework-radio-ui" aria-hidden="true"></span>
                                <div>
                                    <div class="framework-title">{{ $meta['label'] ?? ucfirst($key) }}</div>
                                    <div class="framework-desc">{{ $meta['description'] ?? '' }}</div>
                                </div>
                            </div>
                            <div class="framework-preview" data-framework="{{ $key }}">
                                <span class="fp-btn fp-primary">{{ __('admin.settings.preview_save') }}</span>
                                <span class="fp-btn fp-ghost">{{ __('admin.settings.preview_cancel') }}</span>
                                <span class="fp-input">{{ __('admin.settings.preview_input') }}</span>
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>
    </section>

    <div class="panel mb-4">
        <h2 class="h6 mb-2">{{ __('admin.settings.maintenance_mode') }}</h2>
        <p class="page-intro mb-3">{{ __('admin.settings.maintenance_intro') }}</p>
        <label class="capability-item mb-3">
            <input type="checkbox" name="maintenance_mode" value="1" @checked($maintenanceMode ?? false)>
            <span><strong>{{ __('admin.settings.enable_maintenance') }}</strong><small>{{ __('admin.settings.enable_maintenance_hint') }}</small></span>
        </label>
        <div class="mb-0">
            <label class="form-label">{{ __('admin.settings.message') }}</label>
            <textarea name="maintenance_message" class="form-control" rows="3">{{ old('maintenance_message', $maintenanceMessage ?? '') }}</textarea>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">{{ __('admin.settings.save') }}</button>
    </div>
</form>

<section class="panel mt-4">
    <header class="panel-head">
        <h2 class="h6 mb-0">{{ __('admin.settings.demo_data') }}</h2>
        <p class="panel-desc mb-0">{{ __('admin.settings.demo_data_desc') }}</p>
    </header>
    <div class="panel-body">
        <form method="POST" action="{{ route('admin.settings.demo-data') }}" class="d-flex flex-wrap align-items-center gap-3"
              onsubmit="return confirm(@js(__('admin.settings.demo_confirm')));">
            @csrf
            <label class="capability-item mb-0">
                <input type="checkbox" name="fresh" value="1">
                <span><strong>{{ __('admin.settings.replace_demo') }}</strong><small>{{ __('admin.settings.replace_demo_hint') }}</small></span>
            </label>
            <button type="submit" class="btn btn-outline-primary">{{ __('admin.settings.install_demo') }}</button>
        </form>
    </div>
</section>

<section class="panel mt-4">
    <header class="panel-head">
        <h2 class="h6 mb-0">{{ __('admin.settings.aoyama_title') }}</h2>
        <p class="panel-desc mb-0">{!! __('admin.settings.aoyama_desc') !!}</p>
    </header>
    <div class="panel-body">
        <form method="POST" action="{{ route('admin.settings.aoyama-data') }}" class="d-flex flex-wrap align-items-center gap-3"
              onsubmit="return confirm(@js(__('admin.settings.aoyama_confirm')));">
            @csrf
            <label class="capability-item mb-0">
                <input type="checkbox" name="fresh" value="1">
                <span><strong>{{ __('admin.settings.replace_aoyama') }}</strong><small>{{ __('admin.settings.replace_aoyama_hint') }}</small></span>
            </label>
            <button type="submit" class="btn btn-outline-primary">{{ __('admin.settings.install_aoyama') }}</button>
        </form>
    </div>
</section>
@endsection

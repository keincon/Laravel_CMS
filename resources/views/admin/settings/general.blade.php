@extends('layouts.admin')
@section('title', 'General / UI')
@section('content')
<p class="page-intro mb-4">Update your site name, favicon, and admin UI styling. Changes apply immediately after save.</p>

<form method="POST" action="{{ route('admin.settings.general.update') }}" class="settings-form" x-data="{ framework: @js(old('ui_framework', $current)) }">
    @csrf @method('PUT')

    <section class="panel mb-3">
        <header class="panel-head">
            <h2 class="h6 mb-0">Site identity</h2>
            <p class="panel-desc mb-0">Shown in the admin brand, login screen, and public site.</p>
        </header>
        <div class="panel-body">
            <div class="mb-3">
                <label class="form-label" for="site_name">Site name</label>
                <input id="site_name" name="site_name" class="form-control" value="{{ old('site_name', $siteName) }}" required maxlength="255">
            </div>
            <div class="mb-3">
                <label class="form-label" for="site_description">Site description</label>
                <textarea id="site_description" name="site_description" class="form-control" rows="3" maxlength="500">{{ old('site_description', $siteDescription) }}</textarea>
            </div>
            <div>
                <label class="form-label">Favicon</label>
                <p class="form-text mb-2">PNG, WebP, JPEG, GIF, or ICO. Used in browser tabs on admin, login, and public pages.</p>
                <x-admin.media-picker
                    name="site_favicon_media_id"
                    :value="old('site_favicon_media_id', $faviconMediaId)"
                    label="Choose favicon"
                />
            </div>
        </div>
    </section>

    <section class="panel mb-3">
        <header class="panel-head">
            <h2 class="h6 mb-0">UI framework</h2>
            <p class="panel-desc mb-0">CDN stylesheet for admin and setup screens. Pick one — both keep the same blue/slate theme.</p>
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
                                <span class="fp-btn fp-primary">Save</span>
                                <span class="fp-btn fp-ghost">Cancel</span>
                                <span class="fp-input">Input</span>
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>
    </section>

    <div class="panel mb-4">
        <h2 class="h6 mb-2">Maintenance mode</h2>
        <p class="page-intro mb-3">When enabled, visitors see a maintenance page. Admins can still browse the site.</p>
        <label class="capability-item mb-3">
            <input type="checkbox" name="maintenance_mode" value="1" @checked($maintenanceMode ?? false)>
            <span><strong>Enable maintenance mode</strong><small>Like WordPress maintenance mode</small></span>
        </label>
        <div class="mb-0">
            <label class="form-label">Message</label>
            <textarea name="maintenance_message" class="form-control" rows="3">{{ old('maintenance_message', $maintenanceMessage ?? '') }}</textarea>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Save settings</button>
    </div>
</form>

<section class="panel mt-4">
    <header class="panel-head">
        <h2 class="h6 mb-0">Dummy data</h2>
        <p class="panel-desc mb-0">Seed sample posts, pages, categories, tags, comments, and a placeholder image so you can preview themes and layouts.</p>
    </header>
    <div class="panel-body">
        <form method="POST" action="{{ route('admin.settings.demo-data') }}" class="d-flex flex-wrap align-items-center gap-3"
              onsubmit="return confirm('Install sample posts, pages, terms, and comments? Existing demo slugs are skipped unless you choose replace.');">
            @csrf
            <label class="capability-item mb-0">
                <input type="checkbox" name="fresh" value="1">
                <span><strong>Replace previous demo content</strong><small>Soft-deletes known demo slugs, then reseeds</small></span>
            </label>
            <button type="submit" class="btn btn-outline-primary">Install Dummy Data</button>
        </form>
    </div>
</section>
@endsection

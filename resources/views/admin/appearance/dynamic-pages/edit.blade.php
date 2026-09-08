@extends('layouts.admin')
@section('title', __('admin.nav.dynamic_pages').': '.$setting->label)
@section('content')
<div class="mb-3">
    <a href="{{ route('admin.appearance.dynamic-pages.index') }}">← {{ __('admin.nav.dynamic_pages') }}</a>
</div>
<h1 class="h3 mb-1">{{ $setting->label }}</h1>
<p class="text-muted mb-4">
    Type: <strong>Dynamic</strong> · System: {{ $definition['label'] ?? $setting->type }}
</p>

<form method="POST" action="{{ route('admin.appearance.dynamic-pages.update', $setting->type) }}">
    @csrf @method('PUT')
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="border rounded p-3 bg-white mb-3">
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input name="title" class="form-control" value="{{ old('title', $setting->title) }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description', $setting->description) }}</textarea>
                </div>
                @if ($setting->type !== 'post' && $setting->type !== '404')
                    <div class="mb-3">
                        <label class="form-label">URL</label>
                        <input name="url_path" class="form-control" value="{{ old('url_path', $setting->url_path) }}" placeholder="/blog">
                        <div class="form-text">Informational for most types; routes are system-defined.</div>
                    </div>
                @endif
                @if (! in_array($setting->type, ['post', '404'], true))
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Posts Per Page</label>
                            <input type="number" name="posts_per_page" class="form-control" min="1" max="100" value="{{ old('posts_per_page', $setting->posts_per_page) }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Layout</label>
                            <select name="layout" class="form-select">
                                @foreach (['list' => 'List', 'grid' => 'Grid'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('layout', $setting->layout) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Sidebar</label>
                            <select name="sidebar_position" class="form-select">
                                <option value="">Inherit master</option>
                                @foreach (['none' => 'None', 'left' => 'Left', 'right' => 'Right'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('sidebar_position', $setting->sidebar_position) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif

                @if ($setting->type === '404')
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="message" class="form-control" rows="3">{{ old('message', $setting->message) }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Button Label</label>
                            <input name="button_label" class="form-control" value="{{ old('button_label', $setting->button_label) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Button URL</label>
                            <input name="button_url" class="form-control" value="{{ old('button_url', $setting->button_url) }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image URL</label>
                        <input name="image_url" class="form-control" value="{{ old('image_url', $setting->image_url) }}">
                    </div>
                @endif

                @if ($setting->type === 'archive')
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="archive_enabled" value="1" id="archive_enabled" @checked(old('archive_enabled', $setting->settings['enabled'] ?? true))>
                        <label class="form-check-label" for="archive_enabled">Enable date archives</label>
                    </div>
                @endif
            </div>

            <div class="border rounded p-3 bg-white mb-3">
                <h2 class="h6">SEO overrides for this dynamic type</h2>
                <div class="mb-3">
                    <label class="form-label">Title template</label>
                    <input name="seo_title_template" class="form-control" value="{{ old('seo_title_template', $setting->seo_title_template) }}" placeholder="Leave blank to use global SEO Templates">
                </div>
                <div class="mb-3">
                    <label class="form-label">Description template</label>
                    <textarea name="seo_description_template" class="form-control" rows="2">{{ old('seo_description_template', $setting->seo_description_template) }}</textarea>
                </div>
                <div class="mb-0">
                    <label class="form-label">Robots</label>
                    <input name="seo_robots" class="form-control" value="{{ old('seo_robots', $setting->seo_robots) }}" placeholder="{{ $setting->type === 'search' ? 'noindex, follow' : 'index, follow' }}">
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="border rounded p-3 bg-white mb-3">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="is_enabled" value="1" id="is_enabled" @checked(old('is_enabled', $setting->is_enabled))>
                    <label class="form-check-label" for="is_enabled">Enabled</label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Header</label>
                    <select name="header_mode" class="form-select mb-2">
                        <option value="master" @selected(old('header_mode', $setting->header_mode) === 'master')>Master Header</option>
                        <option value="custom" @selected(old('header_mode', $setting->header_mode) === 'custom')>Custom</option>
                        <option value="disable" @selected(old('header_mode', $setting->header_mode) === 'disable')>Disable</option>
                    </select>
                    <select name="header_id" class="form-select">
                        <option value="">—</option>
                        @foreach ($headers as $header)
                            <option value="{{ $header->id }}" @selected(old('header_id', $setting->header_id) == $header->id)>{{ $header->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label">Footer</label>
                    <select name="footer_mode" class="form-select mb-2">
                        <option value="master" @selected(old('footer_mode', $setting->footer_mode) === 'master')>Master Footer</option>
                        <option value="custom" @selected(old('footer_mode', $setting->footer_mode) === 'custom')>Custom</option>
                        <option value="disable" @selected(old('footer_mode', $setting->footer_mode) === 'disable')>Disable</option>
                    </select>
                    <select name="footer_id" class="form-select">
                        <option value="">—</option>
                        @foreach ($footers as $footer)
                            <option value="{{ $footer->id }}" @selected(old('footer_id', $setting->footer_id) == $footer->id)>{{ $footer->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button class="btn btn-primary w-100" type="submit">Save Dynamic Page</button>
        </div>
    </div>
</form>
@endsection

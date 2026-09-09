@extends('layouts.admin')

@section('title', __('admin.nav.seo'))

@section('content')
<div x-data="{
    title: @js(old('seo_title', $settings->seo_title)),
    description: @js(old('meta_description', $settings->meta_description)),
    url: @js(old('canonical_url', $settings->canonical_url ?: $siteUrl)),
    titleFallback: @js(__('admin.settings.seo_preview_title_fallback')),
    descFallback: @js(__('admin.settings.seo_preview_desc_fallback'))
}">
    <h1 class="h3 mb-2">{{ __('admin.nav.seo') }}</h1>
    <p class="text-muted mb-4">{{ __('admin.settings.seo_intro') }}</p>

    <form method="POST" action="{{ route('admin.settings.seo.update') }}" class="row g-4">
        @csrf
        @method('PUT')

        <div class="col-lg-7">
            <div class="mb-3">
                <label class="form-label">{{ __('admin.settings.seo_site_title') }}</label>
                <input type="text" name="seo_title" class="form-control" x-model="title" maxlength="255">
                <div class="small text-muted mt-1"><span x-text="title.length"></span> {{ __('admin.settings.seo_title_count') }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('admin.settings.seo_meta_description') }}</label>
                <textarea name="meta_description" class="form-control" rows="4" x-model="description"></textarea>
                <div class="small text-muted mt-1"><span x-text="description.length"></span> {{ __('admin.settings.seo_meta_count') }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('admin.settings.seo_keywords') }}</label>
                <input type="text" name="keywords" class="form-control" value="{{ old('keywords', $settings->keywords) }}">
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('admin.settings.seo_canonical') }}</label>
                <input type="url" name="canonical_url" class="form-control" x-model="url">
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('admin.settings.seo_robots') }}</label>
                <input type="text" name="robots" class="form-control" value="{{ old('robots', $settings->robots ?: 'index, follow') }}">
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('admin.settings.seo_organization_name') }}</label>
                <input type="text" name="organization_name" class="form-control" value="{{ old('organization_name', $settings->organization_name) }}">
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('admin.settings.seo_organization_logo') }}</label>
                <input type="url" name="organization_logo_url" class="form-control" value="{{ old('organization_logo_url', $settings->organization_logo_url) }}">
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="sitemap_enabled" value="1" id="sitemap" @checked(old('sitemap_enabled', $settings->sitemap_enabled))>
                <label class="form-check-label" for="sitemap">{{ __('admin.settings.seo_sitemap_enabled') }}</label>
            </div>
            <button class="btn btn-primary" type="submit">{{ __('admin.settings.seo_save') }}</button>
        </div>
        <div class="col-lg-5">
            <h2 class="h6 text-muted">{{ __('admin.settings.seo_preview') }}</h2>
            <div class="border rounded p-3 bg-white mb-3" style="border-color:#e2e8f0">
                <div class="small mb-1" style="color:#202124" x-text="(new URL(url || '{{ $siteUrl }}')).hostname"></div>
                <div class="fw-semibold mb-1" style="color:#1a0dab;font-size:1.1rem" x-text="(title || titleFallback).slice(0,60)"></div>
                <div class="small" style="color:#4d5156" x-text="(description || descFallback).slice(0,160)"></div>
            </div>
            <p class="small text-muted mb-0">{{ __('admin.settings.seo_sitemap_link') }}: <a href="{{ url('/sitemap.xml') }}" target="_blank">/sitemap.xml</a></p>
            <p class="small text-muted">{{ __('admin.settings.seo_robots_link') }}: <a href="{{ url('/robots.txt') }}" target="_blank">/robots.txt</a></p>
        </div>
    </form>
</div>
@endsection

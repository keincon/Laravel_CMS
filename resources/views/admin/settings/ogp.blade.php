@extends('layouts.admin')

@section('title', __('admin.nav.social_ogp'))

@section('content')
@php $ogImage = old('og_image_url', \App\Models\CmsSetting::getValue('og_image_url')); @endphp
<div x-data="{
    title: @js(old('og_title', $settings->og_title ?: $settings->seo_title ?: $siteName)),
    description: @js(old('og_description', $settings->og_description ?: $settings->meta_description)),
    url: @js($siteUrl),
    image: @js($ogImage)
}">
    <h1 class="h3 mb-2">{{ __('admin.nav.social_ogp') }}</h1>
    <p class="text-muted mb-4">Open Graph and X/Twitter card defaults.</p>

    <form method="POST" action="{{ route('admin.settings.ogp.update') }}" class="row g-4">
        @csrf
        @method('PUT')
        <div class="col-lg-6">
            <div class="mb-3">
                <label class="form-label">og:title</label>
                <input type="text" name="og_title" class="form-control" x-model="title">
            </div>
            <div class="mb-3">
                <label class="form-label">og:description</label>
                <textarea name="og_description" class="form-control" rows="3" x-model="description"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">og:type</label>
                <input type="text" name="og_type" class="form-control" value="{{ old('og_type', $settings->og_type ?: 'website') }}">
            </div>
            <div class="mb-3">
                <label class="form-label">og:site_name</label>
                <input type="text" name="og_site_name" class="form-control" value="{{ old('og_site_name', $settings->og_site_name ?: $siteName) }}">
            </div>
            <div class="mb-3">
                <label class="form-label">og:locale</label>
                <input type="text" name="og_locale" class="form-control" value="{{ old('og_locale', $settings->og_locale ?: 'en_US') }}">
            </div>
            <div class="mb-3">
                <label class="form-label">og:image URL</label>
                <input type="url" name="og_image_url" class="form-control" x-model="image" placeholder="https://…">
            </div>
            <div class="mb-4">
                <label class="form-label">Twitter card</label>
                <select name="twitter_card" class="form-select">
                    <option value="summary_large_image" @selected(old('twitter_card', $settings->twitter_card) === 'summary_large_image')>summary_large_image</option>
                    <option value="summary" @selected(old('twitter_card', $settings->twitter_card) === 'summary')>summary</option>
                </select>
            </div>
            <button class="btn btn-primary" type="submit">Save OGP Settings</button>
        </div>
        <div class="col-lg-6">
            <h2 class="h6">Facebook</h2>
            <x-admin.ogp-preview variant="facebook" ::title="title" ::description="description" ::url="url" ::image="image" />
            <div class="mt-3" x-show="false"></div>
            <div class="ogp-preview overflow-hidden rounded border bg-white mb-3" style="max-width:480px;border-color:#e2e8f0">
                <div style="aspect-ratio:1.91/1;background:#e2e8f0;display:grid;place-items:center;overflow:hidden">
                    <template x-if="image"><img :src="image" alt="" style="width:100%;height:100%;object-fit:cover"></template>
                    <span class="text-muted small" x-show="!image">OGP IMAGE</span>
                </div>
                <div class="p-3" style="background:#f0f2f5">
                    <div class="small text-uppercase text-muted mb-1" x-text="(new URL(url)).hostname.toUpperCase()"></div>
                    <div class="fw-semibold mb-1" x-text="(title || 'Title').slice(0,70)"></div>
                    <div class="small text-muted" x-text="(description || 'Description').slice(0,120)"></div>
                </div>
            </div>
            <h2 class="h6">LinkedIn</h2>
            <div class="ogp-preview overflow-hidden rounded border bg-white mb-3" style="max-width:480px;border-color:#e2e8f0">
                <div style="aspect-ratio:1.91/1;background:#e2e8f0;display:grid;place-items:center;overflow:hidden">
                    <template x-if="image"><img :src="image" alt="" style="width:100%;height:100%;object-fit:cover"></template>
                    <span class="text-muted small" x-show="!image">OGP IMAGE</span>
                </div>
                <div class="p-3">
                    <div class="fw-semibold mb-1" x-text="(title || 'Title').slice(0,70)"></div>
                    <div class="small text-muted" x-text="(description || 'Description').slice(0,120)"></div>
                    <div class="small text-muted mt-2" x-text="(new URL(url)).hostname"></div>
                </div>
            </div>
            <h2 class="h6">X / Twitter</h2>
            <div class="ogp-preview overflow-hidden rounded border bg-white" style="max-width:480px;border-color:#e2e8f0">
                <div style="aspect-ratio:1.91/1;background:#e2e8f0;display:grid;place-items:center;overflow:hidden">
                    <template x-if="image"><img :src="image" alt="" style="width:100%;height:100%;object-fit:cover"></template>
                    <span class="text-muted small" x-show="!image">OGP IMAGE</span>
                </div>
                <div class="p-3">
                    <div class="fw-semibold mb-1" x-text="(title || 'Title').slice(0,70)"></div>
                    <div class="small text-muted" x-text="(description || 'Description').slice(0,120)"></div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

{{-- Shared SEO + OGP fields for post/page editors --}}
@props([
    'model' => null,
    'previewUrl' => '',
])

@php
    $title = old('seo_title', $model?->seo_title);
    $description = old('seo_description', $model?->seo_description);
    $canonical = old('seo_canonical', $model?->seo_canonical ?: $previewUrl);
@endphp

<div class="border rounded p-3 mb-4" x-data="{
    title: @js($title ?? ''),
    description: @js($description ?? ''),
    url: @js($canonical ?? '')
}">
    <h3 class="h6 mb-3">SEO</h3>
    <div class="mb-3">
        <label class="form-label">SEO Title</label>
        <input type="text" name="seo_title" class="form-control" x-model="title">
        <div class="small text-muted"><span x-text="title.length"></span> / 60 characters</div>
    </div>
    <div class="mb-3">
        <label class="form-label">Meta Description</label>
        <textarea name="seo_description" class="form-control" rows="3" x-model="description"></textarea>
        <div class="small text-muted"><span x-text="description.length"></span> / 160 characters</div>
    </div>
    <div class="mb-3">
        <label class="form-label">Canonical URL</label>
        <input type="url" name="seo_canonical" class="form-control" x-model="url">
    </div>
    <div class="mb-3">
        <label class="form-label">Robots</label>
        <input type="text" name="seo_robots" class="form-control" value="{{ old('seo_robots', $model?->seo_robots) }}" placeholder="index, follow">
    </div>

    <h3 class="h6 mt-4 mb-3">Open Graph</h3>
    <div class="mb-3">
        <label class="form-label">OGP Title</label>
        <input type="text" name="og_title" class="form-control" value="{{ old('og_title', $model?->og_title) }}">
    </div>
    <div class="mb-3">
        <label class="form-label">OGP Description</label>
        <textarea name="og_description" class="form-control" rows="2">{{ old('og_description', $model?->og_description) }}</textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">OGP Type</label>
        <input type="text" name="og_type" class="form-control" value="{{ old('og_type', $model?->og_type) }}" placeholder="article">
    </div>

    <h3 class="h6 mt-4 mb-2">Search preview</h3>
    <div class="border rounded p-3 bg-white mb-3">
        <div class="small" style="color:#202124" x-text="url ? (new URL(url, window.location.origin)).hostname : 'example.com'"></div>
        <div class="fw-semibold" style="color:#1a0dab" x-text="(title || 'SEO Title').slice(0,60)"></div>
        <div class="small" style="color:#4d5156" x-text="(description || 'Meta description…').slice(0,160)"></div>
    </div>
</div>

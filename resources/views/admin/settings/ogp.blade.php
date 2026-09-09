@extends('layouts.admin')

@section('title', __('admin.ogp.title'))

@section('content')
@php
    $siteLanguage = \App\Models\CmsSetting::getValue('language', 'en');
    $defaultLocale = old(
        'og_locale',
        $settings->og_locale
            ?: ($siteLanguage === 'ja' ? 'ja_JP' : 'en_US')
    );
    $ogImage = old('og_image_url', \App\Models\CmsSetting::getValue('og_image_url', ''));
    $previewHost = strtoupper(parse_url($siteUrl ?: url('/'), PHP_URL_HOST) ?: 'LOCALHOST');
@endphp

<div
    x-data="{
        title: @js(old('og_title', $settings->og_title ?: $settings->seo_title ?: $siteName)),
        description: @js(old('og_description', $settings->og_description ?: $settings->meta_description)),
        host: @js($previewHost),
        image: @js($ogImage),
        card: @js(old('twitter_card', $settings->twitter_card ?: 'summary_large_image')),
        placeholders: {
            title: @js(__('admin.ogp.preview_title_fallback')),
            description: @js(__('admin.ogp.preview_desc_fallback')),
            image: @js(__('admin.ogp.preview_image_fallback')),
        }
    }"
>
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1">{{ __('admin.ogp.title') }}</h1>
            <p class="page-intro mb-0">{{ __('admin.ogp.intro') }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.settings.seo') }}">{{ __('admin.ogp.link_seo') }}</a>
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.settings.seo.templates') }}">{{ __('admin.ogp.link_templates') }}</a>
        </div>
    </div>

    <div class="alert alert-info small mb-3" role="status">
        {!! __('admin.ogp.help_html', ['seo' => route('admin.settings.seo')]) !!}
    </div>

    <form method="POST" action="{{ route('admin.settings.ogp.update') }}" class="row g-4">
        @csrf
        @method('PUT')

        <div class="col-lg-6">
            <section class="panel mb-3">
                <header class="panel-head">
                    <h2 class="h6 mb-0">{{ __('admin.ogp.section_defaults') }}</h2>
                    <p class="panel-desc mb-0">{{ __('admin.ogp.section_defaults_desc') }}</p>
                </header>
                <div class="panel-body">
                    <div class="mb-3">
                        <label class="form-label" for="og_title">{{ __('admin.ogp.og_title') }}</label>
                        <input id="og_title" type="text" name="og_title" class="form-control" x-model="title" maxlength="255">
                        <div class="form-text">
                            <span x-text="title.length"></span> / 70
                            <span class="text-muted">{{ __('admin.ogp.chars_recommended') }}</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="og_description">{{ __('admin.ogp.og_description') }}</label>
                        <textarea id="og_description" name="og_description" class="form-control" rows="3" x-model="description" maxlength="1000"></textarea>
                        <div class="form-text">
                            <span x-text="description.length"></span> / 200
                            <span class="text-muted">{{ __('admin.ogp.chars_recommended') }}</span>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="og_type">{{ __('admin.ogp.og_type') }}</label>
                            <select id="og_type" name="og_type" class="form-select">
                                @foreach (['website', 'article', 'product', 'profile'] as $type)
                                    <option value="{{ $type }}" @selected(old('og_type', $settings->og_type ?: 'website') === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="og_locale">{{ __('admin.ogp.og_locale') }}</label>
                            <input id="og_locale" type="text" name="og_locale" class="form-control" value="{{ $defaultLocale }}" list="og-locale-suggestions" placeholder="ja_JP">
                            <datalist id="og-locale-suggestions">
                                <option value="ja_JP"></option>
                                <option value="en_US"></option>
                            </datalist>
                            <div class="form-text">{{ __('admin.ogp.og_locale_help') }}</div>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="og_site_name">{{ __('admin.ogp.og_site_name') }}</label>
                        <input id="og_site_name" type="text" name="og_site_name" class="form-control" value="{{ old('og_site_name', $settings->og_site_name ?: $siteName) }}" maxlength="255">
                    </div>
                </div>
            </section>

            <section class="panel mb-3">
                <header class="panel-head">
                    <h2 class="h6 mb-0">{{ __('admin.ogp.section_image') }}</h2>
                    <p class="panel-desc mb-0">{{ __('admin.ogp.section_image_desc') }}</p>
                </header>
                <div class="panel-body">
                    <div class="mb-3">
                        <label class="form-label" for="og_image_url">{{ __('admin.ogp.og_image_url') }}</label>
                        <input id="og_image_url" type="url" name="og_image_url" class="form-control" x-model="image" placeholder="https://…">
                        <div class="form-text">{{ __('admin.ogp.og_image_help') }}</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="twitter_card">{{ __('admin.ogp.twitter_card') }}</label>
                        <select id="twitter_card" name="twitter_card" class="form-select" x-model="card">
                            <option value="summary_large_image">{{ __('admin.ogp.card_large') }}</option>
                            <option value="summary">{{ __('admin.ogp.card_summary') }}</option>
                        </select>
                    </div>
                </div>
            </section>

            <button class="btn btn-primary" type="submit">{{ __('admin.ogp.save') }}</button>
        </div>

        <div class="col-lg-6">
            <div class="panel sticky-lg-top" style="top:1rem">
                <header class="panel-head">
                    <h2 class="h6 mb-0">{{ __('admin.ogp.preview_heading') }}</h2>
                    <p class="panel-desc mb-0">{{ __('admin.ogp.preview_desc') }}</p>
                </header>
                <div class="panel-body">
                    <h3 class="h6 mb-2">Facebook</h3>
                    <div class="ogp-preview overflow-hidden rounded border bg-white mb-4" style="max-width:480px;border-color:#e2e8f0">
                        <div style="aspect-ratio:1.91/1;background:#e2e8f0;display:grid;place-items:center;overflow:hidden">
                            <template x-if="image"><img :src="image" alt="" style="width:100%;height:100%;object-fit:cover" x-on:error="image=''"></template>
                            <span class="text-muted small" x-show="!image" x-text="placeholders.image"></span>
                        </div>
                        <div class="p-3" style="background:#f0f2f5">
                            <div class="small text-uppercase text-muted mb-1" x-text="host"></div>
                            <div class="fw-semibold mb-1" x-text="(title || placeholders.title).slice(0,70)"></div>
                            <div class="small text-muted" x-text="(description || placeholders.description).slice(0,120)"></div>
                        </div>
                    </div>

                    <h3 class="h6 mb-2">LinkedIn</h3>
                    <div class="ogp-preview overflow-hidden rounded border bg-white mb-4" style="max-width:480px;border-color:#e2e8f0">
                        <div style="aspect-ratio:1.91/1;background:#e2e8f0;display:grid;place-items:center;overflow:hidden">
                            <template x-if="image"><img :src="image" alt="" style="width:100%;height:100%;object-fit:cover"></template>
                            <span class="text-muted small" x-show="!image" x-text="placeholders.image"></span>
                        </div>
                        <div class="p-3">
                            <div class="fw-semibold mb-1" x-text="(title || placeholders.title).slice(0,70)"></div>
                            <div class="small text-muted" x-text="(description || placeholders.description).slice(0,120)"></div>
                            <div class="small text-muted mt-2" x-text="host.toLowerCase()"></div>
                        </div>
                    </div>

                    <h3 class="h6 mb-2">X / Twitter</h3>
                    <div class="ogp-preview overflow-hidden rounded border bg-white" style="max-width:480px;border-color:#e2e8f0" :class="{ 'is-summary': card === 'summary' }">
                        <div :style="card === 'summary' ? 'aspect-ratio:1/1;max-width:8rem;margin:0.75rem' : 'aspect-ratio:1.91/1'" style="background:#e2e8f0;display:grid;place-items:center;overflow:hidden">
                            <template x-if="image"><img :src="image" alt="" style="width:100%;height:100%;object-fit:cover"></template>
                            <span class="text-muted small" x-show="!image" x-text="placeholders.image"></span>
                        </div>
                        <div class="p-3">
                            <div class="fw-semibold mb-1" x-text="(title || placeholders.title).slice(0,70)"></div>
                            <div class="small text-muted" x-text="(description || placeholders.description).slice(0,120)"></div>
                            <div class="small text-muted mt-2" x-text="card"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

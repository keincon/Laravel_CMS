@extends('layouts.admin')
@section('title', __('admin.nav.reading'))
@section('content')
@php
    $homepageType = old('homepage_type', $settings->homepage_type ?: 'posts');
@endphp
<h1 class="h3 mb-2">{{ __('admin.settings.reading_title') }}</h1>
<p class="text-muted mb-4">{{ __('admin.settings.reading_intro') }}</p>
<form method="POST" action="{{ route('admin.settings.reading.update') }}">
    @csrf @method('PUT')
    <div class="mb-3">
        <label class="form-label">{{ __('admin.settings.reading_homepage') }}</label>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="homepage_type" value="posts" id="hp-posts" @checked($homepageType === 'posts')>
            <label class="form-check-label" for="hp-posts">
                {{ __('admin.settings.reading_latest_posts') }}
                <span class="text-muted">{{ __('admin.settings.reading_latest_posts_hint') }}</span>
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="homepage_type" value="static" id="hp-static" @checked($homepageType === 'static')>
            <label class="form-check-label" for="hp-static">{{ __('admin.settings.reading_static_page') }}</label>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label" for="homepage_page_id">{{ __('admin.settings.reading_homepage_page') }}</label>
        <select id="homepage_page_id" name="homepage_page_id" class="form-select">
            <option value="">{{ __('admin.settings.reading_none') }}</option>
            @foreach ($pages as $page)
                <option value="{{ $page->id }}" @selected((string) old('homepage_page_id', $settings->homepage_page_id) === (string) $page->id)>{{ $page->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label" for="posts_page_id">{{ __('admin.settings.reading_posts_page') }}</label>
        <select id="posts_page_id" name="posts_page_id" class="form-select">
            <option value="">{{ __('admin.settings.reading_none') }}</option>
            @foreach ($pages as $page)
                <option value="{{ $page->id }}" @selected((string) old('posts_page_id', $settings->posts_page_id) === (string) $page->id)>{{ $page->title }}</option>
            @endforeach
        </select>
        <div class="form-text">{{ __('admin.settings.reading_blog_hint') }}</div>
    </div>
    <button class="btn btn-primary" type="submit">{{ __('admin.settings.reading_save') }}</button>
</form>
@endsection

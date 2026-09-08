@extends('layouts.admin')
@section('title', __('admin.nav.reading'))
@section('content')
<h1 class="h3 mb-2">{{ __('admin.settings.reading_title') }}</h1>
<p class="text-muted mb-4">Choose whether the homepage is a Static Page or the Dynamic latest-posts feed.</p>
<form method="POST" action="{{ route('admin.settings.reading.update') }}">
    @csrf @method('PUT')
    <div class="mb-3">
        <label class="form-label">Homepage</label>
        <div class="form-check"><input class="form-check-input" type="radio" name="homepage_type" value="posts" id="hp-posts" @checked(old('homepage_type', $settings->homepage_type) === 'posts')><label class="form-check-label" for="hp-posts">Latest Posts <span class="text-muted">(Dynamic Blog/Home)</span></label></div>
        <div class="form-check"><input class="form-check-input" type="radio" name="homepage_type" value="static" id="hp-static" @checked(old('homepage_type', $settings->homepage_type) !== 'posts')><label class="form-check-label" for="hp-static">Static Page</label></div>
    </div>
    <div class="mb-3">
        <label class="form-label">Homepage Static Page</label>
        <select name="homepage_page_id" class="form-select">
            <option value="">—</option>
            @foreach ($pages as $page)
                <option value="{{ $page->id }}" @selected($settings->homepage_page_id == $page->id)>{{ $page->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Posts Page (optional legacy link)</label>
        <select name="posts_page_id" class="form-select">
            <option value="">—</option>
            @foreach ($pages as $page)
                <option value="{{ $page->id }}" @selected($settings->posts_page_id == $page->id)>{{ $page->title }}</option>
            @endforeach
        </select>
        <div class="form-text">Blog archive settings (title, layout, posts per page) are configured under Appearance → Dynamic Pages → Blog.</div>
    </div>
    <button class="btn btn-primary" type="submit">Save Reading Settings</button>
</form>
@endsection

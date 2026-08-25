@extends('layouts.admin')
@section('title', 'Reading')
@section('content')
<h1 class="h3 mb-2">Reading Settings</h1>
<p class="text-muted mb-4">Configure homepage and posts page (WordPress-style).</p>
<form method="POST" action="{{ route('admin.settings.reading.update') }}">
    @csrf @method('PUT')
    <div class="mb-3">
        <label class="form-label">Homepage displays</label>
        <div class="form-check"><input class="form-check-input" type="radio" name="homepage_type" value="posts" id="hp-posts" @checked(old('homepage_type', $settings->homepage_type) === 'posts')><label class="form-check-label" for="hp-posts">Latest Posts</label></div>
        <div class="form-check"><input class="form-check-input" type="radio" name="homepage_type" value="static" id="hp-static" @checked(old('homepage_type', $settings->homepage_type) !== 'posts')><label class="form-check-label" for="hp-static">Static Page</label></div>
    </div>
    <div class="mb-3">
        <label class="form-label">Homepage</label>
        <select name="homepage_page_id" class="form-select">
            <option value="">—</option>
            @foreach ($pages as $page)
                <option value="{{ $page->id }}" @selected($settings->homepage_page_id == $page->id)>{{ $page->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Posts Page</label>
        <select name="posts_page_id" class="form-select">
            <option value="">—</option>
            @foreach ($pages as $page)
                <option value="{{ $page->id }}" @selected($settings->posts_page_id == $page->id)>{{ $page->title }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn btn-primary" type="submit">Save Reading Settings</button>
</form>
@endsection

@extends('layouts.admin')
@section('title', $post->exists ? 'Edit Post' : 'New Post')
@section('content')
<h1 class="h3 mb-3">{{ $post->exists ? 'Edit Post' : 'New Post' }}</h1>
<form method="POST" action="{{ $post->exists ? route('admin.posts.update', $post) : route('admin.posts.store') }}">
    @csrf
    @if ($post->exists) @method('PUT') @endif
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="mb-3"><label class="form-label">Title</label><input name="title" class="form-control" value="{{ old('title', $post->title) }}" required></div>
            <div class="mb-3"><label class="form-label">Slug</label><input name="slug" class="form-control" value="{{ old('slug', $post->slug) }}"></div>
            <div class="mb-3"><label class="form-label">Excerpt</label><textarea name="excerpt" class="form-control" rows="2">{{ old('excerpt', $post->excerpt) }}</textarea></div>
            <div class="mb-3"><label class="form-label">Content</label><textarea name="content" class="form-control" rows="12">{{ old('content', $post->content) }}</textarea></div>
            <x-admin.seo-fields :model="$post" />
        </div>
        <div class="col-lg-4">
            <div class="border rounded p-3 bg-white mb-3">
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach (['draft','publish','private'] as $st)
                            <option value="{{ $st }}" @selected(old('status', $post->status) === $st)>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Template</label>
                    <select name="template" class="form-select">
                        @foreach ($templates as $value => $label)
                            <option value="{{ $value }}" @selected(old('template', $post->template ?: 'default') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Categories</label>
                    <select name="categories[]" class="form-select" multiple size="5">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(in_array($category->id, old('categories', $selectedCategories)))>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tags</label>
                    <select name="tags[]" class="form-select" multiple size="5">
                        @foreach ($tags as $tag)
                            <option value="{{ $tag->id }}" @selected(in_array($tag->id, old('tags', $selectedTags)))>{{ $tag->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Header</label>
                    <select name="header_mode" class="form-select mb-2">
                        <option value="master" @selected(old('header_mode', $post->header_mode ?: 'master') === 'master')>Use Master Header</option>
                        <option value="custom" @selected(old('header_mode', $post->header_mode) === 'custom')>Custom Header</option>
                        <option value="disable" @selected(old('header_mode', $post->header_mode) === 'disable')>Disable Header</option>
                    </select>
                    <select name="header_id" class="form-select">
                        <option value="">—</option>
                        @foreach ($headers as $header)
                            <option value="{{ $header->id }}" @selected(old('header_id', $post->header_id) == $header->id)>{{ $header->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Footer</label>
                    <select name="footer_mode" class="form-select mb-2">
                        <option value="master" @selected(old('footer_mode', $post->footer_mode ?: 'master') === 'master')>Use Master Footer</option>
                        <option value="custom" @selected(old('footer_mode', $post->footer_mode) === 'custom')>Custom Footer</option>
                        <option value="disable" @selected(old('footer_mode', $post->footer_mode) === 'disable')>Disable Footer</option>
                    </select>
                    <select name="footer_id" class="form-select">
                        <option value="">—</option>
                        @foreach ($footers as $footer)
                            <option value="{{ $footer->id }}" @selected(old('footer_id', $post->footer_id) == $footer->id)>{{ $footer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Sidebar</label>
                    <select name="sidebar_position" class="form-select">
                        <option value="">Inherit</option>
                        @foreach (['none','left','right'] as $pos)
                            <option value="{{ $pos }}" @selected(old('sidebar_position', $post->sidebar_position) === $pos)>{{ ucfirst($pos) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" type="submit">{{ $post->exists ? 'Update' : 'Create' }}</button>
                    @if ($post->exists)
                        <a class="btn btn-outline-secondary" target="_blank" href="{{ route('admin.posts.preview', $post) }}">Preview</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

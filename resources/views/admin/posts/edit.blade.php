@extends('layouts.admin')

@section('title', $post->exists ? __('admin.posts.edit') : __('admin.posts.add'))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">{{ $post->exists ? __('admin.posts.edit') : __('admin.posts.add') }}</h1>
    @if ($post->exists)
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.posts.preview', $post) }}" target="_blank">Preview</a>
            <form method="POST" action="{{ route('admin.posts.duplicate', $post) }}">@csrf
                <button class="btn btn-outline-secondary btn-sm" type="submit">Duplicate</button>
            </form>
        </div>
    @endif
</div>

<form method="POST" action="{{ $post->exists ? route('admin.posts.update', $post) : route('admin.posts.store') }}">
    @csrf
    @if ($post->exists) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="panel mb-3">
                <div class="mb-3">
                    <label class="form-label" for="title">Title</label>
                    <input id="title" name="title" class="form-control" value="{{ old('title', $post->title) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="slug">Permalink slug</label>
                    <input id="slug" name="slug" class="form-control" value="{{ old('slug', $post->slug) }}">
                </div>
                <div class="mb-3">
                    <x-admin.html-editor name="content" :value="old('content', $post->content)" :rows="14" label="Content (HTML)" />
                </div>
                <div class="mb-0">
                    <label class="form-label" for="excerpt">Excerpt</label>
                    <textarea id="excerpt" name="excerpt" class="form-control" rows="3">{{ old('excerpt', $post->excerpt) }}</textarea>
                </div>
            </div>
            <div class="panel mb-3">
                <h2 class="h6 mb-3">SEO</h2>
                <x-admin.seo-fields :model="$post" />
            </div>
            <x-admin.custom-code-fields :model="$post" />
        </div>

        <div class="col-lg-4">
            <div class="panel mb-3">
                <h2 class="h6 mb-3">Publish</h2>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach (['draft' => 'Draft', 'pending' => 'Pending Review', 'publish' => 'Published', 'private' => 'Private'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $post->status ?: 'draft') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Publish date</label>
                    <input type="datetime-local" name="published_at" class="form-control"
                           value="{{ old('published_at', optional($post->published_at)->format('Y-m-d\\TH:i')) }}">
                </div>
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" type="submit">{{ $post->exists ? 'Update' : 'Publish / Save' }}</button>
                    @if ($post->exists && $post->status !== 'trash')
                        <button class="btn btn-outline-danger" form="move-trash" type="submit">Move to Trash</button>
                    @endif
                </div>
            </div>

            <div class="panel mb-3">
                <h2 class="h6 mb-3">Categories</h2>
                <select name="categories[]" class="form-select" multiple size="6">
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(in_array($category->id, old('categories', $selectedCategories)))>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="panel mb-3">
                <h2 class="h6 mb-3">Tags</h2>
                <select name="tags[]" class="form-select" multiple size="5">
                    @foreach ($tags as $tag)
                        <option value="{{ $tag->id }}" @selected(in_array($tag->id, old('tags', $selectedTags)))>{{ $tag->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="panel mb-3">
                <h2 class="h6 mb-3">Featured image</h2>
                <x-admin.media-picker name="featured_image_id" :value="old('featured_image_id', $post->featured_image_id)" />
            </div>

            <div class="panel mb-3">
                <h2 class="h6 mb-3">Discussion</h2>
                <select name="comment_status" class="form-select">
                    <option value="open" @selected(old('comment_status', $post->comment_status ?: 'open') === 'open')>Allow comments</option>
                    <option value="closed" @selected(old('comment_status', $post->comment_status) === 'closed')>Close comments</option>
                </select>
            </div>

            <div class="panel mb-3">
                <h2 class="h6 mb-3">Template & layout</h2>
                <div class="mb-3">
                    <label class="form-label">Template</label>
                    <select name="template" class="form-select">
                        @foreach ($templates as $value => $label)
                            <option value="{{ $value }}" @selected(old('template', $post->template ?: 'default') === $value)>{{ $label }}</option>
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
                <div>
                    <label class="form-label">Sidebar</label>
                    <select name="sidebar_position" class="form-select">
                        <option value="">Inherit</option>
                        @foreach (['none','left','right'] as $pos)
                            <option value="{{ $pos }}" @selected(old('sidebar_position', $post->sidebar_position) === $pos)>{{ ucfirst($pos) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if (!empty($revisions) && $revisions->isNotEmpty())
                <div class="panel">
                    <h2 class="h6 mb-2">Revisions</h2>
                    @foreach ($revisions as $revision)
                        <div class="small py-2 border-bottom">
                            {{ $revision->note }} · {{ $revision->created_at?->diffForHumans() }}
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</form>

@if ($post->exists && $post->status !== 'trash')
    <form id="move-trash" method="POST" action="{{ route('admin.posts.destroy', $post) }}" class="d-none">
        @csrf
        @method('DELETE')
    </form>
@endif
@endsection

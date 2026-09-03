@extends('layouts.admin')

@section('title', $page->exists ? 'Edit Page' : 'Add New Page')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">{{ $page->exists ? 'Edit Page' : 'Add New Page' }}</h1>
        <p class="page-intro mb-0">Static page content. Reserved slugs: <code>{{ implode(', ', $reservedSlugs ?? []) }}</code></p>
    </div>
    @if ($page->exists && ! $page->trashed())
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.pages.preview', $page) }}" target="_blank">Preview</a>
            <form method="POST" action="{{ route('admin.pages.duplicate', $page->id) }}">@csrf
                <button class="btn btn-outline-secondary btn-sm" type="submit">Duplicate</button>
            </form>
        </div>
    @endif
</div>

@if ($page->trashed())
    <div class="panel mb-3">
        <strong>This page is in the Trash.</strong>
        <form method="POST" action="{{ route('admin.pages.restore', $page->id) }}" class="d-inline ms-2">@csrf
            <button class="btn btn-sm btn-primary" type="submit">Restore</button>
        </form>
    </div>
@endif

<form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page->id) : route('admin.pages.store') }}">
    @csrf
    @if ($page->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="panel mb-3">
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input name="title" class="form-control" value="{{ old('title', $page->title) }}" required @disabled($page->trashed())>
                </div>
                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input name="slug" class="form-control" value="{{ old('slug', $page->slug) }}" @disabled($page->trashed())>
                    @error('slug')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Parent page</label>
                    <select name="parent_id" class="form-select" @disabled($page->trashed())>
                        <option value="">— None —</option>
                        @foreach (($parents ?? []) as $parent)
                            <option value="{{ $parent->id }}" @selected(old('parent_id', $page->parent_id) == $parent->id)>{{ $parent->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    @unless ($page->trashed())
                        <x-admin.html-editor name="content" :value="old('content', $page->content)" :rows="14" label="Content (HTML)" />
                    @else
                        <label class="form-label">Content</label>
                        <textarea class="form-control" rows="8" disabled>{{ $page->content }}</textarea>
                    @endunless
                </div>
                <div class="mb-0">
                    <label class="form-label">Excerpt</label>
                    <textarea name="excerpt" class="form-control" rows="3" @disabled($page->trashed())>{{ old('excerpt', $page->excerpt) }}</textarea>
                </div>
            </div>
            <div class="panel mb-3">
                <h2 class="h6 mb-3">SEO</h2>
                <x-admin.seo-fields :model="$page" :preview-url="$page->slug ? url('/'.$page->slug) : url('/')" />
            </div>
            @unless ($page->trashed())
                <x-admin.custom-code-fields :model="$page" />
            @endunless
        </div>
        <div class="col-lg-4">
            <div class="panel mb-3">
                <h2 class="h6 mb-3">Publish</h2>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" @disabled($page->trashed())>
                        @foreach (['draft'=>'Draft','publish'=>'Published','private'=>'Private'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $page->status ?: 'draft') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Publish date</label>
                    <input type="datetime-local" name="published_at" class="form-control" @disabled($page->trashed())
                           value="{{ old('published_at', optional($page->published_at)->format('Y-m-d\\TH:i')) }}">
                </div>
                <div class="d-grid gap-2">
                    @unless ($page->trashed())
                        <button class="btn btn-primary" type="submit">{{ $page->exists ? 'Update' : 'Publish / Save' }}</button>
                        @if ($page->exists)
                            <button class="btn btn-outline-danger" form="move-trash" type="submit">Move to Trash</button>
                        @endif
                    @endunless
                </div>
            </div>

            <div class="panel mb-3">
                <h2 class="h6 mb-3">Featured image</h2>
                <x-admin.media-picker name="featured_image_id" :value="old('featured_image_id', $page->featured_image_id)" />
            </div>

            <div class="panel mb-3">
                <h2 class="h6 mb-3">Template & layout</h2>
                <div class="mb-3">
                    <label class="form-label">Template</label>
                    <select name="template" class="form-select" @disabled($page->trashed())>
                        @foreach ($templates as $value => $label)
                            <option value="{{ $value }}" @selected(old('template', $page->template ?: 'default') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Header</label>
                    <select name="header_mode" class="form-select mb-2" @disabled($page->trashed())>
                        <option value="master" @selected(old('header_mode', $page->header_mode ?: 'master') === 'master')>Use Master Header</option>
                        <option value="custom" @selected(old('header_mode', $page->header_mode) === 'custom')>Custom Header</option>
                        <option value="disable" @selected(old('header_mode', $page->header_mode) === 'disable')>Disable Header</option>
                    </select>
                    <select name="header_id" class="form-select" @disabled($page->trashed())>
                        <option value="">—</option>
                        @foreach ($headers as $header)
                            <option value="{{ $header->id }}" @selected(old('header_id', $page->header_id) == $header->id)>{{ $header->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Footer</label>
                    <select name="footer_mode" class="form-select mb-2" @disabled($page->trashed())>
                        <option value="master" @selected(old('footer_mode', $page->footer_mode ?: 'master') === 'master')>Use Master Footer</option>
                        <option value="custom" @selected(old('footer_mode', $page->footer_mode) === 'custom')>Custom Footer</option>
                        <option value="disable" @selected(old('footer_mode', $page->footer_mode) === 'disable')>Disable Footer</option>
                    </select>
                    <select name="footer_id" class="form-select" @disabled($page->trashed())>
                        <option value="">—</option>
                        @foreach ($footers as $footer)
                            <option value="{{ $footer->id }}" @selected(old('footer_id', $page->footer_id) == $footer->id)>{{ $footer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Sidebar</label>
                    <select name="sidebar_position" class="form-select" @disabled($page->trashed())>
                        <option value="">Inherit</option>
                        @foreach (['none','left','right'] as $pos)
                            <option value="{{ $pos }}" @selected(old('sidebar_position', $page->sidebar_position) === $pos)>{{ ucfirst($pos) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if (!empty($revisions) && $revisions->count())
                <div class="panel">
                    <h2 class="h6 mb-2">Revisions</h2>
                    @foreach ($revisions as $rev)
                        <div class="small py-2 border-bottom">{{ $rev->note }} · {{ $rev->created_at?->diffForHumans() }}</div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</form>

@if ($page->exists && ! $page->trashed())
    <form id="move-trash" method="POST" action="{{ route('admin.pages.destroy', $page->id) }}" class="d-none">@csrf @method('DELETE')</form>
@endif
@endsection

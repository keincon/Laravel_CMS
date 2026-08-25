@extends('layouts.admin')
@section('title', $page->exists ? 'Edit Page' : 'New Page')
@section('content')
<h1 class="h3 mb-3">{{ $page->exists ? 'Edit Static Page' : 'New Static Page' }}</h1>
<p class="text-muted mb-3">
    Type: <strong>Static Page</strong> — manually authored content.
    @if (! empty($reservedSlugs))
        Reserved system slugs: <code>{{ implode(', ', $reservedSlugs) }}</code>
    @endif
</p>
<form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}">
    @csrf
    @if ($page->exists) @method('PUT') @endif
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="mb-3"><label class="form-label">Title</label><input name="title" class="form-control" value="{{ old('title', $page->title) }}" required></div>
            <div class="mb-3"><label class="form-label">Slug</label><input name="slug" class="form-control" value="{{ old('slug', $page->slug) }}">@error('slug')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="mb-3">
                <label class="form-label">Parent page</label>
                <select name="parent_id" class="form-select">
                    <option value="">— None —</option>
                    @foreach (($parents ?? []) as $parent)
                        <option value="{{ $parent->id }}" @selected(old('parent_id', $page->parent_id) == $parent->id)>{{ $parent->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3"><label class="form-label">Excerpt</label><textarea name="excerpt" class="form-control" rows="2">{{ old('excerpt', $page->excerpt) }}</textarea></div>
            <div class="mb-3"><label class="form-label">Content</label><textarea name="content" class="form-control" rows="12">{{ old('content', $page->content) }}</textarea></div>
            <x-admin.seo-fields :model="$page" :preview-url="$page->slug ? url('/'.$page->slug) : url('/')" />
        </div>
        <div class="col-lg-4">
            <div class="border rounded p-3 bg-white mb-3">
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach (['draft','publish','private'] as $st)
                            <option value="{{ $st }}" @selected(old('status', $page->status) === $st)>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Template</label>
                    <select name="template" class="form-select">
                        @foreach ($templates as $value => $label)
                            <option value="{{ $value }}" @selected(old('template', $page->template ?: 'default') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Header</label>
                    <select name="header_mode" class="form-select mb-2">
                        <option value="master" @selected(old('header_mode', $page->header_mode ?: 'master') === 'master')>Use Master Header</option>
                        <option value="custom" @selected(old('header_mode', $page->header_mode) === 'custom')>Custom Header</option>
                        <option value="disable" @selected(old('header_mode', $page->header_mode) === 'disable')>Disable Header</option>
                    </select>
                    <select name="header_id" class="form-select">
                        <option value="">—</option>
                        @foreach ($headers as $header)
                            <option value="{{ $header->id }}" @selected(old('header_id', $page->header_id) == $header->id)>{{ $header->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Footer</label>
                    <select name="footer_mode" class="form-select mb-2">
                        <option value="master" @selected(old('footer_mode', $page->footer_mode ?: 'master') === 'master')>Use Master Footer</option>
                        <option value="custom" @selected(old('footer_mode', $page->footer_mode) === 'custom')>Custom Footer</option>
                        <option value="disable" @selected(old('footer_mode', $page->footer_mode) === 'disable')>Disable Footer</option>
                    </select>
                    <select name="footer_id" class="form-select">
                        <option value="">—</option>
                        @foreach ($footers as $footer)
                            <option value="{{ $footer->id }}" @selected(old('footer_id', $page->footer_id) == $footer->id)>{{ $footer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Sidebar</label>
                    <select name="sidebar_position" class="form-select">
                        <option value="">Inherit</option>
                        @foreach (['none','left','right'] as $pos)
                            <option value="{{ $pos }}" @selected(old('sidebar_position', $page->sidebar_position) === $pos)>{{ ucfirst($pos) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" type="submit">{{ $page->exists ? 'Update' : 'Create' }}</button>
                    @if ($page->exists)
                        <a class="btn btn-outline-secondary" target="_blank" href="{{ route('admin.pages.preview', $page) }}">Preview</a>
                    @endif
                </div>
            </div>
            @if (!empty($revisions) && $revisions->count())
                <div class="border rounded p-3 bg-white">
                    <h2 class="h6">Revisions</h2>
                    <ul class="small mb-0">
                        @foreach ($revisions as $rev)
                            <li>{{ $rev->created_at?->diffForHumans() }} — {{ $rev->note }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</form>
@endsection

@extends('layouts.admin')
@section('title', 'SEO Templates')
@section('content')
<h1 class="h3 mb-2">SEO Templates</h1>
<p class="text-muted mb-4">
    These templates generate titles and descriptions for dynamic pages (and fill in when Static Pages / Posts have no manual SEO).
</p>

<div class="alert alert-secondary">
    <strong>Available variables:</strong>
    <code>{site_name}</code>,
    <code>{post_title}</code>,
    <code>{post_excerpt}</code>,
    <code>{page_title}</code>,
    <code>{page_excerpt}</code>,
    <code>{category_name}</code>,
    <code>{tag_name}</code>,
    <code>{author_name}</code>,
    <code>{search_query}</code>,
    <code>{archive_label}</code>
</div>

<form method="POST" action="{{ route('admin.settings.seo.templates.update') }}">
    @csrf @method('PUT')
    @foreach ($templates as $type => $template)
        <div class="border rounded p-3 bg-white mb-3">
            <h2 class="h6 text-uppercase">{{ str_replace('_', ' ', $type) }}</h2>
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input class="form-control" name="templates[{{ $type }}][title]" value="{{ old("templates.$type.title", $template['title'] ?? '') }}">
            </div>
            <div class="mb-0">
                <label class="form-label">Description</label>
                <textarea class="form-control" rows="2" name="templates[{{ $type }}][description]">{{ old("templates.$type.description", $template['description'] ?? '') }}</textarea>
            </div>
        </div>
    @endforeach
    <button class="btn btn-primary" type="submit">Save SEO Templates</button>
</form>
@endsection

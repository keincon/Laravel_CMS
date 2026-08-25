@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<h1 class="h3 mb-3">Dashboard</h1>
<p class="text-muted mb-4">Static Pages are content. Dynamic Pages are systems. Both share Master Header, Footer, Theme, and SEO.</p>

<div class="row g-3">
    <div class="col-md-4">
        <div class="p-3 border rounded bg-white h-100">
            <h2 class="h6">Static Pages</h2>
            <p class="small text-muted">Manually authored pages like About and Contact.</p>
            <a href="{{ route('admin.pages.index') }}">Manage pages →</a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-3 border rounded bg-white h-100">
            <h2 class="h6">Dynamic Pages</h2>
            <p class="small text-muted">Blog, category, tag, search, archives, 404.</p>
            <a href="{{ route('admin.appearance.dynamic-pages.index') }}">Configure systems →</a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-3 border rounded bg-white h-100">
            <h2 class="h6">Posts & Taxonomy</h2>
            <p class="small text-muted">Articles, categories, and tags.</p>
            <a href="{{ route('admin.posts.index') }}">Posts</a> ·
            <a href="{{ route('admin.categories.index') }}">Categories</a> ·
            <a href="{{ route('admin.tags.index') }}">Tags</a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-3 border rounded bg-white h-100">
            <h2 class="h6">Header</h2>
            <p class="small text-muted">Logo, navigation, search, CTA.</p>
            <a href="{{ route('admin.headers.index') }}">Manage headers →</a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-3 border rounded bg-white h-100">
            <h2 class="h6">Footer</h2>
            <p class="small text-muted">Columns, menus, copyright.</p>
            <a href="{{ route('admin.footers.index') }}">Manage footers →</a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-3 border rounded bg-white h-100">
            <h2 class="h6">Reading & SEO</h2>
            <p class="small text-muted">Homepage mode and SEO templates.</p>
            <a href="{{ route('admin.settings.reading') }}">Reading</a> ·
            <a href="{{ route('admin.settings.seo.templates') }}">SEO Templates</a>
        </div>
    </div>
</div>
@endsection

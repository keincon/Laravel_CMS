@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<h1 class="h3 mb-3">Dashboard</h1>
<p class="text-muted mb-4">Build the header and footer once — pages and posts inherit the master layout automatically.</p>

<div class="row g-3">
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
            <h2 class="h6">Content</h2>
            <p class="small text-muted">Pages and posts with SEO & templates.</p>
            <a href="{{ route('admin.pages.index') }}">Pages</a> ·
            <a href="{{ route('admin.posts.index') }}">Posts</a>
        </div>
    </div>
</div>
@endsection

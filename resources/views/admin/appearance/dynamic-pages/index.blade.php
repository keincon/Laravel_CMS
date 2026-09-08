@extends('layouts.admin')
@section('title', __('admin.nav.dynamic_pages'))
@section('content')
<h1 class="h3 mb-2">{{ __('admin.nav.dynamic_pages') }}</h1>
<p class="text-muted mb-4">
    These are <strong>system templates</strong>, not Static Pages. The CMS generates their content from data
    (posts, categories, search queries, etc.).
</p>

<div class="table-responsive bg-white border rounded">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>Type</th>
                <th>Title</th>
                <th>URL</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @foreach ($pages as $page)
            @php $def = $definitions[$page->type] ?? []; @endphp
            <tr>
                <td>
                    <span class="badge text-bg-light border">Dynamic</span>
                    {{ $page->label ?: ($def['label'] ?? $page->type) }}
                </td>
                <td>{{ $page->title }}</td>
                <td class="text-muted">{{ $page->url_path ?: '—' }}</td>
                <td>
                    @if ($page->is_enabled)
                        <span class="text-success">Enabled</span>
                    @else
                        <span class="text-danger">Disabled</span>
                    @endif
                </td>
                <td class="text-end">
                    <a href="{{ route('admin.appearance.dynamic-pages.edit', $page->type) }}">Configure</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="alert alert-info mt-4 mb-0">
    <strong>Static Pages</strong> are managed under
    <a href="{{ route('admin.pages.index') }}">Pages</a>.
    Do not create fake static pages for Blog, Category, Tag, Search, or Archives.
</div>
@endsection

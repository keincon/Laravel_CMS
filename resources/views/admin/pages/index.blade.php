@extends('layouts.admin')
@section('title', 'Pages')
@section('content')
<div class="d-flex justify-content-between align-items-start mb-3 gap-3 flex-wrap">
    <div>
        <h1 class="h3 mb-1">Pages</h1>
        <p class="text-muted mb-0">Static Pages are manually authored content. Dynamic systems live under Appearance → Dynamic Pages.</p>
    </div>
    <a class="btn btn-primary cms-btn cms-btn-primary" href="{{ route('admin.pages.create') }}">Add Static Page</a>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="border rounded bg-white">
            <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                <strong>Static Pages</strong>
                <span class="badge text-bg-primary">Content</span>
            </div>
            <table class="table mb-0">
                <thead><tr><th>Title</th><th>Slug</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($pages as $page)
                    <tr>
                        <td>
                            @if ($page->parent_id)
                                <span class="text-muted ms-3">↳</span>
                            @endif
                            {{ $page->title }}
                        </td>
                        <td>/{{ $page->slug }}</td>
                        <td>{{ $page->status }}</td>
                        <td class="text-end"><a href="{{ route('admin.pages.edit', $page) }}">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">No static pages yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="border rounded bg-white">
            <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                <strong>Dynamic Pages</strong>
                <span class="badge text-bg-secondary">Systems</span>
            </div>
            <ul class="list-group list-group-flush">
                @foreach ($dynamicPages as $dyn)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            {{ $dyn->label }}
                            <div class="small text-muted">{{ $dyn->url_path ?: 'System route' }}</div>
                        </span>
                        <a href="{{ route('admin.appearance.dynamic-pages.edit', $dyn->type) }}">Configure</a>
                    </li>
                @endforeach
            </ul>
            <div class="p-3 border-top">
                <a href="{{ route('admin.appearance.dynamic-pages.index') }}">Manage all Dynamic Pages →</a>
            </div>
        </div>
    </div>
</div>
@endsection

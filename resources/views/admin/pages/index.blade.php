@extends('layouts.admin')

@section('title', 'Pages')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Pages</h1>
        <p class="page-intro mb-0">Static pages you author. Dynamic systems stay under Appearance → Dynamic Pages.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('admin.pages.create') }}">Add New</a>
</div>

<div class="users-role-tabs mb-3">
    @foreach (['all'=>'All','publish'=>'Published','draft'=>'Draft','private'=>'Private','trash'=>'Trash'] as $key => $label)
        <a href="{{ route('admin.pages.index', array_filter(['status' => $key === 'all' ? null : $key, 'q' => $q ?: null])) }}"
           class="{{ $status === $key ? 'is-active' : '' }}">
            {{ $label }} <span>({{ $counts[$key] ?? 0 }})</span>
        </a>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="panel mb-3">
            <form method="GET" class="d-flex gap-2 flex-wrap">
                @if ($status !== 'all')<input type="hidden" name="status" value="{{ $status }}">@endif
                <input type="search" name="q" value="{{ $q }}" class="form-control" style="max-width:280px" placeholder="Search pages">
                <button class="btn btn-outline-secondary" type="submit">Search Pages</button>
            </form>
        </div>

        <form method="POST" action="{{ route('admin.pages.bulk') }}">
            @csrf
            <div class="panel">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <select name="action" class="form-select" style="max-width:200px" required>
                        <option value="">Bulk actions</option>
                        @if ($status === 'trash')
                            <option value="restore">Restore</option>
                            <option value="delete">Delete permanently</option>
                        @else
                            <option value="publish">Publish</option>
                            <option value="draft">Move to Draft</option>
                            <option value="trash">Move to Trash</option>
                        @endif
                    </select>
                    <button class="btn btn-outline-secondary" type="submit">Apply</button>
                </div>

                @if ($pages->isEmpty())
                    <div class="empty-state">No pages found. <a href="{{ route('admin.pages.create') }}">Add a page</a>.</div>
                @else
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th style="width:2rem"><input type="checkbox" onclick="document.querySelectorAll('.page-check').forEach(c=>c.checked=this.checked)"></th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pages as $page)
                                    <tr>
                                        <td><input class="page-check" type="checkbox" name="pages[]" value="{{ $page->id }}"></td>
                                        <td>
                                            @if ($page->parent_id && ! $page->trashed())
                                                <span class="page-intro">↳ </span>
                                            @endif
                                            <a class="fw-semibold" href="{{ route('admin.pages.edit', $page->id) }}">{{ $page->title }}</a>
                                            <div class="row-actions small mt-1">
                                                @if ($page->trashed())
                                                    <button form="restore-page-{{ $page->id }}" type="submit">Restore</button> ·
                                                    <button form="delete-page-{{ $page->id }}" class="link-danger" type="submit" onclick="return confirm('Delete permanently?')">Delete Permanently</button>
                                                @else
                                                    <a href="{{ route('admin.pages.edit', $page) }}">Edit</a> ·
                                                    <a href="{{ route('admin.pages.preview', $page) }}" target="_blank">Preview</a> ·
                                                    <button form="delete-page-{{ $page->id }}" class="link-danger" type="submit">Trash</button>
                                                @endif
                                            </div>
                                        </td>
                                        <td>{{ $page->author?->name ?: '—' }}</td>
                                        <td><span class="badge text-bg-secondary">{{ $page->trashed() ? 'trash' : $page->status }}</span></td>
                                        <td>{{ optional($page->published_at ?? $page->updated_at)->format('Y-m-d') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $pages->links() }}</div>
                @endif
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        <div class="panel">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 mb-0">Dynamic Pages</h2>
                <span class="badge text-bg-secondary">Systems</span>
            </div>
            <ul class="mb-0" style="list-style:none;padding:0">
                @foreach ($dynamicPages as $dyn)
                    <li class="py-2 border-bottom d-flex justify-content-between gap-2">
                        <span>
                            <strong>{{ $dyn->label }}</strong>
                            <div class="small page-intro">{{ $dyn->url_path ?: 'System route' }}</div>
                        </span>
                        <a href="{{ route('admin.appearance.dynamic-pages.edit', $dyn->type) }}">Configure</a>
                    </li>
                @endforeach
            </ul>
            <div class="mt-3"><a href="{{ route('admin.appearance.dynamic-pages.index') }}">Manage all →</a></div>
        </div>
    </div>
</div>

@foreach ($pages as $page)
    @if ($page->trashed())
        <form id="restore-page-{{ $page->id }}" method="POST" action="{{ route('admin.pages.restore', $page->id) }}" class="d-none">@csrf</form>
    @endif
    <form id="delete-page-{{ $page->id }}" method="POST" action="{{ route('admin.pages.destroy', $page->id) }}" class="d-none">@csrf @method('DELETE')</form>
@endforeach
@endsection

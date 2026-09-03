@extends('layouts.admin')

@section('title', 'Posts')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Posts</h1>
        <p class="page-intro mb-0">Create and manage blog posts.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('admin.posts.create') }}">Add New</a>
</div>

<div class="users-role-tabs mb-3">
    @foreach ([
        'all' => 'All',
        'publish' => 'Published',
        'draft' => 'Draft',
        'pending' => 'Pending',
        'private' => 'Private',
        'trash' => 'Trash',
    ] as $key => $label)
        <a href="{{ route('admin.posts.index', array_filter(['status' => $key === 'all' ? null : $key, 'q' => $q ?: null])) }}"
           class="{{ $status === $key ? 'is-active' : '' }}">
            {{ $label }} <span>({{ $counts[$key] ?? 0 }})</span>
        </a>
    @endforeach
</div>

<div class="panel mb-3">
    <form method="GET" action="{{ route('admin.posts.index') }}" class="d-flex gap-2 flex-wrap align-items-center">
        @if ($status !== 'all')
            <input type="hidden" name="status" value="{{ $status }}">
        @endif
        <input type="search" name="q" value="{{ $q }}" class="form-control" style="max-width:280px" placeholder="Search posts">
        <button class="btn btn-outline-secondary" type="submit">Search Posts</button>
    </form>
</div>

<form method="POST" action="{{ route('admin.posts.bulk') }}">
    @csrf
    <div class="panel">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
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

        @if ($posts->isEmpty())
            <div class="empty-state">No posts found. <a href="{{ route('admin.posts.create') }}">Write your first post</a>.</div>
        @else
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th style="width:2rem"><input type="checkbox" onclick="document.querySelectorAll('.post-check').forEach(c=>c.checked=this.checked)"></th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Categories</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($posts as $post)
                            <tr>
                                <td><input class="post-check" type="checkbox" name="posts[]" value="{{ $post->id }}"></td>
                                <td>
                                    <a class="fw-semibold" href="{{ route('admin.posts.edit', $post) }}">{{ $post->title }}</a>
                                    @if ($post->is_sticky)
                                        <span class="badge text-bg-primary">Sticky</span>
                                    @endif
                                    <div class="row-actions small mt-1">
                                        <a href="{{ route('admin.posts.edit', $post) }}">Edit</a>
                                        ·
                                        <a href="{{ route('admin.posts.preview', $post) }}" target="_blank">Preview</a>
                                        ·
                                        <button form="dup-post-{{ $post->id }}" type="submit">Duplicate</button>
                                        ·
                                        <button form="trash-post-{{ $post->id }}" class="link-danger" type="submit"
                                            onclick="return confirm('{{ $post->status === 'trash' ? 'Delete permanently?' : 'Move to Trash?' }}')">
                                            {{ $post->status === 'trash' ? 'Delete Permanently' : 'Trash' }}
                                        </button>
                                    </div>
                                </td>
                                <td>{{ $post->author?->name }}</td>
                                <td>{{ $post->categories->pluck('name')->join(', ') ?: '—' }}</td>
                                <td><span class="badge text-bg-secondary">{{ $post->status }}</span></td>
                                <td>{{ optional($post->published_at ?? $post->updated_at)->format('Y-m-d') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $posts->links() }}</div>
        @endif
    </div>
</form>

@foreach ($posts as $post)
    <form id="trash-post-{{ $post->id }}" method="POST" action="{{ route('admin.posts.destroy', $post) }}" class="d-none">
        @csrf
        @method('DELETE')
    </form>
@endforeach
@endsection

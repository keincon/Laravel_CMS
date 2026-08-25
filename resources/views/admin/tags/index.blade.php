@extends('layouts.admin')
@section('title', 'Tags')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <div>
        <h1 class="h3 mb-1">Tags</h1>
        <p class="text-muted mb-0">Used by the Dynamic Tag Archive (<code>/tag/{slug}</code>).</p>
    </div>
    <a class="btn btn-primary cms-btn cms-btn-primary" href="{{ route('admin.tags.create') }}">Add Tag</a>
</div>
<table class="table bg-white">
    <thead><tr><th>Name</th><th>Slug</th><th>Posts</th><th></th></tr></thead>
    <tbody>
    @forelse ($tags as $tag)
        <tr>
            <td>{{ $tag->name }}</td>
            <td>/tag/{{ $tag->slug }}</td>
            <td>{{ $tag->posts_count }}</td>
            <td class="text-end"><a href="{{ route('admin.tags.edit', $tag) }}">Edit</a></td>
        </tr>
    @empty
        <tr><td colspan="4" class="text-muted">No tags yet.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $tags->links() }}
@endsection

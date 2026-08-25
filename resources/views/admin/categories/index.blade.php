@extends('layouts.admin')
@section('title', 'Categories')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <div>
        <h1 class="h3 mb-1">Categories</h1>
        <p class="text-muted mb-0">Used by the Dynamic Category Archive (<code>/category/{slug}</code>).</p>
    </div>
    <a class="btn btn-primary cms-btn cms-btn-primary" href="{{ route('admin.categories.create') }}">Add Category</a>
</div>
<table class="table bg-white">
    <thead><tr><th>Name</th><th>Slug</th><th>Parent</th><th>Posts</th><th></th></tr></thead>
    <tbody>
    @forelse ($categories as $category)
        <tr>
            <td>{{ $category->name }}</td>
            <td>/category/{{ $category->slug }}</td>
            <td>{{ $category->parent?->name ?: '—' }}</td>
            <td>{{ $category->posts_count }}</td>
            <td class="text-end">
                <a href="{{ route('admin.categories.edit', $category) }}">Edit</a>
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="text-muted">No categories yet.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $categories->links() }}
@endsection

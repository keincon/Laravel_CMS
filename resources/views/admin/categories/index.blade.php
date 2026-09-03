@extends('layouts.admin')

@section('title', 'Categories')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Categories</h1>
        <p class="page-intro mb-0">Organize posts. Archive URLs: <code>/category/{slug}</code></p>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel">
            <h2 class="h6 mb-3">Add New Category</h2>
            <form method="POST" action="{{ route('admin.categories.store') }}">
                @csrf
                <div class="mb-2">
                    <label class="form-label">Name</label>
                    <input name="name" class="form-control" value="{{ old('name') }}" required>
                    @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="mb-2">
                    <label class="form-label">Slug</label>
                    <input name="slug" class="form-control" value="{{ old('slug') }}" placeholder="optional">
                </div>
                <div class="mb-2">
                    <label class="form-label">Parent</label>
                    <select name="parent_id" class="form-select">
                        <option value="">— None —</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->id }}" @selected(old('parent_id') == $parent->id)>{{ $parent->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                </div>
                <button class="btn btn-primary" type="submit">Add New Category</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Slug</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td>
                                @if ($category->parent_id)<span class="page-intro">↳ </span>@endif
                                <a class="fw-semibold" href="{{ route('admin.categories.edit', $category) }}">{{ $category->name }}</a>
                                <div class="row-actions small mt-1">
                                    <a href="{{ route('admin.categories.edit', $category) }}">Edit</a>
                                    @if ($category->slug !== 'uncategorized')
                                        · <button form="del-cat-{{ $category->id }}" class="link-danger" type="submit" onclick="return confirm('Delete category?')">Delete</button>
                                    @endif
                                    · <a href="{{ url('/category/'.$category->slug) }}" target="_blank">View</a>
                                </div>
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($category->description, 60) ?: '—' }}</td>
                            <td>{{ $category->slug }}</td>
                            <td>{{ $category->posts_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="empty-state">No categories yet.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-3">{{ $categories->links() }}</div>
        </div>
    </div>
</div>

@foreach ($categories as $category)
    @if ($category->slug !== 'uncategorized')
        <form id="del-cat-{{ $category->id }}" method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="d-none">@csrf @method('DELETE')</form>
    @endif
@endforeach
@endsection

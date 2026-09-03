@extends('layouts.admin')
@section('title', 'Menus')
@section('content')
<p class="text-muted mb-4">Menus power header and footer navigation.</p>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="panel">
            <h2 class="h6 mb-3">Create menu</h2>
            <form method="POST" action="{{ route('admin.menus.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input name="name" class="form-control" required value="{{ old('name') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input name="slug" class="form-control" value="{{ old('slug') }}" placeholder="auto from name">
                </div>
                <div class="mb-3">
                    <label class="form-label">Location</label>
                    <select name="location" class="form-select">
                        <option value="">—</option>
                        <option value="primary">Primary</option>
                        <option value="footer">Footer</option>
                        <option value="secondary">Secondary</option>
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">Create</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="panel">
            <h2 class="h6 mb-3">Existing menus</h2>
            @forelse ($menus as $menu)
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <a href="{{ route('admin.menus.edit', $menu) }}" class="fw-semibold">{{ $menu->name }}</a>
                        <div class="small text-muted">{{ $menu->slug }} · {{ $menu->location ?: 'no location' }} · {{ $menu->items_count }} items</div>
                    </div>
                    <a href="{{ route('admin.menus.edit', $menu) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                </div>
            @empty
                <div class="empty-state py-4">No menus yet. Create one to start building navigation.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection

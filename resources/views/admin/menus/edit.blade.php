@extends('layouts.admin')
@section('title', 'Edit Menu')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('admin.menus.index') }}" class="text-muted">← Menus</a>
    <form method="POST" action="{{ route('admin.menus.destroy', $menu) }}" onsubmit="return confirm('Delete this menu?')">
        @csrf @method('DELETE')
        <button class="btn btn-sm btn-outline-danger" type="submit">Delete menu</button>
    </form>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel mb-3">
            <h2 class="h6 mb-3">Menu settings</h2>
            <form method="POST" action="{{ route('admin.menus.update', $menu) }}">
                @csrf @method('PUT')
                <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $menu->name) }}" required></div>
                <div class="mb-3"><label class="form-label">Slug</label><input name="slug" class="form-control" value="{{ old('slug', $menu->slug) }}" required></div>
                <div class="mb-3">
                    <label class="form-label">Location</label>
                    <select name="location" class="form-select">
                        @foreach (['' => '—', 'primary' => 'Primary', 'footer' => 'Footer', 'secondary' => 'Secondary'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('location', $menu->location) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">Save</button>
            </form>
        </div>
        <div class="panel">
            <h2 class="h6 mb-3">Add item</h2>
            <form method="POST" action="{{ route('admin.menus.items.store', $menu) }}">
                @csrf
                <div class="mb-3"><label class="form-label">Title</label><input name="title" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Custom URL</label><input name="url" class="form-control" placeholder="/about or https://…"></div>
                <div class="mb-3">
                    <label class="form-label">Or page</label>
                    <select name="page_id" class="form-select">
                        <option value="">—</option>
                        @foreach ($pages as $page)
                            <option value="{{ $page->id }}">{{ $page->title }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-outline-primary w-100" type="submit">Add item</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel">
            <h2 class="h6 mb-3">Items</h2>
            @forelse ($menu->items as $item)
                <div class="border rounded p-3 mb-2 bg-white">
                    <form method="POST" action="{{ route('admin.menus.items.update', [$menu, $item]) }}">
                        @csrf @method('PUT')
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3"><label class="form-label">Title</label><input name="title" class="form-control form-control-sm" value="{{ $item->title }}" required></div>
                            <div class="col-md-3"><label class="form-label">URL</label><input name="url" class="form-control form-control-sm" value="{{ $item->url }}"></div>
                            <div class="col-md-3">
                                <label class="form-label">Page</label>
                                <select name="page_id" class="form-select form-select-sm">
                                    <option value="">—</option>
                                    @foreach ($pages as $page)
                                        <option value="{{ $page->id }}" @selected($item->page_id == $page->id)>{{ $page->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-1"><label class="form-label">Order</label><input type="number" name="sort_order" class="form-control form-control-sm" value="{{ $item->sort_order }}"></div>
                            <div class="col-md-2"><button class="btn btn-sm btn-primary w-100" type="submit">Save</button></div>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('admin.menus.items.destroy', [$menu, $item]) }}" class="mt-2" onsubmit="return confirm('Delete item?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete item</button>
                    </form>
                    @foreach ($item->children as $child)
                        <div class="small text-muted mt-2 ms-3">↳ {{ $child->title }}</div>
                    @endforeach
                </div>
            @empty
                <div class="empty-state py-4">No items yet. Add a link or page on the left.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection

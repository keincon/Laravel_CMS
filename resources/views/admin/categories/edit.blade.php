@extends('layouts.admin')
@section('title', $category->exists ? __('admin.categories.edit') : __('admin.categories.new'))
@section('content')
<h1 class="h3 mb-3">{{ $category->exists ? __('admin.categories.edit') : __('admin.categories.new') }}</h1>
<form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
    @csrf
    @if ($category->exists) @method('PUT') @endif
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="mb-3">
                <label class="form-label">{{ __('admin.ui.name') }}</label>
                <input name="name" class="form-control" value="{{ old('name', $category->name) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('admin.ui.slug') }}</label>
                <input name="slug" class="form-control" value="{{ old('slug', $category->slug) }}">
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4">{{ old('description', $category->description) }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Parent</label>
                <select name="parent_id" class="form-select">
                    <option value="">— None —</option>
                    @foreach ($parents as $parent)
                        <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id) == $parent->id)>{{ $parent->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary cms-btn cms-btn-primary" type="submit">Save Category</button>
        </div>
    </div>
</form>
@if ($category->exists && $category->slug !== 'uncategorized')
    <form class="mt-3" method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm(@js(__('admin.categories.confirm_delete_this')))">
        @csrf @method('DELETE')
        <button class="btn btn-outline-danger" type="submit">{{ __('admin.categories.delete_category') }}</button>
    </form>
@endif
@endsection

@extends('layouts.admin')
@section('title', $category->exists ? __('admin.categories.edit') : __('admin.categories.new'))
@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">{{ $category->exists ? __('admin.categories.edit') : __('admin.categories.new') }}</h1>
        @if ($category->exists)
            <p class="page-intro mb-0">{{ __('admin.categories.edit_intro', ['name' => $category->name]) }}</p>
        @else
            <p class="page-intro mb-0">{!! __('admin.categories.intro') !!}</p>
        @endif
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.categories.index') }}">{{ __('admin.categories.back_list') }}</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="panel">
    @csrf
    @if ($category->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="mb-3">
                <label class="form-label" for="category_name">{{ __('admin.ui.name') }}</label>
                <input id="category_name" name="name" class="form-control" value="{{ old('name', $category->name) }}" required>
                @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="category_slug">{{ __('admin.ui.slug') }}</label>
                <input id="category_slug" name="slug" class="form-control" value="{{ old('slug', $category->slug) }}" placeholder="{{ __('admin.ui.optional') }}">
                <div class="form-text">{{ __('admin.categories.slug_help') }}</div>
                @error('slug')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="category_description">{{ __('admin.categories.description') }}</label>
                <textarea id="category_description" name="description" class="form-control" rows="4">{{ old('description', $category->description) }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label" for="category_parent">{{ __('admin.categories.parent') }}</label>
                <select id="category_parent" name="parent_id" class="form-select">
                    <option value="">{{ __('admin.categories.none_parent') }}</option>
                    @foreach ($parents as $parent)
                        <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id) == $parent->id)>{{ $parent->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-primary" type="submit">{{ $category->exists ? __('admin.categories.save') : __('admin.categories.create') }}</button>
                <a class="btn btn-outline-secondary" href="{{ route('admin.categories.index') }}">{{ __('common.cancel') }}</a>
                @if ($category->exists && $category->slug)
                    <a class="btn btn-outline-secondary" href="{{ url('/category/'.$category->slug) }}" target="_blank" rel="noopener">{{ __('admin.categories.view_archive') }}</a>
                @endif
            </div>
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

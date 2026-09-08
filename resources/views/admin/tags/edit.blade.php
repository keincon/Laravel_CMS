@extends('layouts.admin')
@section('title', $tag->exists ? __('admin.tags.edit') : __('admin.tags.new'))
@section('content')
<h1 class="h3 mb-3">{{ $tag->exists ? __('admin.tags.edit') : __('admin.tags.new') }}</h1>
<form method="POST" action="{{ $tag->exists ? route('admin.tags.update', $tag) : route('admin.tags.store') }}">
    @csrf
    @if ($tag->exists) @method('PUT') @endif
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="mb-3">
                <label class="form-label">{{ __('admin.ui.name') }}</label>
                <input name="name" class="form-control" value="{{ old('name', $tag->name) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('admin.ui.slug') }}</label>
                <input name="slug" class="form-control" value="{{ old('slug', $tag->slug) }}">
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4">{{ old('description', $tag->description) }}</textarea>
            </div>
            <button class="btn btn-primary cms-btn cms-btn-primary" type="submit">Save Tag</button>
        </div>
    </div>
</form>
@if ($tag->exists)
    <form class="mt-3" method="POST" action="{{ route('admin.tags.destroy', $tag) }}" onsubmit="return confirm(@js(__('admin.tags.confirm_delete_this')))">
        @csrf @method('DELETE')
        <button class="btn btn-outline-danger" type="submit">{{ __('admin.tags.delete_tag') }}</button>
    </form>
@endif
@endsection

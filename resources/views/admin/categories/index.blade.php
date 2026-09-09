@extends('layouts.admin')

@section('title', __('admin.nav.categories'))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">{{ __('admin.nav.categories') }}</h1>
        <p class="page-intro mb-0">{!! __('admin.categories.intro') !!}</p>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel">
            <h2 class="h6 mb-3">{{ __('admin.categories.add_new') }}</h2>
            <form method="POST" action="{{ route('admin.categories.store') }}">
                @csrf
                <div class="mb-2">
                    <label class="form-label">{{ __('admin.ui.name') }}</label>
                    <input name="name" class="form-control" value="{{ old('name') }}" required>
                    @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="mb-2">
                    <label class="form-label">{{ __('admin.ui.slug') }}</label>
                    <input name="slug" class="form-control" value="{{ old('slug') }}" placeholder="{{ __('admin.ui.optional') }}">
                </div>
                <div class="mb-2">
                    <label class="form-label">{{ __('admin.categories.parent') }}</label>
                    <select name="parent_id" class="form-select">
                        <option value="">{{ __('admin.categories.none_parent') }}</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->id }}" @selected(old('parent_id') == $parent->id)>{{ $parent->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('admin.categories.description') }}</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                </div>
                <button class="btn btn-primary" type="submit">{{ __('admin.categories.add_new') }}</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>{{ __('admin.ui.name') }}</th>
                        <th>{{ __('admin.categories.description') }}</th>
                        <th>{{ __('admin.ui.slug') }}</th>
                        <th>{{ __('admin.categories.count') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td>
                                @if ($category->parent_id)<span class="page-intro">↳ </span>@endif
                                <a class="fw-semibold" href="{{ route('admin.categories.edit', $category) }}">{{ $category->name }}</a>
                                <div class="row-actions small mt-1">
                                    <a href="{{ route('admin.categories.edit', $category) }}">{{ __('admin.ui.edit') }}</a>
                                    @if ($category->slug !== 'uncategorized')
                                        · <button form="del-cat-{{ $category->id }}" class="link-danger" type="submit" onclick="return confirm(@js(__('admin.categories.confirm_delete')))">{{ __('admin.ui.delete') }}</button>
                                    @endif
                                    · <a href="{{ url('/category/'.$category->slug) }}" target="_blank" rel="noopener">{{ __('admin.categories.view') }}</a>
                                </div>
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($category->description, 60) ?: '—' }}</td>
                            <td>{{ $category->slug }}</td>
                            <td>{{ $category->posts_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="empty-state">{{ __('admin.categories.empty') }}</div></td></tr>
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

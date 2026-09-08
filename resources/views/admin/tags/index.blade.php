@extends('layouts.admin')

@section('title', __('admin.nav.tags'))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">{{ __('admin.nav.tags') }}</h1>
        <p class="page-intro mb-0">{!! __('admin.tags.intro') !!}</p>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel">
            <h2 class="h6 mb-3">Add New Tag</h2>
            <form method="POST" action="{{ route('admin.tags.store') }}">
                @csrf
                <div class="mb-2">
                    <label class="form-label">{{ __('admin.ui.name') }}</label>
                    <input name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">{{ __('admin.ui.slug') }}</label>
                    <input name="slug" class="form-control" value="{{ old('slug') }}" placeholder="{{ __('admin.ui.optional') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                </div>
                <button class="btn btn-primary" type="submit">Add New Tag</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel">
            <table class="table mb-0">
                <thead>
                    <tr><th>{{ __('admin.ui.name') }}</th><th>Description</th><th>{{ __('admin.ui.slug') }}</th><th>Count</th></tr>
                </thead>
                <tbody>
                    @forelse ($tags as $tag)
                        <tr>
                            <td>
                                <a class="fw-semibold" href="{{ route('admin.tags.edit', $tag) }}">{{ $tag->name }}</a>
                                <div class="row-actions small mt-1">
                                    <a href="{{ route('admin.tags.edit', $tag) }}">{{ __('admin.ui.edit') }}</a>
                                    · <button form="del-tag-{{ $tag->id }}" class="link-danger" type="submit" onclick="return confirm(@js(__('admin.tags.confirm_delete')))">{{ __('admin.ui.delete') }}</button>
                                    · <a href="{{ url('/tag/'.$tag->slug) }}" target="_blank">View</a>
                                </div>
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($tag->description, 60) ?: '—' }}</td>
                            <td>{{ $tag->slug }}</td>
                            <td>{{ $tag->posts_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="empty-state">No tags yet.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-3">{{ $tags->links() }}</div>
        </div>
    </div>
</div>

@foreach ($tags as $tag)
    <form id="del-tag-{{ $tag->id }}" method="POST" action="{{ route('admin.tags.destroy', $tag) }}" class="d-none">@csrf @method('DELETE')</form>
@endforeach
@endsection

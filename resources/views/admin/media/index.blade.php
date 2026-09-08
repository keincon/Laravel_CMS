@extends('layouts.admin')
@section('title', __('admin.media.title'))
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <p class="text-muted mb-0">{{ __('admin.media.intro') }}</p>
    <form method="GET" class="d-flex gap-2">
        <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="{{ __('admin.media.search_placeholder') }}">
        <button class="btn btn-sm btn-outline-secondary" type="submit">{{ __('admin.media.search') }}</button>
    </form>
</div>

<div class="panel mb-4">
    <h2 class="h6 mb-3">{{ __('admin.media.upload') }}</h2>
    <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="row g-2 align-items-end">
        @csrf
        <div class="col-md-5">
            <label class="form-label">{{ __('admin.media.file') }}</label>
            <input type="file" name="file" class="form-control" required>
        </div>
        <div class="col-md-5">
            <label class="form-label">{{ __('admin.media.alt') }}</label>
            <input type="text" name="alt" class="form-control" value="{{ old('alt') }}">
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100" type="submit">{{ __('admin.media.upload') }}</button>
        </div>
    </form>
</div>

@if ($media->isEmpty())
    <div class="empty-state">
        <h2 class="h5 text-dark">{{ __('admin.media.empty') }}</h2>
        <p class="mb-0">{{ __('admin.media.empty_help') }}</p>
    </div>
@else
    <div class="media-grid">
        @foreach ($media as $item)
            <div class="media-card">
                @if (str_starts_with((string) $item->mime_type, 'image/'))
                    <img src="{{ $item->url() }}" alt="{{ $item->alt ?: $item->filename }}">
                @else
                    <div class="p-4 text-center text-muted small" style="height:120px;display:grid;place-items:center;background:#f8fafc">{{ $item->mime_type ?: 'file' }}</div>
                @endif
                <div class="meta">
                    <div class="fw-semibold text-truncate" title="{{ $item->filename }}">{{ $item->filename }}</div>
                    <div class="text-muted mb-2">{{ number_format($item->size / 1024, 1) }} KB</div>
                    <form method="POST" action="{{ route('admin.media.update', $item) }}" class="mb-2">
                        @csrf @method('PUT')
                        <input type="text" name="alt" class="form-control form-control-sm mb-1" value="{{ $item->alt }}" placeholder="{{ __('admin.media.alt') }}">
                        <button class="btn btn-sm btn-outline-secondary w-100" type="submit">{{ __('admin.media.save_alt') }}</button>
                    </form>
                    <form method="POST" action="{{ route('admin.media.destroy', $item) }}" onsubmit="return confirm(@js(__('admin.media.delete_confirm')))">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger w-100" type="submit">{{ __('admin.media.delete') }}</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
    <div class="mt-3">{{ $media->links() }}</div>
@endif
@endsection

@extends('layouts.admin')
@section('title', __('admin.nav.menus'))
@section('content')
<p class="text-muted mb-4">{{ __('admin.appearance.menus_intro') }}</p>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="panel">
            <h2 class="h6 mb-3">{{ __('admin.menus.create_menu') }}</h2>
            <form method="POST" action="{{ route('admin.menus.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">{{ __('admin.ui.name') }}</label>
                    <input name="name" class="form-control" required value="{{ old('name') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('admin.ui.slug') }}</label>
                    <input name="slug" class="form-control" value="{{ old('slug') }}" placeholder="{{ __('admin.menus.auto_slug') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('admin.ui.location') }}</label>
                    <select name="location" class="form-select">
                        <option value="">—</option>
                        <option value="primary">{{ __('admin.menus.primary') }}</option>
                        <option value="footer">{{ __('admin.menus.footer') }}</option>
                        <option value="secondary">{{ __('admin.menus.secondary') }}</option>
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">{{ __('admin.ui.create') }}</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="panel">
            <h2 class="h6 mb-3">{{ __('admin.menus.existing') }}</h2>
            @forelse ($menus as $menu)
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <a href="{{ route('admin.menus.edit', $menu) }}" class="fw-semibold">{{ $menu->name }}</a>
                        <div class="small text-muted">{{ $menu->slug }} · {{ $menu->location ? __('admin.menus.'.$menu->location) : __('admin.menus.no_location') }} · {{ __('admin.menus.items_count', ['count' => $menu->items_count]) }}</div>
                    </div>
                    <a href="{{ route('admin.menus.edit', $menu) }}" class="btn btn-sm btn-outline-secondary">{{ __('admin.ui.edit') }}</a>
                </div>
            @empty
                <div class="empty-state py-4">{{ __('admin.menus.empty') }}</div>
            @endforelse
        </div>
    </div>
</div>
@endsection

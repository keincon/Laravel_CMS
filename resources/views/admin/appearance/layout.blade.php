@extends('layouts.admin')
@section('title', __('admin.nav.master_layout'))
@section('content')
<h1 class="h3 mb-2">{{ __('admin.nav.master_layout') }}</h1>
<p class="text-muted mb-4">Global header/footer assignment and content widths.</p>
<form method="POST" action="{{ route('admin.appearance.layout.update') }}">
    @csrf @method('PUT')
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Default Header</label>
            <select name="default_header_id" class="form-select">
                @foreach ($headers as $header)
                    <option value="{{ $header->id }}" @selected($settings->default_header_id == $header->id)>{{ $header->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Default Footer</label>
            <select name="default_footer_id" class="form-select">
                @foreach ($footers as $footer)
                    <option value="{{ $footer->id }}" @selected($settings->default_footer_id == $footer->id)>{{ $footer->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4"><label class="form-label">Container Width (px)</label><input type="number" name="container_width" class="form-control" value="{{ old('container_width', $settings->container_width) }}"></div>
        <div class="col-md-4"><label class="form-label">Content Width (px)</label><input type="number" name="content_width" class="form-control" value="{{ old('content_width', $settings->content_width) }}"></div>
        <div class="col-md-4"><label class="form-label">Sidebar Width (px)</label><input type="number" name="sidebar_width" class="form-control" value="{{ old('sidebar_width', $settings->sidebar_width) }}"></div>
        <div class="col-md-4">
            <label class="form-label">Page Layout</label>
            <select name="page_layout" class="form-select">
                <option value="standard" @selected($settings->page_layout === 'standard')>Standard</option>
                <option value="full_width" @selected($settings->page_layout === 'full_width')>Full Width</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Post Layout</label>
            <select name="post_layout" class="form-select">
                <option value="standard" @selected($settings->post_layout === 'standard')>Standard</option>
                <option value="full_width" @selected($settings->post_layout === 'full_width')>Full Width</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Sidebar</label>
            <select name="sidebar_position" class="form-select">
                <option value="none" @selected($settings->sidebar_position === 'none')>No Sidebar</option>
                <option value="left" @selected($settings->sidebar_position === 'left')>Left Sidebar</option>
                <option value="right" @selected($settings->sidebar_position === 'right')>Right Sidebar</option>
            </select>
        </div>
    </div>
    <button class="btn btn-primary mt-3" type="submit">Save Master Layout</button>
</form>
@endsection

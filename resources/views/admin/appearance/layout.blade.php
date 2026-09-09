@extends('layouts.admin')
@section('title', __('admin.nav.master_layout'))
@section('content')
<h1 class="h3 mb-2">{{ __('admin.nav.master_layout') }}</h1>
<p class="text-muted mb-3">{{ __('admin.appearance.layout_intro') }}</p>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<x-admin.help-next context="layout" />

<form method="POST" action="{{ route('admin.appearance.layout.update') }}">
    @csrf @method('PUT')
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">{{ __('admin.appearance.layout_default_header') }}</label>
            <select name="default_header_id" class="form-select">
                @foreach ($headers as $header)
                    <option value="{{ $header->id }}" @selected($settings->default_header_id == $header->id)>{{ $header->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">{{ __('admin.appearance.layout_default_footer') }}</label>
            <select name="default_footer_id" class="form-select">
                @foreach ($footers as $footer)
                    <option value="{{ $footer->id }}" @selected($settings->default_footer_id == $footer->id)>{{ $footer->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">{{ __('admin.appearance.layout_container_width') }}</label>
            <input type="number" name="container_width" class="form-control" value="{{ old('container_width', $settings->container_width) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">{{ __('admin.appearance.layout_content_width') }}</label>
            <input type="number" name="content_width" class="form-control" value="{{ old('content_width', $settings->content_width) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">{{ __('admin.appearance.layout_sidebar_width') }}</label>
            <input type="number" name="sidebar_width" class="form-control" value="{{ old('sidebar_width', $settings->sidebar_width) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">{{ __('admin.appearance.layout_page_layout') }}</label>
            <select name="page_layout" class="form-select">
                <option value="standard" @selected($settings->page_layout === 'standard')>{{ __('admin.appearance.layout_standard') }}</option>
                <option value="full_width" @selected($settings->page_layout === 'full_width')>{{ __('admin.appearance.layout_full_width') }}</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">{{ __('admin.appearance.layout_post_layout') }}</label>
            <select name="post_layout" class="form-select">
                <option value="standard" @selected($settings->post_layout === 'standard')>{{ __('admin.appearance.layout_standard') }}</option>
                <option value="full_width" @selected($settings->post_layout === 'full_width')>{{ __('admin.appearance.layout_full_width') }}</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">{{ __('admin.appearance.layout_sidebar') }}</label>
            <select name="sidebar_position" class="form-select">
                <option value="none" @selected($settings->sidebar_position === 'none')>{{ __('admin.appearance.layout_sidebar_none') }}</option>
                <option value="left" @selected($settings->sidebar_position === 'left')>{{ __('admin.appearance.layout_sidebar_left') }}</option>
                <option value="right" @selected($settings->sidebar_position === 'right')>{{ __('admin.appearance.layout_sidebar_right') }}</option>
            </select>
        </div>
    </div>
    <button class="btn btn-primary mt-3" type="submit">{{ __('admin.appearance.layout_save') }}</button>
</form>
@endsection

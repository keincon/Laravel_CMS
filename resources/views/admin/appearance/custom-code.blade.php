@extends('layouts.admin')

@section('title', __('admin.nav.custom_code'))

@section('content')
<h1 class="h3 mb-2">{{ __('admin.nav.custom_code') }}</h1>
<p class="text-muted mb-3">
    {{ __('admin.appearance.custom_code_intro') }}
</p>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<x-admin.help-next context="custom_code" />

<form method="POST" action="{{ route('admin.appearance.custom-code.update') }}" class="panel" x-data="{ tab: 'css' }">
    @csrf
    @method('PUT')

    <div class="users-role-tabs mb-4">
        <a href="#" :class="{ 'is-active': tab === 'css' }" @click.prevent="tab='css'">{{ __('admin.appearance.custom_code_tab_css') }}</a>
        <a href="#" :class="{ 'is-active': tab === 'js-head' }" @click.prevent="tab='js-head'">{{ __('admin.appearance.custom_code_tab_js_head') }}</a>
        <a href="#" :class="{ 'is-active': tab === 'js-foot' }" @click.prevent="tab='js-foot'">{{ __('admin.appearance.custom_code_tab_js_foot') }}</a>
        <a href="#" :class="{ 'is-active': tab === 'html' }" @click.prevent="tab='html'">{{ __('admin.appearance.custom_code_tab_html') }}</a>
    </div>

    <div x-show="tab === 'css'">
        <label class="form-label">{{ __('admin.appearance.custom_code_css_label') }}</label>
        <p class="page-intro mb-2">{!! __('admin.appearance.custom_code_css_help_html') !!}</p>
        <textarea name="additional_css" class="code-editor" rows="18" spellcheck="false" placeholder="{{ __('admin.appearance.custom_code_css_placeholder') }}">{{ old('additional_css', $additionalCss) }}</textarea>
    </div>

    <div x-show="tab === 'js-head'" x-cloak>
        <label class="form-label">{!! __('admin.appearance.custom_code_js_head_label_html') !!}</label>
        <p class="page-intro mb-2">{!! __('admin.appearance.custom_code_js_head_help_html') !!}</p>
        <textarea name="header_scripts" class="code-editor" rows="18" spellcheck="false" placeholder="{{ __('admin.appearance.custom_code_js_head_placeholder') }}">{{ old('header_scripts', $headerScripts) }}</textarea>
    </div>

    <div x-show="tab === 'js-foot'" x-cloak>
        <label class="form-label">{!! __('admin.appearance.custom_code_js_foot_label_html') !!}</label>
        <p class="page-intro mb-2">{{ __('admin.appearance.custom_code_js_foot_help') }}</p>
        <textarea name="footer_scripts" class="code-editor" rows="18" spellcheck="false" placeholder="{{ __('admin.appearance.custom_code_js_foot_placeholder') }}">{{ old('footer_scripts', $footerScripts) }}</textarea>
    </div>

    <div x-show="tab === 'html'" x-cloak>
        <div class="mb-3">
            <label class="form-label">{!! __('admin.appearance.custom_code_html_head_label_html') !!}</label>
            <textarea name="custom_html_head" class="code-editor" rows="8" spellcheck="false" placeholder="{{ __('admin.appearance.custom_code_html_head_placeholder') }}">{{ old('custom_html_head', $customHtmlHead) }}</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">{!! __('admin.appearance.custom_code_html_body_open_label_html') !!}</label>
            <textarea name="custom_html_body_open" class="code-editor" rows="6" spellcheck="false" placeholder="{{ __('admin.appearance.custom_code_html_body_open_placeholder') }}">{{ old('custom_html_body_open', $customHtmlBodyOpen) }}</textarea>
        </div>
        <div>
            <label class="form-label">{!! __('admin.appearance.custom_code_html_body_close_label_html') !!}</label>
            <textarea name="custom_html_body_close" class="code-editor" rows="6" spellcheck="false">{{ old('custom_html_body_close', $customHtmlBodyClose) }}</textarea>
        </div>
    </div>

    <div class="form-actions mt-3">
        <button class="btn btn-primary" type="submit">{{ __('admin.appearance.custom_code_save') }}</button>
    </div>
</form>
@endsection

@extends('layouts.admin')

@section('title', __('admin.nav.custom_code'))

@section('content')
<p class="page-intro mb-4">
    {{ __('admin.appearance.custom_code_intro') }}
</p>

<form method="POST" action="{{ route('admin.appearance.custom-code.update') }}" class="panel" x-data="{ tab: 'css' }">
    @csrf
    @method('PUT')

    <div class="users-role-tabs mb-4">
        <a href="#" :class="{ 'is-active': tab === 'css' }" @click.prevent="tab='css'">Additional CSS</a>
        <a href="#" :class="{ 'is-active': tab === 'js-head' }" @click.prevent="tab='js-head'">Header JS</a>
        <a href="#" :class="{ 'is-active': tab === 'js-foot' }" @click.prevent="tab='js-foot'">Footer JS</a>
        <a href="#" :class="{ 'is-active': tab === 'html' }" @click.prevent="tab='html'">Custom HTML</a>
    </div>

    <div x-show="tab === 'css'">
        <label class="form-label">Additional CSS</label>
        <p class="page-intro mb-2">Loaded on every public page inside a <code>&lt;style&gt;</code> tag.</p>
        <textarea name="additional_css" class="code-editor" rows="18" spellcheck="false" placeholder="/* Example */&#10;.site-header { box-shadow: none; }">{{ old('additional_css', $additionalCss) }}</textarea>
    </div>

    <div x-show="tab === 'js-head'" x-cloak>
        <label class="form-label">Scripts in &lt;head&gt;</label>
        <p class="page-intro mb-2">Paste <code>&lt;script&gt;</code> tags or raw JS wrappers. Avoid untrusted third-party code.</p>
        <textarea name="header_scripts" class="code-editor" rows="18" spellcheck="false" placeholder="&lt;script&gt;&#10;  // analytics or early JS&#10;&lt;/script&gt;">{{ old('header_scripts', $headerScripts) }}</textarea>
    </div>

    <div x-show="tab === 'js-foot'" x-cloak>
        <label class="form-label">Scripts before &lt;/body&gt;</label>
        <p class="page-intro mb-2">Best place for most JavaScript.</p>
        <textarea name="footer_scripts" class="code-editor" rows="18" spellcheck="false" placeholder="&lt;script&gt;&#10;  document.addEventListener('DOMContentLoaded', () => {});&#10;&lt;/script&gt;">{{ old('footer_scripts', $footerScripts) }}</textarea>
    </div>

    <div x-show="tab === 'html'" x-cloak>
        <div class="mb-3">
            <label class="form-label">HTML in &lt;head&gt;</label>
            <textarea name="custom_html_head" class="code-editor" rows="8" spellcheck="false" placeholder="&lt;link rel=&quot;preconnect&quot; href=&quot;https://fonts.googleapis.com&quot;&gt;">{{ old('custom_html_head', $customHtmlHead) }}</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">HTML after &lt;body&gt; open</label>
            <textarea name="custom_html_body_open" class="code-editor" rows="6" spellcheck="false" placeholder="&lt;!-- noscript / tracking pixel --&gt;">{{ old('custom_html_body_open', $customHtmlBodyOpen) }}</textarea>
        </div>
        <div>
            <label class="form-label">HTML before &lt;/body&gt;</label>
            <textarea name="custom_html_body_close" class="code-editor" rows="6" spellcheck="false">{{ old('custom_html_body_close', $customHtmlBodyClose) }}</textarea>
        </div>
    </div>

    <div class="form-actions mt-3">
        <button class="btn btn-primary" type="submit">Save Custom Code</button>
    </div>
</form>
@endsection

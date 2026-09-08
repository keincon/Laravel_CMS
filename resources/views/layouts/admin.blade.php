<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('common.admin')) — {{ config('cms.name') }}</title>
    @php $ui = app(\App\Services\UIFrameworkService::class); @endphp
    @foreach ($ui->stylesheetUrls() as $href)
        <link rel="stylesheet" href="{{ $href }}">
    @endforeach
    <x-theme />
    <x-favicon />
    <style>
        :root, [data-theme="light"] {
            color-scheme: light;
            --admin-nav-w: 260px;
            --admin-border: #e2e8f0;
            --admin-muted: #475569;
            --admin-text: #0f172a;
            --admin-surface: #ffffff;
            --admin-bg: #f1f5f9;
            --admin-elevated: #ffffff;
            --admin-primary: #2563eb;
            --admin-primary-soft: #dbeafe;
            --admin-hover: #f8fafc;
            --admin-input-border: #cbd5e1;
            --admin-danger: #b91c1c;
            --admin-danger-border: #fecaca;
            --admin-success-bg: #dcfce7;
            --admin-success-text: #166534;
            --admin-chip-bg: #f8fafc;
            --admin-table-head: #f8fafc;
            --admin-shadow: 0 1px 2px rgba(15, 23, 42, .04);
            --color-primary: #2563eb;
            --color-text: #0f172a;
            --color-background: #f1f5f9;
            --color-surface: #ffffff;
        }
        [data-theme="dark"] {
            color-scheme: dark;
            --admin-border: #334155;
            --admin-muted: #94a3b8;
            --admin-text: #e5e7eb;
            --admin-surface: #111827;
            --admin-bg: #0b1220;
            --admin-elevated: #1f2937;
            --admin-primary: #60a5fa;
            --admin-primary-soft: #1e3a5f;
            --admin-hover: #1e293b;
            --admin-input-border: #475569;
            --admin-danger: #fca5a5;
            --admin-danger-border: #7f1d1d;
            --admin-success-bg: #14532d;
            --admin-success-text: #bbf7d0;
            --admin-chip-bg: #1f2937;
            --admin-table-head: #0f172a;
            --admin-shadow: 0 1px 2px rgba(0, 0, 0, .35);
            --color-primary: #60a5fa;
            --color-text: #e5e7eb;
            --color-background: #0b1220;
            --color-surface: #111827;
            --color-border: #334155;
            --color-muted: #94a3b8;
            --color-elevated: #1f2937;
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            background: var(--admin-bg) !important;
            color: var(--admin-text) !important;
            font-family: "Segoe UI", system-ui, sans-serif;
            color-scheme: light;
        }
        html[data-theme="dark"], html[data-theme="dark"] body {
            color-scheme: dark;
        }
        .admin-shell {
            display: grid;
            grid-template-columns: var(--admin-nav-w) 1fr;
            min-height: 100vh;
            background: var(--admin-bg);
            color: var(--admin-text);
        }
        .admin-nav {
            background: var(--admin-surface);
            border-right: 1px solid var(--admin-border);
            padding: 1rem .85rem 1.5rem;
            position: sticky; top: 0; height: 100vh; overflow-y: auto;
            color: var(--admin-text);
        }
        .admin-brand {
            display: flex; align-items: center; gap: .65rem;
            font-weight: 700; color: var(--admin-primary) !important;
            padding: .35rem .5rem 1rem; text-decoration: none;
        }
        .admin-brand-mark {
            width: 2rem; height: 2rem; border-radius: .55rem;
            background: linear-gradient(145deg, #2563eb, #0ea5e9);
            color: #fff !important; display: grid; place-items: center;
            font-size: .85rem; font-weight: 800;
        }
        .admin-nav-section { margin-top: .85rem; }
        .admin-nav-section summary {
            list-style: none; cursor: pointer; user-select: none;
            font-size: .68rem; letter-spacing: .06em; text-transform: uppercase;
            color: var(--admin-muted) !important; font-weight: 700;
            padding: .4rem .55rem; display: flex; justify-content: space-between; align-items: center;
        }
        .admin-nav-section summary::-webkit-details-marker { display: none; }
        .admin-nav-section summary::after { content: "▾"; font-size: .7rem; opacity: .7; }
        .admin-nav-section:not([open]) summary::after { content: "▸"; }
        .admin-nav a {
            display: flex; align-items: center; gap: .55rem;
            padding: .5rem .65rem; border-radius: .55rem;
            color: var(--admin-text) !important; text-decoration: none;
            margin-bottom: .15rem; font-size: .92rem;
        }
        .admin-nav a:hover { background: var(--admin-hover); }
        .admin-nav a.active {
            background: var(--admin-primary-soft);
            color: var(--admin-primary) !important;
            font-weight: 600;
        }
        .admin-nav-footer { margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--admin-border); }
        .admin-main-wrap { display: flex; flex-direction: column; min-width: 0; background: var(--admin-bg); }
        .admin-topbar {
            display: flex; align-items: center; justify-content: space-between; gap: 1rem;
            padding: .85rem 1.35rem; background: var(--admin-surface);
            border-bottom: 1px solid var(--admin-border);
            position: sticky; top: 0; z-index: 20;
            color: var(--admin-text);
        }
        .admin-topbar h1 { margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--admin-text) !important; }
        .admin-topbar-actions { display: flex; align-items: center; gap: .65rem; }
        .admin-chip {
            display: inline-flex; align-items: center; gap: .4rem;
            padding: .35rem .7rem; border-radius: 999px; font-size: .82rem;
            background: var(--admin-chip-bg); border: 1px solid var(--admin-border);
            color: var(--admin-muted) !important; text-decoration: none;
        }
        .theme-toggle {
            display: inline-flex; align-items: center; gap: .4rem;
            border: 1px solid var(--admin-border);
            background: var(--admin-elevated);
            color: var(--admin-text);
            border-radius: 999px; padding: .35rem .75rem;
            font-size: .82rem; font-weight: 650; cursor: pointer;
        }
        .theme-toggle:hover { border-color: var(--admin-primary); }
        .theme-toggle[data-theme-active="dark"] .theme-toggle-sun { display: inline; }
        .theme-toggle[data-theme-active="dark"] .theme-toggle-moon { display: none; }
        .theme-toggle[data-theme-active="light"] .theme-toggle-sun { display: none; }
        .theme-toggle[data-theme-active="light"] .theme-toggle-moon { display: inline; }
        .locale-switcher { display: inline-flex; align-items: center; margin: 0; }
        .locale-switcher-select {
            border: 1px solid var(--admin-border);
            background: var(--admin-elevated) !important;
            color: var(--admin-text) !important;
            border-radius: 999px;
            padding: .35rem .7rem;
            font-size: .82rem;
            font-weight: 650;
            color-scheme: inherit;
            cursor: pointer;
        }
        .visually-hidden {
            position: absolute !important; width: 1px; height: 1px; padding: 0; margin: -1px;
            overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
        }
        .mode-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .85rem; }
        .mode-card { cursor: pointer; margin: 0; }
        .mode-card-inner {
            border: 1.5px solid var(--admin-border); border-radius: .9rem; padding: 1rem;
            background: var(--admin-elevated); height: 100%; color: var(--admin-text);
        }
        .mode-card.is-selected .mode-card-inner {
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--admin-primary) 28%, transparent);
        }
        .mode-preview {
            display: grid; grid-template-columns: 1fr 2fr; gap: .35rem;
            height: 4.25rem; border-radius: .55rem; overflow: hidden; margin-bottom: .75rem;
            border: 1px solid var(--admin-border);
        }
        .mode-preview span { display: block; }
        .mode-preview-light { background: #f8fafc; }
        .mode-preview-light span:nth-child(1) { background: #e2e8f0; }
        .mode-preview-light span:nth-child(2) { background: #fff; grid-row: span 2; }
        .mode-preview-light span:nth-child(3) { background: #cbd5e1; }
        .mode-preview-dark { background: #0b1220; }
        .mode-preview-dark span:nth-child(1) { background: #1f2937; }
        .mode-preview-dark span:nth-child(2) { background: #111827; grid-row: span 2; }
        .mode-preview-dark span:nth-child(3) { background: #334155; }
        .mode-preview-system {
            background: linear-gradient(90deg, #f8fafc 50%, #0b1220 50%);
        }
        .mode-preview-system span:nth-child(1) { background: linear-gradient(90deg, #e2e8f0 50%, #1f2937 50%); }
        .mode-preview-system span:nth-child(2) { background: linear-gradient(90deg, #fff 50%, #111827 50%); grid-row: span 2; }
        .mode-preview-system span:nth-child(3) { background: linear-gradient(90deg, #cbd5e1 50%, #334155 50%); }
        .mode-title { font-weight: 700; }
        .mode-desc { color: var(--admin-muted); font-size: .86rem; margin-top: .2rem; }
        .admin-main { padding: 1.35rem 1.5rem 2.5rem; color: var(--admin-text); }
        .admin-menu-btn {
            display: none; border: 1px solid var(--admin-border); background: var(--admin-elevated);
            border-radius: .55rem; padding: .45rem .65rem; cursor: pointer; color: var(--admin-text);
        }
        .admin-backdrop { display: none; }
        .page-intro, .text-muted, .panel-desc, .stat-card .label, .stat-card .hint, .small {
            color: var(--admin-muted) !important;
        }
        .stat-card .value, .h3, .h5, .h6, .fw-semibold, label, .form-label {
            color: var(--admin-text) !important;
        }
        a { color: var(--admin-primary); }
        .stat-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; }
        .stat-card, .panel {
            background: var(--admin-elevated) !important;
            border: 1px solid var(--admin-border);
            border-radius: 1rem;
            padding: 1.1rem 1.2rem;
            color: var(--admin-text) !important;
            box-shadow: var(--admin-shadow);
        }
        .stat-card .label { font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
        .stat-card .value { font-size: 1.85rem; font-weight: 750; margin-top: .25rem; }
        .stat-card .hint { font-size: .85rem; margin-top: .35rem; }
        .empty-state {
            text-align: center; padding: 2.5rem 1.25rem; color: var(--admin-muted) !important;
            border: 1px dashed var(--admin-input-border); border-radius: 1rem; background: var(--admin-elevated);
        }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: .35rem; padding: .5rem .95rem; border-radius: .55rem; border: 1px solid transparent; font-size: .9rem; font-weight: 650; text-decoration: none; cursor: pointer; line-height: 1.2; }
        .btn-sm { padding: .3rem .65rem; font-size: .8rem; }
        .btn-primary { background: var(--admin-primary) !important; color: #fff !important; border-color: var(--admin-primary) !important; }
        .btn-primary:hover { filter: brightness(.92); color: #fff !important; }
        .btn-outline-secondary { background: var(--admin-elevated) !important; border-color: var(--admin-input-border) !important; color: var(--admin-text) !important; }
        .btn-outline-danger { background: var(--admin-elevated) !important; border-color: var(--admin-danger-border) !important; color: var(--admin-danger) !important; }
        .btn-outline-primary { background: var(--admin-elevated) !important; border-color: var(--admin-primary) !important; color: var(--admin-primary) !important; }
        .form-control, .form-select { width: 100%; border: 1px solid var(--admin-input-border); border-radius: .55rem; padding: .55rem .75rem; background: var(--admin-elevated) !important; color: var(--admin-text) !important; color-scheme: inherit; }
        select:not(.form-select), input:not([type="checkbox"]):not([type="radio"]):not(.form-control), textarea:not(.form-control) {
            background: var(--admin-elevated);
            color: var(--admin-text);
            border-color: var(--admin-input-border);
            color-scheme: inherit;
        }
        .form-label { display: block; font-size: .85rem; font-weight: 650; margin-bottom: .35rem; }
        .table { width: 100%; border-collapse: collapse; background: var(--admin-elevated); color: var(--admin-text); }
        .table th, .table td { padding: .75rem .9rem; border-bottom: 1px solid var(--admin-border); text-align: left; }
        .table thead th { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: var(--admin-muted) !important; background: var(--admin-table-head); }
        .badge { display: inline-block; padding: .2rem .5rem; border-radius: 999px; font-size: .72rem; font-weight: 700; }
        .text-bg-success { background: var(--admin-success-bg) !important; color: var(--admin-success-text) !important; }
        .text-bg-secondary { background: var(--admin-hover) !important; color: var(--admin-muted) !important; }
        .text-bg-primary { background: var(--admin-primary-soft) !important; color: var(--admin-primary) !important; }
        .row { display: flex; flex-wrap: wrap; margin: 0 -.5rem; }
        .row > [class*="col-"] { padding: 0 .5rem; }
        .col-md-4, .col-lg-4 { flex: 0 0 33.33%; max-width: 33.33%; }
        .col-md-6 { flex: 0 0 50%; max-width: 50%; }
        .col-lg-5 { flex: 0 0 41.66%; max-width: 41.66%; }
        .col-lg-7 { flex: 0 0 58.33%; max-width: 58.33%; }
        .col-lg-8 { flex: 0 0 66.66%; max-width: 66.66%; }
        .d-flex { display: flex; } .d-grid { display: grid; } .d-inline { display: inline; } .d-none { display: none; }
        .flex-wrap { flex-wrap: wrap; } .align-items-center { align-items: center; }
        .justify-content-between { justify-content: space-between; }
        .gap-1 { gap: .25rem; } .gap-2 { gap: .5rem; }
        .w-100 { width: 100%; }
        .mb-0 { margin-bottom: 0; } .mb-2 { margin-bottom: .5rem; } .mb-3 { margin-bottom: 1rem; } .mb-4 { margin-bottom: 1.5rem; }
        .mt-2 { margin-top: .5rem; } .mt-3 { margin-top: 1rem; }
        .py-2 { padding-top: .5rem; padding-bottom: .5rem; }
        .p-3 { padding: 1rem; } .border { border: 1px solid var(--admin-border); } .border-bottom { border-bottom: 1px solid var(--admin-border); }
        .rounded, .rounded-3 { border-radius: .75rem; } .bg-white { background: var(--admin-elevated) !important; }
        .text-end { text-align: right; } .text-center { text-align: center; }
        .h3 { font-size: 1.5rem; font-weight: 700; } .h5 { font-size: 1.15rem; font-weight: 700; } .h6 { font-size: .95rem; font-weight: 700; }
        .settings-form { max-width: 760px; }
        .panel-head { padding: 0 0 .75rem; }
        .panel-body { padding: 0; }
        .framework-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem; }
        .framework-card { display: block; cursor: pointer; margin: 0; }
        .framework-card .framework-radio { position: absolute; opacity: 0; pointer-events: none; }
        .framework-card-inner { border: 1.5px solid var(--admin-border); border-radius: .9rem; padding: 1rem; background: var(--admin-elevated); height: 100%; color: var(--admin-text); }
        .framework-card.is-selected .framework-card-inner { border-color: var(--admin-primary); background: var(--admin-primary-soft); box-shadow: 0 0 0 3px color-mix(in srgb, var(--admin-primary) 28%, transparent); }
        .framework-card-top { display: flex; gap: .75rem; align-items: flex-start; }
        .framework-radio-ui { width: 1.1rem; height: 1.1rem; border-radius: 999px; border: 2px solid var(--admin-muted); margin-top: .15rem; flex-shrink: 0; background: var(--admin-elevated); position: relative; }
        .framework-card.is-selected .framework-radio-ui { border-color: var(--admin-primary); }
        .framework-card.is-selected .framework-radio-ui::after { content: ""; position: absolute; inset: 3px; border-radius: 999px; background: var(--admin-primary); }
        .framework-title { font-weight: 700; color: var(--admin-text) !important; }
        .framework-desc { color: var(--admin-muted) !important; font-size: .86rem; margin-top: .15rem; }
        .framework-preview { margin-top: .9rem; display: flex; flex-wrap: wrap; gap: .45rem; padding: .75rem; border-radius: .65rem; background: var(--admin-hover); border: 1px solid var(--admin-border); }
        .fp-btn { font-size: .75rem; font-weight: 650; padding: .35rem .65rem; border-radius: .45rem; }
        .fp-primary { background: var(--admin-primary); color: #fff !important; }
        .fp-ghost { background: var(--admin-elevated); border: 1px solid var(--admin-input-border); color: var(--admin-text) !important; }
        .fp-input { flex: 1; min-width: 4.5rem; background: var(--admin-elevated); border: 1px solid var(--admin-input-border); border-radius: .45rem; padding: .35rem .55rem; color: var(--admin-muted); font-size: .75rem; }
        .form-actions { display: flex; justify-content: flex-end; padding-top: .85rem; }
        .code-editor {
            width: 100%; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: .85rem; line-height: 1.45; tab-size: 2;
            background: var(--admin-elevated) !important; color: var(--admin-text) !important;
            border: 1px solid var(--admin-input-border); border-radius: .65rem; padding: .75rem .85rem;
        }
        .html-toolbar { display: flex; flex-wrap: wrap; gap: .35rem; }
        .widget-card {
            border: 1px solid var(--admin-border); border-radius: .75rem; padding: .75rem 1rem;
            background: var(--admin-elevated);
        }
        .widget-card summary { cursor: pointer; list-style: none; }
        .widget-card summary::-webkit-details-marker { display: none; }
        .users-role-tabs { display: flex; flex-wrap: wrap; gap: .55rem .85rem; }
        .users-role-tabs a {
            color: var(--admin-muted); text-decoration: none; font-size: .9rem;
            padding: .2rem 0; border-bottom: 2px solid transparent;
        }
        .users-role-tabs a.is-active { color: var(--admin-primary); border-bottom-color: var(--admin-primary); font-weight: 700; }
        .users-role-tabs span { color: var(--admin-muted); font-weight: 600; }
        .row-actions a, .row-actions button {
            background: none; border: 0; padding: 0; color: var(--admin-primary); cursor: pointer; font-size: .8rem;
        }
        .row-actions .link-danger { color: var(--admin-danger); }
        .roles-layout { display: grid; grid-template-columns: 220px 1fr; gap: 1rem; }
        .role-link {
            display: flex; justify-content: space-between; align-items: center;
            padding: .55rem .65rem; border-radius: .55rem; text-decoration: none;
            color: var(--admin-text); margin-bottom: .25rem;
        }
        .role-link:hover { background: var(--admin-hover); }
        .role-link.is-active { background: var(--admin-primary-soft); color: var(--admin-primary); font-weight: 650; }
        .capability-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .65rem; }
        .capability-item {
            display: flex; gap: .65rem; align-items: flex-start;
            border: 1px solid var(--admin-border); border-radius: .65rem; padding: .75rem;
            background: var(--admin-elevated); cursor: pointer;
        }
        .capability-item small { display: block; color: var(--admin-muted); margin-top: .15rem; }
        .capability-list { list-style: none; padding: 0; margin: 0; display: grid; gap: .45rem; }
        .capability-list code { color: var(--admin-muted); font-size: .78rem; margin-left: .35rem; }
        @media (max-width: 900px) {
            .roles-layout { grid-template-columns: 1fr; }
            .capability-grid { grid-template-columns: 1fr; }
        }
        .media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: .85rem; }
        .media-card { border: 1px solid var(--admin-border); border-radius: .85rem; overflow: hidden; background: var(--admin-elevated); color: var(--admin-text); }
        .media-card img { width: 100%; height: 120px; object-fit: cover; display: block; background: var(--admin-hover); }
        .media-card .meta { padding: .65rem .75rem; font-size: .82rem; }
        @media (max-width: 1100px) { .stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 900px) {
            .admin-shell { grid-template-columns: 1fr; }
            .admin-nav {
                position: fixed; inset: 0 auto 0 0; width: min(86vw, 300px);
                transform: translateX(-105%); transition: transform .2s ease; z-index: 40;
                box-shadow: 12px 0 40px rgba(15,23,42,.12);
            }
            .admin-shell.nav-open .admin-nav { transform: translateX(0); }
            .admin-shell.nav-open .admin-backdrop {
                display: block; position: fixed; inset: 0; background: rgba(15,23,42,.35); z-index: 30;
            }
            .admin-menu-btn { display: inline-flex; align-items: center; gap: .35rem; }
            .col-md-4, .col-md-6, .col-lg-4, .col-lg-5, .col-lg-7, .col-lg-8 { flex: 0 0 100%; max-width: 100%; }
            .framework-grid { grid-template-columns: 1fr; }
            .mode-grid { grid-template-columns: 1fr; }
        }
    </style>
    @stack('head')
</head>
<body>
@php
    $siteName = \App\Models\CmsSetting::getValue('site_name') ?: config('cms.name');
@endphp
<div class="admin-shell" x-data="{ open: false }" :class="{ 'nav-open': open }">
    <div class="admin-backdrop" @click="open = false"></div>
    <aside class="admin-nav">
        <a href="{{ route('admin.dashboard') }}" class="admin-brand">
            <span class="admin-brand-mark">{{ strtoupper(substr($siteName, 0, 1)) }}</span>
            <span>{{ $siteName }}</span>
        </a>

        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.dashboard') }}</a>
        <a href="{{ route('admin.help.setup-guide') }}" class="{{ request()->routeIs('admin.help.setup-guide') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.setup_guide') }}</a>

        <details class="admin-nav-section" @if(request()->routeIs('admin.pages.*','admin.posts.*','admin.contents.*','admin.categories.*','admin.tags.*','admin.taxonomies.*','admin.media.*','admin.comments.*')) open @endif>
            <summary>{{ __('admin.nav.content') }}</summary>
            <a href="{{ route('admin.contents.index', ['type' => 'post']) }}" class="{{ request()->routeIs('admin.contents.*') && request('type', 'post') === 'post' ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.posts') }}</a>
            <a href="{{ route('admin.contents.index', ['type' => 'page']) }}" class="{{ request()->routeIs('admin.contents.*') && request('type') === 'page' ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.pages') }}</a>
            <a href="{{ route('admin.contents.index') }}" class="{{ request()->routeIs('admin.contents.*') && ! in_array(request('type'), ['post', 'page'], true) ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.all_contents') }}</a>
            @if(app(\App\Services\Content\LegacyRetirementService::class)->adminUiEnabled())
                <a href="{{ route('admin.posts.index', ['legacy' => 1]) }}" class="{{ request()->routeIs('admin.posts.*') ? 'active' : '' }}" @click="open=false" style="opacity:.65">{{ __('admin.nav.legacy_posts') }}</a>
                <a href="{{ route('admin.pages.index', ['legacy' => 1]) }}" class="{{ request()->routeIs('admin.pages.*') ? 'active' : '' }}" @click="open=false" style="opacity:.65">{{ __('admin.nav.legacy_pages') }}</a>
            @endif
            <a href="{{ route('admin.content-types.index') }}" class="{{ request()->routeIs('admin.content-types.*') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.content_types') }}</a>
            <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.categories') }}</a>
            <a href="{{ route('admin.taxonomies.index') }}" class="{{ request()->routeIs('admin.taxonomies.*') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.taxonomies') }}</a>
            <a href="{{ route('admin.tags.index') }}" class="{{ request()->routeIs('admin.tags.*') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.tags') }}</a>
            <a href="{{ route('admin.media.index') }}" class="{{ request()->routeIs('admin.media.*') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.media') }}</a>
            @can('manage_comments')
                <a href="{{ route('admin.comments.index') }}" class="{{ request()->routeIs('admin.comments.*') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.comments') }}</a>
            @endcan
        </details>

        <details class="admin-nav-section" @if(request()->routeIs('admin.headers.*','admin.footers.*','admin.menus.*','admin.appearance.*')) open @endif>
            <summary>{{ __('admin.nav.appearance') }}</summary>
            <a href="{{ route('admin.headers.index') }}" class="{{ request()->routeIs('admin.headers.*') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.header') }}</a>
            <a href="{{ route('admin.footers.index') }}" class="{{ request()->routeIs('admin.footers.*') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.footer') }}</a>
            <a href="{{ route('admin.menus.index') }}" class="{{ request()->routeIs('admin.menus.*') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.menus') }}</a>
            <a href="{{ route('admin.appearance.themes') }}" class="{{ request()->routeIs('admin.appearance.themes*') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.themes') }}</a>
            <a href="{{ route('admin.appearance.layout') }}" class="{{ request()->routeIs('admin.appearance.layout') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.master_layout') }}</a>
            <a href="{{ route('admin.appearance.dynamic-pages.index') }}" class="{{ request()->routeIs('admin.appearance.dynamic-pages.*') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.dynamic_pages') }}</a>
            <a href="{{ route('admin.appearance.colors') }}" class="{{ request()->routeIs('admin.appearance.colors') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.theme_colors') }}</a>
            <a href="{{ route('admin.appearance.mode') }}" class="{{ request()->routeIs('admin.appearance.mode') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.color_mode') }}</a>
            <a href="{{ route('admin.appearance.widgets') }}" class="{{ request()->routeIs('admin.appearance.widgets*') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.widgets') }}</a>
            <a href="{{ route('admin.appearance.custom-code') }}" class="{{ request()->routeIs('admin.appearance.custom-code*') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.custom_code') }}</a>
        </details>

        <a href="{{ route('admin.plugins.index') }}" class="{{ request()->routeIs('admin.plugins.*') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.plugins') }}</a>

        <details class="admin-nav-section" @if(request()->routeIs('admin.settings.*','admin.redirects.*','admin.audit-logs.*','admin.modules.*')) open @endif>
            <summary>{{ __('admin.nav.settings') }}</summary>
            <a href="{{ route('admin.settings.general') }}" class="{{ request()->routeIs('admin.settings.general*') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.general_ui') }}</a>
            <a href="{{ route('admin.redirects.index') }}" class="{{ request()->routeIs('admin.redirects.*') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.redirects') }}</a>
            <a href="{{ route('admin.audit-logs.index') }}" class="{{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.audit_log') }}</a>
            <a href="{{ route('admin.modules.index') }}" class="{{ request()->routeIs('admin.modules.*') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.modules') }}</a>
            <a href="{{ route('admin.settings.discussion') }}" class="{{ request()->routeIs('admin.settings.discussion*') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.discussion') }}</a>
            <a href="{{ route('admin.settings.seo') }}" class="{{ request()->routeIs('admin.settings.seo') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.seo') }}</a>
            <a href="{{ route('admin.settings.seo.templates') }}" class="{{ request()->routeIs('admin.settings.seo.templates*') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.seo_templates') }}</a>
            <a href="{{ route('admin.settings.ogp') }}" class="{{ request()->routeIs('admin.settings.ogp') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.social_ogp') }}</a>
            <a href="{{ route('admin.settings.permalinks') }}" class="{{ request()->routeIs('admin.settings.permalinks') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.permalinks') }}</a>
            <a href="{{ route('admin.settings.reading') }}" class="{{ request()->routeIs('admin.settings.reading*') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.reading') }}</a>
            <a href="{{ route('admin.settings.api') }}" class="{{ request()->routeIs('admin.settings.api') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.api') }}</a>
            <a href="{{ route('admin.settings.cors') }}" class="{{ request()->routeIs('admin.settings.cors*') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.cors') }}</a>
        </details>

        <details class="admin-nav-section" @if(request()->routeIs('admin.users.*','admin.profile.*')) open @endif>
            <summary>{{ __('admin.nav.users') }}</summary>
            @can('manage_users')
                <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.index','admin.users.create','admin.users.edit') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.all_users') }}</a>
                <a href="{{ route('admin.users.create') }}" class="{{ request()->routeIs('admin.users.create') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.add_new') }}</a>
            @endcan
            @can('manage_roles')
                <a href="{{ route('admin.users.roles') }}" class="{{ request()->routeIs('admin.users.roles*') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.roles') }}</a>
            @endcan
            <a href="{{ route('admin.profile.edit') }}" class="{{ request()->routeIs('admin.profile.*') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.profile') }}</a>
            <a href="{{ route('admin.users.tokens') }}" class="{{ request()->routeIs('admin.users.tokens*') ? 'active' : '' }}" @click="open=false">{{ __('admin.nav.api_tokens') }}</a>
        </details>

        <div class="admin-nav-footer">
            <a href="{{ route('home') }}" target="_blank" rel="noopener">↗ {{ __('common.view_website') }}</a>
            @auth
                <form method="POST" action="{{ route('logout') }}" class="mt-2 px-1">@csrf
                    <button class="btn btn-sm btn-outline-secondary w-100" type="submit">{{ __('common.log_out') }}</button>
                </form>
            @endauth
        </div>
    </aside>

    <div class="admin-main-wrap">
        <header class="admin-topbar">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="admin-menu-btn" @click="open = !open" aria-label="{{ __('common.open_menu') }}">☰ {{ __('common.menu') }}</button>
                <h1>@yield('title', __('common.admin'))</h1>
            </div>
            <div class="admin-topbar-actions">
                <x-locale-switcher />
                <x-theme-toggle />
                @auth
                    <a class="admin-chip" href="{{ route('admin.profile.edit') }}">{{ auth()->user()->name }}</a>
                @endauth
                <a class="admin-chip" href="{{ route('home') }}" target="_blank" rel="noopener">{{ __('common.website') }}</a>
            </div>
        </header>
        <main class="admin-main">
            @if (session('success'))
                <x-ui.alert type="success">{{ session('success') }}</x-ui.alert>
            @endif
            @if (session('error'))
                <x-ui.alert type="danger">{{ session('error') }}</x-ui.alert>
            @endif
            @yield('content')
        </main>
    </div>
</div>
@foreach ($ui->scriptUrls() as $src)
    <script src="{{ $src }}" defer></script>
@endforeach
@stack('scripts')
</body>
</html>

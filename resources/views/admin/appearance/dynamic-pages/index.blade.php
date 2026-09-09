@extends('layouts.admin')
@section('title', __('admin.nav.dynamic_pages'))
@section('content')
<h1 class="h3 mb-2">{{ __('admin.nav.dynamic_pages') }}</h1>
<p class="text-muted mb-3">
    {{ __('admin.help_ux.dynamic_pages_index.intro') }}
</p>

<x-admin.help-next context="dynamic_pages_index" />

<div class="table-responsive bg-white border rounded">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>{{ __('admin.ui.type') }}</th>
                <th>{{ __('admin.ui.title') }}</th>
                <th>URL</th>
                <th>{{ __('admin.ui.status') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @foreach ($pages as $page)
            @php $def = $definitions[$page->type] ?? []; @endphp
            <tr>
                <td>
                    <span class="badge text-bg-light border">Dynamic</span>
                    {{ $page->label ?: ($def['label'] ?? $page->type) }}
                </td>
                <td>{{ $page->title }}</td>
                <td class="text-muted">{{ $page->url_path ?: '—' }}</td>
                <td>
                    @if ($page->is_enabled)
                        <span class="text-success">{{ __('admin.ui.active') }}</span>
                    @else
                        <span class="text-danger">{{ __('admin.ui.statuses.inactive') }}</span>
                    @endif
                </td>
                <td class="text-end">
                    <a href="{{ route('admin.appearance.dynamic-pages.edit', $page->type) }}">{{ __('admin.ui.edit') }}</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection

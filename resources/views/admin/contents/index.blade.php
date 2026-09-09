@extends('layouts.admin')

@section('title', $type->plural_label)

@section('content')
@php
    $isPageType = $isPageType ?? ($type->slug === 'page');
    $permalinks = app(\App\Services\PermalinkService::class);
@endphp
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">{{ $type->plural_label }}</h1>
        <p class="page-intro mb-0">
            @if ($isPageType)
                {{ __('admin.contents.pages_intro') }}
            @else
                {{ __('admin.contents.intro', ['slug' => $type->slug]) }}
            @endif
        </p>
        <p class="small text-muted mb-0 mt-1">{{ __('admin.contents.list_hint') }}</p>
    </div>
    <a class="btn btn-primary" href="{{ route('admin.contents.create', ['type' => $type->slug]) }}">{{ __('admin.contents.add_new') }}</a>
</div>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

@if ($isPageType)
    <x-admin.help-next context="pages_index" />
@elseif ($type->slug === 'post')
    <x-admin.help-next context="posts_index" />
@elseif ($type->slug === 'campaign')
    <x-admin.help-next context="campaigns_index" />
@else
    <x-admin.help-next context="contents_index" />
@endif

<div class="users-role-tabs mb-3">
    @foreach ($types as $t)
        <a href="{{ route('admin.contents.index', ['type' => $t->slug]) }}" class="{{ $type->slug === $t->slug ? 'is-active' : '' }}">{{ $t->plural_label }}</a>
    @endforeach
</div>

<div class="users-role-tabs mb-3">
    @foreach ([
        'all' => __('admin.contents.all'),
        'published' => __('admin.contents.published'),
        'draft' => __('admin.contents.draft'),
        'pending' => __('admin.contents.pending'),
        'scheduled' => __('admin.contents.scheduled'),
        'trash' => __('admin.contents.trash'),
    ] as $key => $label)
        <a href="{{ route('admin.contents.index', array_filter(['type' => $type->slug, 'status' => $key === 'all' ? null : $key, 'q' => $q ?: null])) }}"
           class="{{ $status === $key ? 'is-active' : '' }}">
            {{ $label }} <span>({{ $counts[$key] ?? 0 }})</span>
        </a>
    @endforeach
</div>

<div class="panel mb-3">
    <form method="GET" action="{{ route('admin.contents.index') }}" class="d-flex gap-2 flex-wrap align-items-center">
        <input type="hidden" name="type" value="{{ $type->slug }}">
        @if ($status !== 'all')
            <input type="hidden" name="status" value="{{ $status }}">
        @endif
        <input type="search" name="q" value="{{ $q }}" class="form-control" style="max-width:280px" placeholder="{{ __('admin.contents.search_placeholder') }}">
        <button class="btn btn-outline-secondary" type="submit">{{ __('admin.contents.search') }}</button>
    </form>
</div>

<div class="panel">
    @if ($contents->isEmpty())
        <div class="empty-state">{{ __('admin.contents.no_content') }} <a href="{{ route('admin.contents.create', ['type' => $type->slug]) }}">{{ __('admin.contents.create_one') }}</a>.</div>
    @else
        <div class="table-responsive">
            <table class="table mb-0 align-middle contents-list-table">
                <thead>
                    <tr>
                        <th>{{ __('admin.contents.title_label') }}</th>
                        @if ($isPageType)
                            <th>{{ __('admin.contents.public_url') }}</th>
                            <th>{{ __('admin.contents.in_menu') }}</th>
                        @else
                            <th>{{ __('admin.contents.author') }}</th>
                        @endif
                        <th>{{ __('admin.contents.status') }}</th>
                        <th>{{ __('admin.contents.updated') }}</th>
                        <th class="text-end">{{ __('admin.contents.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($contents as $item)
                        @php
                            $editUrl = route('admin.contents.edit', $item);
                            $path = '/'.ltrim($permalinks->contentPath($item), '/');
                            $statusKey = $item->status instanceof \BackedEnum ? $item->status->value : (string) $item->status;
                            $statusLabel = __('admin.contents.'.$statusKey);
                            if ($statusLabel === 'admin.contents.'.$statusKey) {
                                $statusLabel = $statusKey;
                            }
                            $inMenu = $isPageType && in_array($path === '/' ? '/' : $path, $menuPaths ?? [], true);
                        @endphp
                        <tr class="contents-list-row" style="cursor:pointer" onclick="if(!event.target.closest('a,button,form,select,input')) window.location='{{ $editUrl }}'">
                            <td>
                                <a class="fw-semibold text-decoration-underline" href="{{ $editUrl }}">{{ $item->title }}</a>
                                <div class="small text-muted">{{ __('admin.contents.click_to_edit') }}</div>
                            </td>
                            @if ($isPageType)
                                <td><code>{{ $path }}</code></td>
                                <td>
                                    @if ($inMenu)
                                        <span class="badge text-bg-success">{{ __('admin.contents.yes') }}</span>
                                    @else
                                        <span class="badge text-bg-light text-muted">{{ __('admin.contents.no') }}</span>
                                    @endif
                                </td>
                            @else
                                <td>{{ $item->author?->publicName() ?? '—' }}</td>
                            @endif
                            <td><span class="badge text-bg-light border">{{ $statusLabel }}</span></td>
                            <td>{{ $item->updated_at?->diffForHumans() }}</td>
                            <td class="text-end text-nowrap" onclick="event.stopPropagation()">
                                <a class="btn btn-sm btn-primary" href="{{ $editUrl }}">{{ __('admin.contents.edit') }}</a>
                                @if ($statusKey === 'published' || $statusKey === 'publish')
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ url($path) }}" target="_blank" rel="noopener">{{ __('admin.contents.view') }}</a>
                                @endif
                                @if ($isPageType && ! $inMenu && ($menus ?? collect())->isNotEmpty())
                                    <form method="POST" action="{{ route('admin.contents.add-to-menu', $item) }}" class="d-inline-flex gap-1 align-items-center ms-1">
                                        @csrf
                                        <select name="menu_id" class="form-select form-select-sm" style="width:auto;max-width:140px" required>
                                            @foreach ($menus as $menu)
                                                <option value="{{ $menu->id }}" @selected($menu->slug === 'primary' || $menu->location === 'primary')>{{ $menu->name }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-sm btn-outline-primary" type="submit">{{ __('admin.contents.add_to_menu_short') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $contents->links() }}</div>
    @endif
</div>
@endsection

@push('head')
<style>
    .contents-list-row:hover { background: var(--admin-hover, #f8fafc); }
    .contents-list-table a.fw-semibold { color: var(--admin-primary, #2563eb); }
</style>
@endpush

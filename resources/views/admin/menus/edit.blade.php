@extends('layouts.admin')
@section('title', __('admin.appearance.edit_menu'))
@section('content')
@php
    $selectedLinkValue = static function (?string $url, $pageId) {
        if (filled($pageId)) {
            return 'page:'.$pageId;
        }
        if (filled($url)) {
            $path = str_starts_with($url, 'http') ? $url : '/'.ltrim($url, '/');
            if ($url === '/' || $path === '/') {
                return 'url:/';
            }

            return 'url:'.$path;
        }

        return '';
    };
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('admin.menus.index') }}" class="text-muted">← {{ __('admin.nav.menus') }}</a>
    <form method="POST" action="{{ route('admin.menus.destroy', $menu) }}" onsubmit="return confirm(@js(__('admin.menus.confirm_delete_menu')))">
        @csrf @method('DELETE')
        <button class="btn btn-sm btn-outline-danger" type="submit">{{ __('admin.menus.delete_menu') }}</button>
    </form>
</div>

<x-admin.help-next context="menus_edit" />

<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel mb-3">
            <h2 class="h6 mb-3">{{ __('admin.menus.settings') }}</h2>
            <form method="POST" action="{{ route('admin.menus.update', $menu) }}">
                @csrf @method('PUT')
                <div class="mb-3"><label class="form-label">{{ __('admin.ui.name') }}</label><input name="name" class="form-control" value="{{ old('name', $menu->name) }}" required></div>
                <div class="mb-3"><label class="form-label">{{ __('admin.ui.slug') }}</label><input name="slug" class="form-control" value="{{ old('slug', $menu->slug) }}" required></div>
                <div class="mb-3">
                    <label class="form-label">{{ __('admin.ui.location') }}</label>
                    <select name="location" class="form-select">
                        @foreach (['' => '—', 'primary' => __('admin.menus.primary'), 'footer' => __('admin.menus.footer'), 'secondary' => __('admin.menus.secondary')] as $value => $label)
                            <option value="{{ $value }}" @selected(old('location', $menu->location) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">{{ __('admin.ui.save') }}</button>
            </form>
        </div>
        <div class="panel">
            <h2 class="h6 mb-3">{{ __('admin.menus.add_item') }}</h2>
            <p class="small text-muted mb-3">{{ __('admin.menus.link_hint') }}</p>
            <form method="POST" action="{{ route('admin.menus.items.store', $menu) }}" class="js-menu-item-form">
                @csrf
                <div class="mb-3">
                    <label class="form-label">{{ __('admin.menus.link_to') }}</label>
                    <select class="form-select js-menu-link-picker" data-fill-title="1">
                        <option value="">{{ __('admin.menus.link_custom') }}</option>
                        @foreach ($linkGroups as $group)
                            <optgroup label="{{ $group['label'] }}">
                                @foreach ($group['options'] as $option)
                                    <option
                                        value="{{ $option['value'] }}"
                                        data-url="{{ $option['url'] ?? '' }}"
                                        data-page-id="{{ $option['page_id'] ?? '' }}"
                                        data-title="{{ $option['title'] }}"
                                    >{{ $option['label'] }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">{{ __('admin.ui.title') }}</label><input name="title" class="form-control js-menu-title" required></div>
                <div class="mb-3"><label class="form-label">{{ __('admin.menus.custom_url') }}</label><input name="url" class="form-control js-menu-url" placeholder="/about or https://…"></div>
                <input type="hidden" name="page_id" class="js-menu-page-id" value="">
                <div class="mb-3">
                    <label class="form-label">{{ __('admin.menus.parent') }}</label>
                    <select name="parent_id" class="form-select">
                        <option value="">{{ __('admin.menus.parent_none') }}</option>
                        @foreach ($parentOptions as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->title }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-outline-primary w-100" type="submit">{{ __('admin.menus.add_item') }}</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel">
            <h2 class="h6 mb-3">{{ __('admin.menus.items') }}</h2>
            @forelse ($menu->items as $item)
                @include('admin.menus._item-row', ['item' => $item, 'depth' => 0, 'linkGroups' => $linkGroups, 'parentOptions' => $parentOptions, 'selectedLinkValue' => $selectedLinkValue])
                @foreach ($item->children as $child)
                    @include('admin.menus._item-row', ['item' => $child, 'depth' => 1, 'linkGroups' => $linkGroups, 'parentOptions' => $parentOptions, 'selectedLinkValue' => $selectedLinkValue])
                @endforeach
            @empty
                <div class="empty-state py-4">{{ __('admin.menus.items_empty') }}</div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    function applyPicker(select) {
        const form = select.closest('.js-menu-item-form');
        if (!form) return;
        const option = select.options[select.selectedIndex];
        const urlInput = form.querySelector('.js-menu-url');
        const pageInput = form.querySelector('.js-menu-page-id');
        const titleInput = form.querySelector('.js-menu-title');
        if (!urlInput || !pageInput) return;

        if (!select.value) {
            return;
        }

        const url = option.getAttribute('data-url') || '';
        const pageId = option.getAttribute('data-page-id') || '';
        const title = option.getAttribute('data-title') || '';

        urlInput.value = url;
        pageInput.value = pageId;
        if (select.dataset.fillTitle === '1' && titleInput && !titleInput.value.trim() && title) {
            titleInput.value = title;
        }
    }

    document.querySelectorAll('.js-menu-link-picker').forEach(function (select) {
        select.addEventListener('change', function () {
            applyPicker(select);
        });
    });
})();
</script>
@endpush

@php
    $currentValue = $selectedLinkValue($item->url, $item->page_id);
@endphp
<div class="border rounded p-3 mb-2 bg-white {{ $depth > 0 ? 'ms-4' : '' }}">
    <form method="POST" action="{{ route('admin.menus.items.update', [$menu, $item]) }}" class="js-menu-item-form">
        @csrf @method('PUT')
        <div class="row g-2 align-items-end">
            <div class="col-12">
                <label class="form-label">{{ __('admin.menus.link_to') }}</label>
                <select class="form-select form-select-sm js-menu-link-picker">
                    <option value="">{{ __('admin.menus.link_custom') }}</option>
                    @foreach ($linkGroups as $group)
                        <optgroup label="{{ $group['label'] }}">
                            @foreach ($group['options'] as $option)
                                <option
                                    value="{{ $option['value'] }}"
                                    data-url="{{ $option['url'] ?? '' }}"
                                    data-page-id="{{ $option['page_id'] ?? '' }}"
                                    data-title="{{ $option['title'] }}"
                                    @selected($currentValue === $option['value'])
                                >{{ $option['label'] }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3"><label class="form-label">{{ __('admin.ui.title') }}</label><input name="title" class="form-control form-control-sm js-menu-title" value="{{ $item->title }}" required></div>
            <div class="col-md-3"><label class="form-label">{{ __('admin.menus.custom_url') }}</label><input name="url" class="form-control form-control-sm js-menu-url" value="{{ $item->url }}" placeholder="/path or https://…"></div>
            <input type="hidden" name="page_id" class="js-menu-page-id" value="{{ $item->page_id }}">
            <div class="col-md-2">
                <label class="form-label">{{ __('admin.menus.parent') }}</label>
                <select name="parent_id" class="form-select form-select-sm" @disabled($item->children->count() > 0)>
                    <option value="">{{ __('admin.menus.parent_none') }}</option>
                    @foreach ($parentOptions as $parent)
                        @if ((int) $parent->id !== (int) $item->id)
                            <option value="{{ $parent->id }}" @selected((int) $item->parent_id === (int) $parent->id)>{{ $parent->title }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><label class="form-label">{{ __('admin.menus.order') }}</label><input type="number" name="sort_order" class="form-control form-control-sm" value="{{ $item->sort_order }}"></div>
            <div class="col-md-2"><button class="btn btn-sm btn-primary w-100" type="submit">{{ __('admin.ui.save') }}</button></div>
        </div>
        @if ($item->page_id && $item->page)
            <div class="small text-muted mt-2">{{ __('admin.menus.linked_page') }}: {{ $item->page->title }} <code>/{{ $item->page->slug }}</code></div>
        @elseif ($item->url)
            <div class="small text-muted mt-2">{{ __('admin.menus.preview') }}: <code>{{ $item->href() }}</code></div>
        @endif
    </form>
    <form method="POST" action="{{ route('admin.menus.items.destroy', [$menu, $item]) }}" class="mt-2" onsubmit="return confirm(@js(__('admin.menus.confirm_delete_item')))">
        @csrf @method('DELETE')
        <button class="btn btn-sm btn-outline-danger" type="submit">{{ __('admin.menus.delete_item') }}</button>
    </form>
</div>

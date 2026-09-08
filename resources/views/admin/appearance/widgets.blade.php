@extends('layouts.admin')

@section('title', __('admin.nav.widgets'))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">{{ __('admin.nav.widgets') }}</h1>
        <p class="page-intro mb-0">{{ __('admin.appearance.widgets_intro') }}</p>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel">
            <h2 class="h6 mb-3">Available widgets</h2>
            <form method="POST" action="{{ route('admin.appearance.widgets.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">{{ __('admin.ui.type') }}</label>
                    <select name="type" class="form-select" required>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input name="title" class="form-control" placeholder="{{ __('admin.appearance.optional_title') }}">
                </div>
                <button class="btn btn-primary" type="submit">Add to Main Sidebar</button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="panel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h6 mb-0">{{ $sidebar->name }}</h2>
                    <p class="page-intro mb-0">{{ $sidebar->description }}</p>
                </div>
            </div>

            @forelse ($sidebar->widgets as $widget)
                <details class="widget-card mb-3" open>
                    <summary class="d-flex justify-content-between align-items-center gap-2">
                        <span>
                            <strong>{{ $widget->title ?: ($types[$widget->type] ?? $widget->type) }}</strong>
                            <span class="badge text-bg-secondary">{{ $types[$widget->type] ?? $widget->type }}</span>
                            @unless ($widget->is_active)
                                <span class="badge text-bg-secondary">Inactive</span>
                            @endunless
                        </span>
                        <span class="small page-intro">#{{ $widget->sort_order }}</span>
                    </summary>
                    <form method="POST" action="{{ route('admin.appearance.widgets.update', $widget) }}" class="mt-3">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="widget_id" value="{{ $widget->id }}">
                        <div class="mb-2">
                            <label class="form-label">Title</label>
                            <input name="title" class="form-control" value="{{ old('title', $widget->title) }}">
                        </div>
                        @if (in_array($widget->type, ['recent_posts', 'categories'], true))
                            <div class="mb-2">
                                <label class="form-label">Limit</label>
                                <input type="number" min="1" max="50" name="settings[limit]" class="form-control"
                                       value="{{ old('settings.limit', $widget->settings['limit'] ?? 5) }}">
                            </div>
                        @endif
                        @if (in_array($widget->type, ['text', 'custom_html'], true))
                            <div class="mb-2">
                                <label class="form-label">{{ $widget->type === 'custom_html' ? 'HTML' : 'Text' }}</label>
                                <textarea name="settings[content]" class="form-control" rows="5">{{ old('settings.content', $widget->settings['content'] ?? '') }}</textarea>
                            </div>
                        @endif
                        <label class="capability-item mb-3">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $widget->is_active))>
                            <span><strong>{{ __('admin.ui.active') }}</strong><small>Show this widget on the site</small></span>
                        </label>
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-primary btn-sm" type="submit">Save</button>
                            <button class="btn btn-outline-danger btn-sm" form="delete-widget-{{ $widget->id }}" type="submit" onclick="return confirm('Remove widget?')">Remove</button>
                        </div>
                    </form>
                </details>
                <form id="delete-widget-{{ $widget->id }}" method="POST" action="{{ route('admin.appearance.widgets.destroy', $widget) }}" class="d-none">@csrf @method('DELETE')</form>
            @empty
                <div class="empty-state">No widgets yet. Add one from the left.</div>
            @endforelse

            @if ($sidebar->widgets->isNotEmpty())
                <form method="POST" action="{{ route('admin.appearance.widgets.reorder') }}" class="mt-3">
                    @csrf
                    <label class="form-label">Order (top to bottom, comma-separated IDs)</label>
                    <input name="order_raw" class="form-control mb-2" value="{{ $sidebar->widgets->pluck('id')->join(',') }}"
                           oninput="this.form.querySelector('#order-fields').innerHTML = this.value.split(',').map((id,i)=>`<input type=hidden name='order[${i}]' value='${id.trim()}'>`).join('')">
                    <div id="order-fields">
                        @foreach ($sidebar->widgets as $i => $widget)
                            <input type="hidden" name="order[{{ $i }}]" value="{{ $widget->id }}">
                        @endforeach
                    </div>
                    <button class="btn btn-outline-secondary btn-sm" type="submit">Save order</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('title', __('admin.nav.theme_colors'))

@section('content')
@php
    $colorLabels = __('admin.appearance.colors_labels');
    if (! is_array($colorLabels)) {
        $colorLabels = [];
    }
    $ui = [
        'saving' => __('admin.appearance.colors_saving'),
        'save' => __('admin.appearance.colors_save'),
        'saved' => __('admin.appearance.colors_saved_inline'),
        'preview_title' => __('admin.appearance.colors_preview_title'),
        'preview_body' => __('admin.appearance.colors_preview_body'),
        'btn_primary' => __('admin.appearance.colors_btn_primary'),
        'btn_secondary' => __('admin.appearance.colors_btn_secondary'),
        'btn_accent' => __('admin.appearance.colors_btn_accent'),
        'badge_success' => __('admin.appearance.colors_badge_success'),
        'badge_warning' => __('admin.appearance.colors_badge_warning'),
        'badge_danger' => __('admin.appearance.colors_badge_danger'),
        'badge_info' => __('admin.appearance.colors_badge_info'),
        'surface' => __('admin.appearance.colors_surface_panel'),
    ];
@endphp
<div x-data="themeCustomizer(@js($colors), @js($colorLabels), @js($ui))" x-cloak>
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">{{ __('admin.nav.theme_colors') }}</h1>
            <p class="text-muted mb-0">{{ __('admin.appearance.colors_intro') }}</p>
        </div>
        <form method="POST" action="{{ route('admin.appearance.colors.reset') }}">@csrf
            <button class="btn btn-outline-secondary" type="submit">{{ __('admin.appearance.colors_reset') }}</button>
        </form>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <x-admin.help-next context="colors" />

    <form method="POST" action="{{ route('admin.appearance.colors.update') }}" class="row g-4" @submit.prevent="save">
        @csrf
        @method('PUT')
        <div class="col-lg-5">
            <template x-for="(value, key) in colors" :key="key">
                <div class="mb-3">
                    <label class="form-label" x-text="labels[key] || key"></label>
                    <div class="d-flex gap-2">
                        <input type="color" class="form-control form-control-color" :value="value" @input="colors[key] = $event.target.value; applyPreview()">
                        <input type="text" class="form-control" :name="key + '_color'" x-model="colors[key]" @input="applyPreview()" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" required>
                    </div>
                </div>
            </template>
            <button class="btn btn-primary" type="submit" :disabled="saving" x-text="saving ? ui.saving : ui.save"></button>
            <p class="small text-success mt-2" x-show="saved" x-transition x-text="ui.saved"></p>
        </div>
        <div class="col-lg-7">
            <div class="border rounded p-4" :style="`background:${colors.background};color:${colors.text}`">
                <h2 class="h5 mb-2" x-text="ui.preview_title"></h2>
                <p class="mb-3" x-text="ui.preview_body"></p>
                <button type="button" class="btn me-2" :style="`background:${colors.primary};border-color:${colors.primary};color:#fff`" x-text="ui.btn_primary"></button>
                <button type="button" class="btn me-2" :style="`background:${colors.secondary};border-color:${colors.secondary};color:#fff`" x-text="ui.btn_secondary"></button>
                <button type="button" class="btn" :style="`background:${colors.accent};border-color:${colors.accent};color:#fff`" x-text="ui.btn_accent"></button>
                <div class="mt-3 d-flex gap-2 flex-wrap">
                    <span class="badge" :style="`background:${colors.success}`" x-text="ui.badge_success"></span>
                    <span class="badge" :style="`background:${colors.warning}`" x-text="ui.badge_warning"></span>
                    <span class="badge" :style="`background:${colors.danger}`" x-text="ui.badge_danger"></span>
                    <span class="badge" :style="`background:${colors.info}`" x-text="ui.badge_info"></span>
                </div>
                <div class="mt-3 p-3 rounded" :style="`background:${colors.surface};border:1px solid #e2e8f0`" x-text="ui.surface"></div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function themeCustomizer(initial, labels, ui) {
    const colors = {};
    Object.keys(initial).forEach((k) => { colors[k] = initial[k]; });
    return {
        colors,
        labels: labels || {},
        ui: ui || {},
        saving: false,
        saved: false,
        applyPreview() {
            const root = document.documentElement;
            Object.entries(this.colors).forEach(([key, value]) => {
                root.style.setProperty('--color-' + key, value);
                root.style.setProperty('--bs-' + key, value);
            });
        },
        async save() {
            this.saving = true;
            this.saved = false;
            const token = document.querySelector('meta[name="csrf-token"]').content;
            const payload = {};
            Object.entries(this.colors).forEach(([key, value]) => {
                payload[`${key}_color`] = value;
            });
            try {
                const res = await fetch(@json(route('admin.appearance.colors.update')), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-HTTP-METHOD-OVERRIDE': 'PUT',
                    },
                    body: JSON.stringify({ ...payload, _method: 'PUT' }),
                });
                const json = await res.json();
                if (json.success) {
                    this.saved = true;
                    this.applyPreview();
                }
            } finally {
                this.saving = false;
            }
        }
    }
}
</script>
@endpush

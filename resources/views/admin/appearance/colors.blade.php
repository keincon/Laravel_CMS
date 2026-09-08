@extends('layouts.admin')

@section('title', __('admin.nav.theme_colors'))

@section('content')
<div x-data="themeCustomizer(@js($colors))" x-cloak>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">{{ __('admin.nav.theme_colors') }}</h1>
            <p class="text-muted mb-0">Runtime CSS variables — no NPM rebuild required.</p>
        </div>
        <form method="POST" action="{{ route('admin.appearance.colors.reset') }}">@csrf
            <button class="btn btn-outline-secondary" type="submit">Reset to default</button>
        </form>
    </div>

    <form method="POST" action="{{ route('admin.appearance.colors.update') }}" class="row g-4" @submit.prevent="save">
        @csrf
        @method('PUT')
        <div class="col-lg-5">
            <template x-for="(value, key) in colors" :key="key">
                <div class="mb-3">
                    <label class="form-label text-capitalize" x-text="key.replace('_', ' ')"></label>
                    <div class="d-flex gap-2">
                        <input type="color" class="form-control form-control-color" :value="value" @input="colors[key] = $event.target.value; applyPreview()">
                        <input type="text" class="form-control" :name="key + '_color'" x-model="colors[key]" @input="applyPreview()" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" required>
                    </div>
                </div>
            </template>
            <button class="btn btn-primary" type="submit" :disabled="saving" x-text="saving ? 'Saving…' : 'Save Changes'"></button>
            <p class="small text-success mt-2" x-show="saved" x-transition>Saved.</p>
        </div>
        <div class="col-lg-7">
            <div class="border rounded p-4" :style="`background:${colors.background};color:${colors.text}`">
                <h2 class="h5 mb-2">Website Preview</h2>
                <p class="mb-3">Heading and body text using theme variables.</p>
                <button type="button" class="btn me-2" :style="`background:${colors.primary};border-color:${colors.primary};color:#fff`">Primary Button</button>
                <button type="button" class="btn me-2" :style="`background:${colors.secondary};border-color:${colors.secondary};color:#fff`">Secondary</button>
                <button type="button" class="btn" :style="`background:${colors.accent};border-color:${colors.accent};color:#fff`">Accent</button>
                <div class="mt-3 d-flex gap-2 flex-wrap">
                    <span class="badge" :style="`background:${colors.success}`">Success</span>
                    <span class="badge" :style="`background:${colors.warning}`">Warning</span>
                    <span class="badge" :style="`background:${colors.danger}`">Danger</span>
                    <span class="badge" :style="`background:${colors.info}`">Info</span>
                </div>
                <div class="mt-3 p-3 rounded" :style="`background:${colors.surface};border:1px solid #e2e8f0`">
                    Surface panel
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function themeCustomizer(initial) {
    // Map primary_color keys from server into short keys for UI
    const colors = {};
    Object.keys(initial).forEach((k) => { colors[k] = initial[k]; });
    return {
        colors,
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
            const body = {};
            Object.entries(this.colors).forEach(([key, value]) => {
                body[key + '_color'] = value;
            });
            // colors already use primary, secondary keys — server expects primary_color
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

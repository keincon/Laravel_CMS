@props([
    'name' => 'featured_image_id',
    'value' => null,
    'label' => 'Select image',
])

@php
    $selected = $value ? \App\Models\Media::query()->find($value) : null;
    $selectedUrl = null;
    if ($selected) {
        $selectedUrl = ($selected->disk ?: 'public') === 'public'
            ? '/storage/'.ltrim((string) $selected->path, '/')
            : $selected->url();
    }
@endphp

<div
    class="media-picker"
    x-data="mediaPicker({
        name: @js($name),
        value: @js($value ? (int) $value : null),
        selectedUrl: @js($selectedUrl),
        selectedName: @js($selected?->alt ?: $selected?->filename),
        listUrl: @js(route('admin.media.json')),
        uploadUrl: @js(route('admin.media.store.json')),
        csrf: @js(csrf_token()),
    })"
>
    <input type="hidden" :name="name" x-model="value">
    <div class="media-picker-preview" x-show="value">
        <img :src="selectedUrl" :alt="selectedName" x-show="selectedUrl">
        <div class="small mt-1" x-text="selectedName"></div>
    </div>
    <div class="d-flex gap-2 flex-wrap mt-2">
        <button type="button" class="btn btn-sm btn-outline-secondary" @click="open = true; load()">{{ $label }}</button>
        <button type="button" class="btn btn-sm btn-outline-danger" x-show="value" @click="clear()">Remove</button>
    </div>

    <div class="media-modal" x-show="open" x-cloak @keydown.escape.window="open=false">
        <div class="media-modal-backdrop" @click="open=false"></div>
        <div class="media-modal-dialog panel" @click.stop>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 mb-0">Media Library</h2>
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="open=false">Close</button>
            </div>
            <div class="d-flex gap-2 mb-3 flex-wrap">
                <input type="search" class="form-control" style="max-width:220px" placeholder="Search…" x-model="q" @keydown.enter.prevent="load()">
                <button type="button" class="btn btn-outline-secondary" @click="load()">Search</button>
                <label class="btn btn-primary mb-0">
                    Upload
                    <input type="file" class="d-none" accept="image/*,.ico,.pdf,.mp4,.webm" @change="upload($event)">
                </label>
            </div>
            <div class="media-picker-grid">
                <template x-for="item in items" :key="item.id">
                    <button type="button" class="media-picker-item" :class="{ 'is-selected': value === item.id }" @click="choose(item)">
                        <template x-if="item.is_image">
                            <img :src="item.url" :alt="item.alt || item.filename">
                        </template>
                        <template x-if="!item.is_image">
                            <div class="media-picker-file" x-text="item.mime_type || 'file'"></div>
                        </template>
                        <span x-text="item.filename"></span>
                    </button>
                </template>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <button type="button" class="btn btn-sm btn-outline-secondary" :disabled="page <= 1" @click="page--; load()">Prev</button>
                <span class="small" x-text="'Page ' + page + ' / ' + lastPage"></span>
                <button type="button" class="btn btn-sm btn-outline-secondary" :disabled="page >= lastPage" @click="page++; load()">Next</button>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
function mediaPicker(config) {
    return {
        ...config,
        open: false,
        q: '',
        page: 1,
        lastPage: 1,
        items: [],
        async load() {
            const url = new URL(this.listUrl, window.location.origin);
            url.searchParams.set('page', this.page);
            if (this.q) url.searchParams.set('q', this.q);
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            this.items = json.data || [];
            this.lastPage = json.meta?.last_page || 1;
        },
        choose(item) {
            this.value = item.id;
            this.selectedUrl = item.url;
            this.selectedName = item.alt || item.filename;
            this.open = false;
        },
        clear() {
            this.value = null;
            this.selectedUrl = null;
            this.selectedName = null;
        },
        async upload(event) {
            const file = event.target.files?.[0];
            if (!file) return;
            const body = new FormData();
            body.append('file', file);
            body.append('_token', this.csrf);
            const res = await fetch(this.uploadUrl, { method: 'POST', body, headers: { 'Accept': 'application/json' } });
            if (!res.ok) { alert('Upload failed'); return; }
            const item = await res.json();
            this.choose(item);
            event.target.value = '';
        }
    }
}
</script>
@endpush
<style>
.media-picker-preview img { max-width: 100%; border-radius: .65rem; border: 1px solid var(--admin-border); }
.media-modal { position: fixed; inset: 0; z-index: 80; display: grid; place-items: center; }
.media-modal-backdrop { position: absolute; inset: 0; background: rgba(15,23,42,.45); }
.media-modal-dialog { position: relative; width: min(920px, 94vw); max-height: 86vh; overflow: auto; }
.media-picker-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: .65rem; }
.media-picker-item { border: 1px solid var(--admin-border); border-radius: .65rem; background: var(--admin-elevated); padding: .4rem; text-align: left; cursor: pointer; color: var(--admin-text); }
.media-picker-item img, .media-picker-file { width: 100%; height: 84px; object-fit: cover; border-radius: .45rem; display: grid; place-items: center; background: var(--admin-hover); font-size: .72rem; }
.media-picker-item span { display: block; margin-top: .35rem; font-size: .72rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.media-picker-item.is-selected { border-color: var(--admin-primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--admin-primary) 25%, transparent); }
[x-cloak] { display: none !important; }
</style>
@endonce

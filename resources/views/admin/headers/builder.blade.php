@extends('layouts.admin')
@section('title', 'Header Builder')
@section('content')
<div x-data="headerBuilder(@js($structure), @js($available))" x-cloak>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">Header Builder</h1>
            <p class="text-muted mb-0">{{ $header->name }} · status: <strong>{{ $header->status }}</strong></p>
        </div>
        <a href="{{ route('admin.headers.index') }}" class="btn btn-outline-secondary">← Back</a>
    </div>

    <form method="POST" action="{{ route('admin.headers.update', $header) }}">
        @csrf @method('PUT')
        <input type="hidden" name="structure" :value="JSON.stringify(structure)">
        <div class="mb-3">
            <label class="form-label">Header name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $header->name) }}" required>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="border rounded p-3 bg-white mb-3">
                    <h2 class="h6">Live canvas</h2>
                    <template x-for="(row, ri) in structure.rows" :key="row.id">
                        <div class="border rounded p-2 mb-2" :class="row.enabled ? '' : 'opacity-50'">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong x-text="row.label || row.id"></strong>
                                <label class="small mb-0"><input type="checkbox" x-model="row.enabled"> Enabled</label>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <template x-for="(comp, ci) in row.components" :key="comp.id">
                                    <div class="badge text-bg-light border p-2" style="cursor:grab" draggable="true"
                                         @dragstart="dragStart(ri, ci)"
                                         @dragover.prevent
                                         @drop="dropOn(ri, ci)">
                                        <span x-text="comp.type"></span>
                                        <button type="button" class="btn btn-sm btn-link" @click="comp.enabled = !comp.enabled" x-text="comp.enabled ? 'on' : 'off'"></button>
                                        <button type="button" class="btn btn-sm btn-link text-danger" @click="row.components.splice(ci,1)">×</button>
                                    </div>
                                </template>
                            </div>
                            <div class="mt-2">
                                <select class="form-select form-select-sm d-inline-block w-auto" x-model="row._add">
                                    <template x-for="(label, type) in available" :key="type">
                                        <option :value="type" x-text="label"></option>
                                    </template>
                                </select>
                                <button type="button" class="btn btn-sm btn-outline-primary" @click="addComponent(ri)">Add</button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="border rounded p-3 bg-white">
                    <h2 class="h6">Style</h2>
                    <div class="row g-2">
                        <div class="col-md-4"><label class="form-label">Background</label><input class="form-control" x-model="structure.style.background"></div>
                        <div class="col-md-4"><label class="form-label">Text color</label><input class="form-control" x-model="structure.style.text_color"></div>
                        <div class="col-md-4"><label class="form-label">Height</label><input class="form-control" x-model="structure.style.height"></div>
                        <div class="col-md-4 form-check mt-4"><input class="form-check-input" type="checkbox" x-model="structure.style.sticky" id="sticky"><label class="form-check-label" for="sticky">Sticky header</label></div>
                        <div class="col-md-4 form-check mt-4"><input class="form-check-input" type="checkbox" x-model="structure.style.transparent" id="transparent"><label class="form-check-label" for="transparent">Transparent</label></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="border rounded p-3 bg-white mb-3">
                    <h2 class="h6">Available components</h2>
                    <ul class="list-unstyled mb-0">
                        <template x-for="(label, type) in available" :key="type">
                            <li class="mb-1" x-text="label"></li>
                        </template>
                    </ul>
                </div>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-secondary" type="submit" name="action" value="draft">Save Draft</button>
                    <button class="btn btn-primary" type="submit" name="action" value="publish">Publish</button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function headerBuilder(structure, available) {
    return {
        structure,
        available,
        drag: null,
        addComponent(ri) {
            const type = this.structure.rows[ri]._add || 'text';
            this.structure.rows[ri].components.push({
                id: 'c' + Math.random().toString(36).slice(2, 8),
                type,
                enabled: true,
                settings: type === 'button' ? {label: 'Button', url: '#'} : (type === 'navigation' ? {menu: 'primary'} : {})
            });
        },
        dragStart(ri, ci) { this.drag = {ri, ci}; },
        dropOn(ri, ci) {
            if (!this.drag) return;
            const item = this.structure.rows[this.drag.ri].components.splice(this.drag.ci, 1)[0];
            this.structure.rows[ri].components.splice(ci, 0, item);
            this.drag = null;
        }
    }
}
</script>
@endpush

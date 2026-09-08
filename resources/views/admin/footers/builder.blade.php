@extends('layouts.admin')
@section('title', __('admin.appearance.footer_builder'))
@section('content')
<div x-data="footerBuilder(@js($structure), @js($available))" x-cloak>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">{{ __('admin.appearance.footer_builder') }}</h1>
            <p class="text-muted mb-0">{{ $footer->name }} · <strong>{{ $footer->status }}</strong></p>
        </div>
        <a href="{{ route('admin.footers.index') }}" class="btn btn-outline-secondary">← Back</a>
    </div>

    <form method="POST" action="{{ route('admin.footers.update', $footer) }}">
        @csrf @method('PUT')
        <input type="hidden" name="structure" :value="JSON.stringify(structure)">
        <div class="mb-3">
            <label class="form-label">Footer name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $footer->name) }}" required>
        </div>

        <div class="d-flex gap-2 mb-3">
            <button type="button" class="btn btn-sm btn-outline-primary" @click="addColumn">Add column</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="structure.columns.pop()" :disabled="structure.columns.length < 2">Remove column</button>
        </div>

        <div class="row g-3 mb-3">
            <template x-for="(col, ci) in structure.columns" :key="col.id">
                <div class="col-md-6 col-xl-3">
                    <div class="border rounded p-3 bg-white h-100">
                        <div class="fw-semibold mb-2">Column <span x-text="ci+1"></span></div>
                        <template x-for="(comp, ki) in col.components" :key="comp.id">
                            <div class="border rounded p-2 mb-2 d-flex justify-content-between align-items-center">
                                <span x-text="comp.type"></span>
                                <button type="button" class="btn btn-sm btn-link text-danger" @click="col.components.splice(ki,1)">×</button>
                            </div>
                        </template>
                        <select class="form-select form-select-sm mb-2" x-model="col._add">
                            <template x-for="(label, type) in available" :key="type">
                                <option :value="type" x-text="label"></option>
                            </template>
                        </select>
                        <button type="button" class="btn btn-sm btn-outline-primary" @click="addToColumn(ci)">Add</button>
                    </div>
                </div>
            </template>
        </div>

        <div class="border rounded p-3 bg-white mb-3">
            <h2 class="h6">Bottom bar</h2>
            <template x-for="(comp, i) in structure.bottom" :key="comp.id">
                <div class="d-flex justify-content-between border rounded p-2 mb-2">
                    <span x-text="comp.type"></span>
                    <button type="button" class="btn btn-sm btn-link text-danger" @click="structure.bottom.splice(i,1)">×</button>
                </div>
            </template>
            <button type="button" class="btn btn-sm btn-outline-primary" @click="structure.bottom.push({id:'b'+Math.random().toString(36).slice(2,7), type:'copyright', enabled:true, settings:{text:'© {year} {site}'}})">Add copyright</button>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" type="submit" name="action" value="draft">Save Draft</button>
            <button class="btn btn-primary" type="submit" name="action" value="publish">Publish</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function footerBuilder(structure, available) {
    return {
        structure, available,
        addColumn() {
            this.structure.columns.push({ id: 'col'+Math.random().toString(36).slice(2,6), components: [] });
        },
        addToColumn(ci) {
            const type = this.structure.columns[ci]._add || 'text';
            this.structure.columns[ci].components.push({
                id: 'f'+Math.random().toString(36).slice(2,7),
                type, enabled: true,
                settings: type === 'menu' ? {menu:'primary', title:'Links'} : (type === 'text' ? {text:''} : {})
            });
        }
    }
}
</script>
@endpush

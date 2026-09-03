@extends('layouts.admin')
@section('title', $taxonomy->plural_label)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-1">{{ $taxonomy->plural_label }}</h1>
        <p class="page-intro mb-0"><a href="{{ route('admin.taxonomies.index') }}">All taxonomies</a></p>
    </div>
</div>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel">
            <h2 class="h6">Add term</h2>
            <form method="POST" action="{{ route('admin.taxonomies.terms.store', $taxonomy) }}">
                @csrf
                <label class="form-label">Name</label>
                <input class="form-control" name="name" required>
                <label class="form-label mt-2">Slug</label>
                <input class="form-control" name="slug">
                <label class="form-label mt-2">Description</label>
                <textarea class="form-control" name="description" rows="3"></textarea>
                @if ($taxonomy->hierarchical)
                    <label class="form-label mt-2">Parent</label>
                    <select class="form-select" name="parent_id">
                        <option value="">— None —</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                        @endforeach
                    </select>
                @endif
                <button class="btn btn-primary mt-3" type="submit">Add Term</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel">
            <table class="table mb-0">
                <thead><tr><th>Name</th><th>Slug</th><th></th></tr></thead>
                <tbody>
                    @forelse ($terms as $term)
                        <tr>
                            <td>
                                {{ $term->name }}
                                @if ($term->parent) <span class="text-muted small">(child of {{ $term->parent->name }})</span> @endif
                            </td>
                            <td><code>{{ $term->slug }}</code></td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.taxonomies.terms.destroy', [$taxonomy, $term]) }}" onsubmit="return confirm('Delete term?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">No terms yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-3">{{ $terms->links() }}</div>
        </div>
    </div>
</div>
@endsection

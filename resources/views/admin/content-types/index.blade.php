@extends('layouts.admin')
@section('title', 'Content Types')
@section('content')
<div class="mb-3">
    <h1 class="h3 mb-1">Content Types</h1>
    <p class="page-intro mb-0">Register custom types (news, product, event, …).</p>
</div>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel">
            <h2 class="h6">Add type</h2>
            <form method="POST" action="{{ route('admin.content-types.store') }}">
                @csrf
                <label class="form-label">Name</label>
                <input class="form-control" name="name" required>
                <label class="form-label mt-2">Slug</label>
                <input class="form-control" name="slug" placeholder="news">
                <label class="form-label mt-2">Singular label</label>
                <input class="form-control" name="singular_label" required>
                <label class="form-label mt-2">Plural label</label>
                <input class="form-control" name="plural_label" required>
                <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="hierarchical" value="1"><label class="form-check-label">Hierarchical</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="has_archive" value="1" checked><label class="form-check-label">Has archive</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="public" value="1" checked><label class="form-check-label">Public</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="show_in_rest" value="1" checked><label class="form-check-label">Show in REST</label></div>
                @foreach (['title','editor','excerpt','author','featured_image','comments','revisions','custom_fields','hierarchy','archive'] as $support)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="supports[]" value="{{ $support }}" @checked(in_array($support, ['title','editor','excerpt','author','revisions'], true))>
                        <label class="form-check-label">{{ $support }}</label>
                    </div>
                @endforeach
                <button class="btn btn-primary mt-3" type="submit">Create</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel">
            <table class="table mb-0">
                <thead><tr><th>Type</th><th>Slug</th><th>Supports</th><th></th></tr></thead>
                <tbody>
                    @foreach ($types as $type)
                        <tr>
                            <td>
                                {{ $type->plural_label }}
                                @if ($type->is_builtin) <span class="badge text-bg-secondary">builtin</span> @endif
                            </td>
                            <td><code>{{ $type->slug }}</code></td>
                            <td class="small">{{ implode(', ', $type->supports ?? []) }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.contents.index', ['type' => $type->slug]) }}">Open</a>
                                @unless ($type->is_builtin)
                                    <form class="d-inline" method="POST" action="{{ route('admin.content-types.destroy', $type) }}" onsubmit="return confirm('Delete type?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')
@section('title', 'Redirects')
@section('content')
<div class="mb-3">
    <h1 class="h3 mb-1">Redirects</h1>
    <p class="page-intro mb-0">Manage 301/302/307/308 redirects.</p>
</div>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel">
            <h2 class="h6">Add redirect</h2>
            <form method="POST" action="{{ route('admin.redirects.store') }}">
                @csrf
                <label class="form-label">From path</label>
                <input class="form-control" name="from_path" placeholder="/old-url" required>
                <label class="form-label mt-2">To path / URL</label>
                <input class="form-control" name="to_path" placeholder="/new-url" required>
                <label class="form-label mt-2">Status</label>
                <select class="form-select" name="status_code">
                    @foreach ([301,302,307,308] as $code)
                        <option value="{{ $code }}">{{ $code }}</option>
                    @endforeach
                </select>
                <label class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                    <span class="form-check-label">Active</span>
                </label>
                <button class="btn btn-primary mt-3" type="submit">Create</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel">
            <table class="table mb-0">
                <thead><tr><th>From</th><th>To</th><th>Code</th><th>Active</th><th></th></tr></thead>
                <tbody>
                    @forelse ($redirects as $redirect)
                        <tr>
                            <td><code>{{ $redirect->from_path }}</code></td>
                            <td><code>{{ $redirect->to_path }}</code></td>
                            <td>{{ $redirect->status_code }}</td>
                            <td>{{ $redirect->is_active ? 'Yes' : 'No' }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.redirects.destroy', $redirect) }}" onsubmit="return confirm('Delete?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No redirects.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-3">{{ $redirects->links() }}</div>
        </div>
    </div>
</div>
@endsection

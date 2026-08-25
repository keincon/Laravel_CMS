@extends('layouts.admin')

@section('title', 'API Tokens')

@section('content')
<h1 class="h3 mb-2">API Tokens</h1>
<p class="text-muted mb-4">Personal access tokens for external applications. Secrets are shown once at creation.</p>

@if ($plainTextToken)
    <x-ui.alert type="success">
        <strong>New token (copy now):</strong>
        <code class="d-block mt-2 p-2 bg-dark text-white rounded">{{ $plainTextToken }}</code>
    </x-ui.alert>
@endif

<form method="POST" action="{{ route('admin.users.tokens.store') }}" class="row g-2 align-items-end mb-4">
    @csrf
    <div class="col-md-6">
        <label class="form-label">Token Name</label>
        <input type="text" name="name" class="form-control" required placeholder="Mobile app, CI, etc.">
    </div>
    <div class="col-md-3">
        <button class="btn btn-primary" type="submit">Create Token</button>
    </div>
</form>

<div class="table-responsive">
    <table class="table bg-white align-middle">
        <thead>
            <tr>
                <th>Token Name</th>
                <th>Created</th>
                <th>Last Used</th>
                <th>Expires</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tokens as $token)
                <tr>
                    <td>{{ $token->name }}</td>
                    <td>{{ $token->created_at?->toDayDateTimeString() }}</td>
                    <td>{{ $token->last_used_at?->diffForHumans() ?? '—' }}</td>
                    <td>{{ $token->expires_at?->toDayDateTimeString() ?? 'Never' }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.users.tokens.destroy', $token->id) }}" onsubmit="return confirm('Revoke this token?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Revoke</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">No tokens yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

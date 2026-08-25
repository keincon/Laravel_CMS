@extends('layouts.admin')

@section('title', 'API')

@section('content')
<h1 class="h3 mb-2">API Documentation</h1>
<p class="text-muted mb-4">Versioned REST API for public content and authenticated administration.</p>

<div class="mb-4 p-3 border rounded bg-white">
    <div class="small text-muted">Base URL</div>
    <code>{{ $baseUrl }}</code>
</div>

<div class="mb-4 p-3 border rounded bg-white">
    <h2 class="h6">Authentication</h2>
    <p class="mb-2">Admin endpoints require a Bearer personal access token:</p>
    <pre class="bg-dark text-white p-3 rounded small mb-2">Authorization: Bearer YOUR_TOKEN</pre>
    <p class="mb-0"><a href="{{ route('admin.users.tokens') }}">Create / revoke API tokens →</a></p>
</div>

<div class="table-responsive">
    <table class="table table-sm align-middle bg-white">
        <thead>
            <tr>
                <th>Method</th>
                <th>Endpoint</th>
                <th>Auth</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($endpoints as $endpoint)
                <tr>
                    <td><code>{{ $endpoint['method'] }}</code></td>
                    <td><code>{{ $baseUrl }}{{ $endpoint['path'] }}</code></td>
                    <td>{{ $endpoint['auth'] }}</td>
                    <td>{{ $endpoint['desc'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="p-3 border rounded bg-white">
    <h2 class="h6">Pagination & filters</h2>
    <p class="mb-1"><code>?page=1&amp;per_page=20</code> (max 100)</p>
    <p class="mb-0"><code>/posts?category=technology&amp;tag=laravel&amp;author=1&amp;search=postgresql</code></p>
</div>
@endsection

@extends('layouts.admin')

@section('title', __('admin.nav.api'))

@section('content')
<h1 class="h3 mb-2">{{ __('admin.settings.api_title') }}</h1>
<p class="text-muted mb-4">{{ __('admin.api.intro') }}</p>

<div class="mb-4 p-3 border rounded bg-white">
    <div class="small text-muted">{{ __('admin.api.base_url') }}</div>
    <code>{{ $baseUrl }}</code>
</div>

<div class="mb-4 p-3 border rounded bg-white">
    <h2 class="h6">{{ __('admin.api.cors_heading') }}</h2>
    <p class="mb-2">{!! __('admin.api.cors_body') !!}</p>
    <p class="mb-0"><a href="{{ route('admin.settings.cors') }}">{{ __('admin.api.cors_link') }}</a></p>
</div>

<div class="mb-4 p-3 border rounded bg-white">
    <h2 class="h6">{{ __('admin.api.auth_heading') }}</h2>
    <p class="mb-2">{{ __('admin.api.auth_body') }}</p>
    <pre class="bg-dark text-white p-3 rounded small mb-2">Authorization: Bearer YOUR_TOKEN</pre>
    <p class="mb-0"><a href="{{ route('admin.users.tokens') }}">{{ __('admin.api.tokens_link') }}</a></p>
</div>

<div class="table-responsive">
    <table class="table table-sm align-middle bg-white">
        <thead>
            <tr>
                <th>{{ __('admin.api.col_method') }}</th>
                <th>{{ __('admin.api.col_endpoint') }}</th>
                <th>{{ __('admin.api.col_auth') }}</th>
                <th>{{ __('admin.api.col_description') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($endpoints as $endpoint)
                <tr>
                    <td><code>{{ $endpoint['method'] }}</code></td>
                    <td><code>{{ $baseUrl }}{{ $endpoint['path'] }}</code></td>
                    <td>{{ __('admin.api.auth.'.$endpoint['auth']) }}</td>
                    <td>{{ __('admin.api.endpoints.'.$endpoint['desc']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="p-3 border rounded bg-white">
    <h2 class="h6">{{ __('admin.api.pagination_heading') }}</h2>
    <p class="mb-1"><code>?page=1&amp;per_page=20</code> (max 100)</p>
    <p class="mb-0"><code>/posts?category=technology&amp;tag=laravel&amp;author=1&amp;search=postgresql</code></p>
</div>
@endsection

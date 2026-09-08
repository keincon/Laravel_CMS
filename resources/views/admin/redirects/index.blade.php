@extends('layouts.admin')
@section('title', __('admin.nav.redirects'))
@section('content')
<div class="mb-3">
    <h1 class="h3 mb-1">{{ __('admin.nav.redirects') }}</h1>
    <p class="page-intro mb-0">{{ __('admin.redirects.intro') }}</p>
</div>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel">
            <h2 class="h6">{{ __('admin.redirects.add') }}</h2>
            <form method="POST" action="{{ route('admin.redirects.store') }}">
                @csrf
                <label class="form-label">{{ __('admin.redirects.from_path') }}</label>
                <input class="form-control" name="from_path" placeholder="/old-url" required>
                <label class="form-label mt-2">{{ __('admin.redirects.to_path') }}</label>
                <input class="form-control" name="to_path" placeholder="/new-url" required>
                <label class="form-label mt-2">{{ __('admin.ui.status') }}</label>
                <select class="form-select" name="status_code">
                    @foreach ([301,302,307,308] as $code)
                        <option value="{{ $code }}">{{ $code }}</option>
                    @endforeach
                </select>
                <label class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                    <span class="form-check-label">{{ __('admin.ui.active') }}</span>
                </label>
                <button class="btn btn-primary mt-3" type="submit">{{ __('admin.ui.create') }}</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>{{ __('admin.ui.from') }}</th>
                        <th>{{ __('admin.ui.to') }}</th>
                        <th>{{ __('admin.ui.code') }}</th>
                        <th>{{ __('admin.ui.active') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($redirects as $redirect)
                        <tr>
                            <td><code>{{ $redirect->from_path }}</code></td>
                            <td><code>{{ $redirect->to_path }}</code></td>
                            <td>{{ $redirect->status_code }}</td>
                            <td>{{ $redirect->is_active ? __('admin.ui.yes') : __('admin.ui.no') }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.redirects.destroy', $redirect) }}" onsubmit="return confirm(@js(__('admin.ui.confirm_delete')))">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">{{ __('admin.ui.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">{{ __('admin.redirects.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-3">{{ $redirects->links() }}</div>
        </div>
    </div>
</div>
@endsection

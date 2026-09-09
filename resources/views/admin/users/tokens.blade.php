@extends('layouts.admin')

@section('title', __('admin.nav.api_tokens'))

@section('content')
<h1 class="h3 mb-2">{{ __('admin.users.tokens_title') }}</h1>
<p class="text-muted mb-4">{{ __('admin.users.tokens_intro') }}</p>

@if ($plainTextToken)
    <x-ui.alert type="success">
        <strong>{{ __('admin.tokens.new_token') }}</strong>
        <code class="d-block mt-2 p-2 bg-dark text-white rounded">{{ $plainTextToken }}</code>
    </x-ui.alert>
@endif

<form method="POST" action="{{ route('admin.users.tokens.store') }}" class="row g-2 align-items-end mb-4">
    @csrf
    <div class="col-md-6">
        <label class="form-label" for="token_name">{{ __('admin.tokens.name') }}</label>
        <input id="token_name" type="text" name="name" class="form-control" required placeholder="{{ __('admin.tokens.name_placeholder') }}">
    </div>
    <div class="col-md-3">
        <button class="btn btn-primary" type="submit">{{ __('admin.tokens.create') }}</button>
    </div>
</form>

<div class="table-responsive">
    <table class="table bg-white align-middle">
        <thead>
            <tr>
                <th>{{ __('admin.tokens.name') }}</th>
                <th>{{ __('admin.tokens.created') }}</th>
                <th>{{ __('admin.tokens.last_used') }}</th>
                <th>{{ __('admin.tokens.expires') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tokens as $token)
                <tr>
                    <td>{{ $token->name }}</td>
                    <td>{{ $token->created_at?->toDayDateTimeString() }}</td>
                    <td>{{ $token->last_used_at?->diffForHumans() ?? '—' }}</td>
                    <td>{{ $token->expires_at?->toDayDateTimeString() ?? __('admin.tokens.never') }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.users.tokens.destroy', $token->id) }}" onsubmit="return confirm(@js(__('admin.tokens.revoke_confirm')))">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">{{ __('admin.tokens.revoke') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">{{ __('admin.tokens.empty') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

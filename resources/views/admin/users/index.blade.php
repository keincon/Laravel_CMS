@extends('layouts.admin')

@section('title', __('admin.users.title'))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <p class="page-intro mb-0">{{ __('admin.users.intro') }}</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('admin.users.roles') }}">{{ __('admin.users.roles') }}</a>
        <a class="btn btn-primary" href="{{ route('admin.users.create') }}">{{ __('admin.users.add_new') }}</a>
    </div>
</div>

<div class="users-role-tabs mb-3">
    <a href="{{ route('admin.users.index', array_filter(['q' => $q ?: null])) }}" class="{{ $roleFilter === '' ? 'is-active' : '' }}">
        {{ __('admin.users.all') }} <span>({{ $roleCounts['all'] ?? 0 }})</span>
    </a>
    @foreach ($roles as $r)
        <a href="{{ route('admin.users.index', array_filter(['role' => $r->name, 'q' => $q ?: null])) }}" class="{{ $roleFilter === $r->name ? 'is-active' : '' }}">
            {{ $r->localizedName() }} <span>({{ $roleCounts[$r->name] ?? 0 }})</span>
        </a>
    @endforeach
</div>

<div class="panel mb-4">
    <form method="GET" action="{{ route('admin.users.index') }}" class="d-flex gap-2 flex-wrap align-items-center">
        @if ($roleFilter !== '')
            <input type="hidden" name="role" value="{{ $roleFilter }}">
        @endif
        <input type="search" name="q" value="{{ $q }}" class="form-control" style="max-width: 280px" placeholder="{{ __('admin.users.search_placeholder') }}">
        <button class="btn btn-outline-secondary" type="submit">{{ __('admin.users.search') }}</button>
        @if ($q !== '')
            <a class="btn btn-outline-secondary" href="{{ route('admin.users.index', array_filter(['role' => $roleFilter ?: null])) }}">{{ __('admin.users.clear') }}</a>
        @endif
    </form>
</div>

<form method="POST" action="{{ route('admin.users.bulk') }}" id="users-bulk-form">
    @csrf
    <div class="panel mb-3">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <select name="action" class="form-select" style="max-width: 200px" required>
                <option value="">{{ __('admin.users.bulk_actions') }}</option>
                <option value="change_role">{{ __('admin.users.change_role') }}</option>
                <option value="delete">{{ __('admin.users.delete') }}</option>
            </select>
            <select name="role" class="form-select" style="max-width: 200px">
                <option value="">{{ __('admin.users.role_placeholder') }}</option>
                @foreach ($roles as $r)
                    <option value="{{ $r->name }}">{{ $r->localizedName() }}</option>
                @endforeach
            </select>
            <button class="btn btn-outline-secondary" type="submit">{{ __('admin.users.apply') }}</button>
        </div>

        @if ($users->isEmpty())
            <div class="empty-state">{{ __('admin.users.no_users') }}</div>
        @else
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th style="width:2rem"><input type="checkbox" onclick="document.querySelectorAll('.user-check').forEach(c => c.checked = this.checked)"></th>
                            <th>{{ __('admin.users.username') }}</th>
                            <th>{{ __('admin.users.name') }}</th>
                            <th>{{ __('admin.users.email') }}</th>
                            <th>{{ __('admin.users.role') }}</th>
                            <th>{{ __('admin.users.posts') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>
                                    @if ($user->id !== auth()->id())
                                        <input class="user-check" type="checkbox" name="users[]" value="{{ $user->id }}">
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.users.edit', $user) }}" class="fw-semibold">{{ $user->username ?: $user->email }}</a>
                                    <div class="row-actions small mt-1">
                                        <a href="{{ route('admin.users.edit', $user) }}">{{ __('admin.users.edit_action') }}</a>
                                        @if ($user->id !== auth()->id())
                                            ·
                                            <button form="delete-user-{{ $user->id }}" class="link-danger" type="submit" onclick="return confirm(@js(__('admin.users.delete_confirm')))">{{ __('admin.users.delete') }}</button>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @forelse ($user->roles as $role)
                                        <span class="badge text-bg-primary">{{ $role->localizedName() }}</span>
                                    @empty
                                        <span class="badge text-bg-secondary">{{ __('admin.users.none') }}</span>
                                    @endforelse
                                </td>
                                <td>{{ $user->posts()->count() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $users->links() }}</div>
        @endif
    </div>
</form>

@foreach ($users as $user)
    @if ($user->id !== auth()->id())
        <form id="delete-user-{{ $user->id }}" method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    @endif
@endforeach
@endsection

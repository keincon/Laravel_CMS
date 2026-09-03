@extends('layouts.admin')

@section('title', 'Edit User')

@section('content')
<p class="page-intro mb-4">Update profile, password, and role for <strong>{{ $user->username ?: $user->email }}</strong>.</p>

<form method="POST" action="{{ route('admin.users.update', $user) }}" class="settings-form panel">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label class="form-label" for="username">Username</label>
        <input id="username" class="form-control" name="username" value="{{ old('username', $user->username) }}" required>
        @error('username')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input id="email" type="email" class="form-control" name="email" value="{{ old('email', $user->email) }}" required>
        @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label" for="name">Display name</label>
        <input id="name" class="form-control" name="name" value="{{ old('name', $user->name) }}" required>
        @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label" for="role">Role</label>
        <select id="role" name="role" class="form-select" required>
            @foreach ($roles as $role)
                <option value="{{ $role->name }}" @selected(old('role', $user->primaryRoleName()) === $role->name)>{{ $role->name }}</option>
            @endforeach
        </select>
        @error('role')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label" for="password">New password</label>
        <input id="password" type="password" class="form-control" name="password" autocomplete="new-password" placeholder="Leave blank to keep current">
        @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label" for="password_confirmation">Confirm new password</label>
        <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" autocomplete="new-password">
    </div>
    <div class="form-actions">
        <a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">Cancel</a>
        <button class="btn btn-primary" type="submit">Update User</button>
    </div>
</form>
@endsection

@extends('layouts.admin')

@section('title', 'Add New User')

@section('content')
<p class="page-intro mb-4">Create a user and assign a role.</p>

<form method="POST" action="{{ route('admin.users.store') }}" class="settings-form panel">
    @csrf
    <div class="mb-3">
        <label class="form-label" for="username">Username</label>
        <input id="username" class="form-control" name="username" value="{{ old('username') }}" required>
        @error('username')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required>
        @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label" for="name">Display name</label>
        <input id="name" class="form-control" name="name" value="{{ old('name') }}" required>
        @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label" for="role">Role</label>
        <select id="role" name="role" class="form-select" required>
            @foreach ($roles as $role)
                <option value="{{ $role->name }}" @selected(old('role', 'Subscriber') === $role->name)>{{ $role->name }}</option>
            @endforeach
        </select>
        @error('role')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label" for="password">Password</label>
        <input id="password" type="password" class="form-control" name="password" required autocomplete="new-password">
        @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label" for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
    </div>
    <div class="form-actions">
        <a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">Cancel</a>
        <button class="btn btn-primary" type="submit">Add New User</button>
    </div>
</form>
@endsection

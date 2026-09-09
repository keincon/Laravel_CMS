@extends('layouts.admin')

@section('title', __('admin.users.edit'))

@section('content')
<p class="page-intro mb-4">{{ __('admin.users.edit_intro') }} <strong>{{ $user->username ?: $user->email }}</strong></p>

<form method="POST" action="{{ route('admin.users.update', $user) }}" class="settings-form panel">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label class="form-label" for="username">{{ __('admin.users.username') }}</label>
        <input id="username" class="form-control" name="username" value="{{ old('username', $user->username) }}" required>
        @error('username')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label" for="email">{{ __('admin.users.email') }}</label>
        <input id="email" type="email" class="form-control" name="email" value="{{ old('email', $user->email) }}" required>
        @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label" for="name">{{ __('admin.users.display_name') }}</label>
        <input id="name" class="form-control" name="name" value="{{ old('name', $user->name) }}" required>
        @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label" for="role">{{ __('admin.users.role') }}</label>
        <select id="role" name="role" class="form-select" required>
            @foreach ($roles as $role)
                <option value="{{ $role->name }}" @selected(old('role', $user->primaryRoleName()) === $role->name)>{{ $role->localizedName() }}</option>
            @endforeach
        </select>
        @error('role')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label" for="password">{{ __('admin.users.new_password') }}</label>
        <input id="password" type="password" class="form-control" name="password" autocomplete="new-password" placeholder="{{ __('common.leave_blank') }}">
        @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label" for="password_confirmation">{{ __('admin.users.confirm_new_password') }}</label>
        <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" autocomplete="new-password">
    </div>
    <div class="form-actions">
        <a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">{{ __('admin.users.cancel') }}</a>
        <button class="btn btn-primary" type="submit">{{ __('admin.users.submit_update') }}</button>
    </div>
</form>
@endsection

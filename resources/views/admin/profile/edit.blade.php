@extends('layouts.admin')

@section('title', 'Profile')

@section('content')
<p class="page-intro mb-4">Your account details. Role changes are managed under Users.</p>
<form method="POST" action="{{ route('admin.profile.update') }}" class="settings-form panel">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label class="form-label">Display name</label>
        <input name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
        @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label">Username</label>
        <input name="username" class="form-control" value="{{ old('username', $user->username) }}" required>
        @error('username')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
        @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label">Role</label>
        <div>@forelse($user->roles as $role)<span class="badge text-bg-primary">{{ $role->name }}</span>@empty<span class="badge text-bg-secondary">None</span>@endforelse</div>
    </div>
    <div class="mb-3">
        <label class="form-label">New password</label>
        <input type="password" name="password" class="form-control" autocomplete="new-password" placeholder="Leave blank to keep current">
        @error('password')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label">Confirm password</label>
        <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
    </div>
    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Update Profile</button>
    </div>
</form>

<section class="panel settings-form" style="margin-top:1.5rem">
    <h2 style="font-size:1.1rem;margin-bottom:.75rem">Two-factor authentication</h2>
    @if ($user->hasTwoFactorEnabled())
        <p class="muted">2FA is enabled on this account.</p>
        <form method="POST" action="{{ route('admin.profile.two-factor.disable') }}">
            @csrf
            @method('DELETE')
            <div class="mb-3">
                <label class="form-label">Current password</label>
                <input type="password" name="password" class="form-control" required autocomplete="current-password">
                @error('password')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-outline-danger" type="submit">Disable 2FA</button>
        </form>
    @else
        @php($setup = $pendingTwoFactor ?? session('two_factor_setup'))
        @if ($setup)
            <p>Secret: <code>{{ $setup['secret'] }}</code></p>
            <p class="muted small">Add this otpauth URL in your authenticator app:</p>
            <p><code style="word-break:break-all">{{ $setup['otpauth_url'] }}</code></p>
            @if (!empty($setup['recovery_codes']))
                <p class="muted small">Recovery codes (store securely):</p>
                <ul>
                    @foreach ($setup['recovery_codes'] as $code)
                        <li><code>{{ $code }}</code></li>
                    @endforeach
                </ul>
            @endif
            <form method="POST" action="{{ route('admin.profile.two-factor.confirm') }}" class="mt-3">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Authentication code</label>
                    <input name="code" class="form-control" required autocomplete="one-time-code">
                    @error('code')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <button class="btn btn-primary" type="submit">Confirm 2FA</button>
            </form>
        @else
            <p class="muted">Protect your account with a TOTP authenticator app.</p>
            <form method="POST" action="{{ route('admin.profile.two-factor.enable') }}">
                @csrf
                <button class="btn btn-primary" type="submit">Enable 2FA</button>
            </form>
        @endif
    @endif
</section>
@endsection

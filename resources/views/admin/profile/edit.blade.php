@extends('layouts.admin')

@section('title', __('admin.profile.title'))

@section('content')
<p class="page-intro mb-4">{{ __('admin.profile.intro') }}</p>
<form method="POST" action="{{ route('admin.profile.update') }}" class="settings-form panel">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label class="form-label">{{ __('admin.profile.display_name') }}</label>
        <input name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
        @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label">{{ __('admin.profile.username') }}</label>
        <input name="username" class="form-control" value="{{ old('username', $user->username) }}" required>
        @error('username')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label">{{ __('admin.profile.email') }}</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
        @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label">{{ __('admin.profile.role') }}</label>
        <div>@forelse($user->roles as $role)<span class="badge text-bg-primary">{{ $role->name }}</span>@empty<span class="badge text-bg-secondary">{{ __('admin.profile.none') }}</span>@endforelse</div>
    </div>
    <div class="mb-3">
        <label class="form-label" for="locale">{{ __('admin.profile.locale') }}</label>
        <select id="locale" name="locale" class="form-select" required>
            @foreach (config('cms.ui_locales', ['en' => 'English', 'ja' => '日本語']) as $code => $label)
                <option value="{{ $code }}" @selected(old('locale', $user->locale ?: app()->getLocale()) === $code)>{{ $label }}</option>
            @endforeach
        </select>
        <p class="form-text mb-0">{{ __('admin.profile.locale_help') }}</p>
        @error('locale')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label">{{ __('admin.profile.new_password') }}</label>
        <input type="password" name="password" class="form-control" autocomplete="new-password" placeholder="{{ __('admin.profile.password_placeholder') }}">
        @error('password')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label">{{ __('admin.profile.confirm_password') }}</label>
        <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
    </div>
    <div class="form-actions">
        <button class="btn btn-primary" type="submit">{{ __('admin.profile.update') }}</button>
    </div>
</form>

<section class="panel settings-form" style="margin-top:1.5rem">
    <h2 style="font-size:1.1rem;margin-bottom:.75rem">{{ __('admin.profile.two_factor') }}</h2>
    @if ($user->hasTwoFactorEnabled())
        <p class="muted">{{ __('admin.profile.two_factor_enabled') }}</p>
        <form method="POST" action="{{ route('admin.profile.two-factor.disable') }}">
            @csrf
            @method('DELETE')
            <div class="mb-3">
                <label class="form-label">{{ __('admin.profile.current_password') }}</label>
                <input type="password" name="password" class="form-control" required autocomplete="current-password">
                @error('password')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-outline-danger" type="submit">{{ __('admin.profile.disable_2fa') }}</button>
        </form>
    @else
        @php($setup = $pendingTwoFactor ?? session('two_factor_setup'))
        @if ($setup)
            <p>{{ __('admin.profile.secret') }}: <code>{{ $setup['secret'] }}</code></p>
            <p class="muted small">{{ __('admin.profile.otpauth_help') }}</p>
            <p><code style="word-break:break-all">{{ $setup['otpauth_url'] }}</code></p>
            @if (!empty($setup['recovery_codes']))
                <p class="muted small">{{ __('admin.profile.recovery_codes') }}</p>
                <ul>
                    @foreach ($setup['recovery_codes'] as $code)
                        <li><code>{{ $code }}</code></li>
                    @endforeach
                </ul>
            @endif
            <form method="POST" action="{{ route('admin.profile.two-factor.confirm') }}" class="mt-3">
                @csrf
                <div class="mb-3">
                    <label class="form-label">{{ __('admin.profile.authentication_code') }}</label>
                    <input name="code" class="form-control" required autocomplete="one-time-code">
                    @error('code')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <button class="btn btn-primary" type="submit">{{ __('admin.profile.confirm_2fa') }}</button>
            </form>
        @else
            <p class="muted">{{ __('admin.profile.protect_account') }}</p>
            <form method="POST" action="{{ route('admin.profile.two-factor.enable') }}">
                @csrf
                <button class="btn btn-primary" type="submit">{{ __('admin.profile.enable_2fa') }}</button>
            </form>
        @endif
    @endif
</section>
@endsection

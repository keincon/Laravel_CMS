@extends('layouts.admin')

@section('title', __('admin.nav.mail'))

@section('content')
<h1 class="h3 mb-2">{{ __('admin.nav.mail') }}</h1>
<p class="text-muted mb-3">{{ __('admin.settings.mail_intro') }}</p>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<x-admin.help-next context="mail" />

<form method="POST" action="{{ route('admin.settings.mail.update') }}" class="settings-form panel mb-4" x-data="{ mailer: @js(old('mailer', $settings['mailer'])) }">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label class="form-label" for="mailer">{{ __('admin.settings.mail_mailer') }}</label>
        <select name="mailer" id="mailer" class="form-select" x-model="mailer" required>
            @foreach ($mailers as $option)
                <option value="{{ $option }}" @selected(old('mailer', $settings['mailer']) === $option)>
                    {{ __('admin.settings.mail_mailers.'.$option) }}
                </option>
            @endforeach
        </select>
        <div class="form-text">{{ __('admin.settings.mail_mailer_hint') }}</div>
    </div>

    <div class="row g-3" x-show="mailer === 'smtp'" x-cloak>
        <div class="col-md-8">
            <label class="form-label" for="host">{{ __('admin.settings.mail_host') }}</label>
            <input type="text" name="host" id="host" class="form-control" value="{{ old('host', $settings['host']) }}" maxlength="255" placeholder="smtp.example.com">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="port">{{ __('admin.settings.mail_port') }}</label>
            <input type="number" name="port" id="port" class="form-control" value="{{ old('port', $settings['port']) }}" min="1" max="65535">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="encryption">{{ __('admin.settings.mail_encryption') }}</label>
            <select name="encryption" id="encryption" class="form-select" required>
                @foreach ($encryptions as $option)
                    <option value="{{ $option }}" @selected(old('encryption', $settings['encryption']) === $option)>
                        {{ __('admin.settings.mail_encryptions.'.$option) }}
                    </option>
                @endforeach
            </select>
            <div class="form-text">{{ __('admin.settings.mail_encryption_hint') }}</div>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="username">{{ __('admin.settings.mail_username') }}</label>
            <input type="text" name="username" id="username" class="form-control" value="{{ old('username', $settings['username']) }}" maxlength="255" autocomplete="off">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="password">{{ __('admin.settings.mail_password') }}</label>
            <input type="password" name="password" id="password" class="form-control" value="" maxlength="500" autocomplete="new-password" placeholder="{{ $settings['password_set'] ? __('admin.settings.mail_password_kept') : '' }}">
            <div class="form-text">{{ __('admin.settings.mail_password_hint') }}</div>
            @if ($settings['password_set'])
                <label class="capability-item mt-2 mb-0">
                    <input type="checkbox" name="clear_password" value="1">
                    <span><strong>{{ __('admin.settings.mail_clear_password') }}</strong></span>
                </label>
            @endif
        </div>
    </div>

    <hr class="my-4">

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="from_address">{{ __('admin.settings.mail_from_address') }}</label>
            <input type="email" name="from_address" id="from_address" class="form-control" value="{{ old('from_address', $settings['from_address']) }}" required maxlength="255">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="from_name">{{ __('admin.settings.mail_from_name') }}</label>
            <input type="text" name="from_name" id="from_name" class="form-control" value="{{ old('from_name', $settings['from_name']) }}" required maxlength="255">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="reply_to">{{ __('admin.settings.mail_reply_to') }}</label>
            <input type="email" name="reply_to" id="reply_to" class="form-control" value="{{ old('reply_to', $settings['reply_to']) }}" maxlength="255">
            <div class="form-text">{{ __('admin.settings.mail_reply_to_hint') }}</div>
        </div>
    </div>

    <div class="form-actions mt-4">
        <button class="btn btn-primary" type="submit">{{ __('admin.settings.mail_save') }}</button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.settings.mail.templates') }}">{{ __('admin.nav.mail_templates') }}</a>
    </div>
</form>

<section class="panel">
    <header class="panel-head">
        <h2 class="h6 mb-0">{{ __('admin.settings.mail_test_title') }}</h2>
        <p class="panel-desc mb-0">{{ __('admin.settings.mail_test_intro') }}</p>
    </header>
    <div class="panel-body">
        <form method="POST" action="{{ route('admin.settings.mail.test') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-8">
                <label class="form-label" for="test_to">{{ __('admin.settings.mail_test_to') }}</label>
                <input type="email" name="test_to" id="test_to" class="form-control" value="{{ old('test_to') }}" required>
            </div>
            <div class="col-md-4">
                <button class="btn btn-outline-primary w-100" type="submit">{{ __('admin.settings.mail_test_send') }}</button>
            </div>
        </form>
    </div>
</section>
@endsection

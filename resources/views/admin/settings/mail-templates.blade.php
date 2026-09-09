@extends('layouts.admin')

@section('title', __('admin.nav.mail_templates'))

@section('content')
<h1 class="h3 mb-2">{{ __('admin.nav.mail_templates') }}</h1>
<p class="text-muted mb-3">{{ __('admin.settings.mail_templates_intro') }}</p>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<x-admin.help-next context="mail_templates" />

<div class="alert alert-secondary mb-4">
    <strong>{{ __('admin.settings.mail_templates_variables') }}:</strong>
    @foreach ($placeholders as $name)
        <code>{{ '{'.$name.'}' }}</code>@if (! $loop->last), @endif
    @endforeach
</div>

<form method="POST" action="{{ route('admin.settings.mail.templates.update') }}">
    @csrf
    @method('PUT')

    @foreach ($templates as $key => $template)
        <div class="border rounded p-3 bg-white mb-3">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-2 flex-wrap">
                <div>
                    <h2 class="h6 mb-1">
                        @if (trans()->has('admin.settings.mail_template_types.'.$key))
                            {{ __('admin.settings.mail_template_types.'.$key) }}
                        @else
                            {{ str_replace('_', ' ', $key) }}
                        @endif
                    </h2>
                    <p class="text-muted small mb-0">
                        @if (trans()->has('admin.settings.mail_template_descs.'.$key))
                            {{ __('admin.settings.mail_template_descs.'.$key) }}
                        @else
                            {{ $template['description'] }}
                        @endif
                    </p>
                </div>
                <label class="capability-item mb-0">
                    <input type="checkbox" name="templates[{{ $key }}][enabled]" value="1" @checked(old("templates.$key.enabled", $template['enabled']))>
                    <span><strong>{{ __('admin.settings.mail_template_enabled') }}</strong></span>
                </label>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('admin.settings.mail_template_subject') }}</label>
                <input class="form-control" name="templates[{{ $key }}][subject]" value="{{ old("templates.$key.subject", $template['subject']) }}" maxlength="255">
            </div>
            <div class="mb-0">
                <label class="form-label">{{ __('admin.settings.mail_template_body') }}</label>
                <textarea class="form-control font-monospace" rows="8" name="templates[{{ $key }}][body]">{{ old("templates.$key.body", $template['body']) }}</textarea>
                <div class="form-text">{{ __('admin.settings.mail_template_body_hint') }}</div>
            </div>
        </div>
    @endforeach

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">{{ __('admin.settings.mail_templates_save') }}</button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.settings.mail') }}">{{ __('admin.nav.mail') }}</a>
    </div>
</form>
@endsection

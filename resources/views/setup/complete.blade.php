@php
    $ui = app(\App\Services\UIFrameworkService::class);
@endphp

<x-setup.layout :cms-name="$cmsName" :current-step="$currentStep" :title="__('setup.complete.title')">
    <div class="text-center">
        <div style="font-size:2.5rem;margin-bottom:.5rem" aria-hidden="true">🎉</div>
        <h2 class="{{ $ui->class('heading') }}">{{ __('setup.complete.heading') }}</h2>
        <p class="{{ $ui->class('subheading') }}">
            {{ __('setup.complete.body') }}
        </p>

        <x-ui.alert type="success">{{ __('setup.complete.success_alert') }}</x-ui.alert>

        <ul class="check-list text-start mb-4">
            <li><strong>{{ __('setup.complete.label_website') }}:</strong>&nbsp; <a href="{{ $websiteUrl }}">{{ $websiteUrl }}</a></li>
            <li><strong>{{ __('setup.complete.label_admin') }}:</strong>&nbsp; <a href="{{ $adminUrl }}">{{ $adminUrl }}</a></li>
        </ul>

        <div class="actions" style="justify-content:center">
            <x-ui.button href="{{ $websiteUrl }}" variant="secondary" type="button">{{ __('setup.complete.website') }}</x-ui.button>
            <x-ui.button href="{{ $adminUrl }}" variant="primary" type="button">{{ __('setup.complete.admin') }}</x-ui.button>
        </div>
    </div>
</x-setup.layout>

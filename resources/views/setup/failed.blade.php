@php
    $ui = app(\App\Services\UIFrameworkService::class);
@endphp

<x-setup.layout :cms-name="$cmsName" :current-step="$currentStep" :title="__('setup.failed.title')">
    <h2 class="{{ $ui->class('heading') }}">{{ __('setup.failed.heading') }}</h2>
    <p class="{{ $ui->class('subheading') }}">{{ __('setup.failed.body') }}</p>

    <x-ui.alert type="danger">
        {{ session('error') ?: __('setup.failed.default_error') }}
    </x-ui.alert>

    <div class="{{ $ui->class('alert_info') }} mb-4">
        <strong>{{ __('setup.failed.common_fixes') }}</strong><br>
        {!! __('setup.failed.common_fixes_body') !!}
    </div>

    <div class="actions between">
        <x-ui.button href="{{ route('setup.database') }}" variant="secondary" type="button">← {{ __('setup.failed.database') }}</x-ui.button>
        <x-ui.button href="{{ route('setup.administrator') }}" variant="secondary" type="button">{{ __('setup.failed.administrator') }}</x-ui.button>
        <x-ui.button href="{{ route('setup.install.show') }}" variant="primary" type="button">{{ __('setup.failed.retry') }}</x-ui.button>
    </div>
</x-setup.layout>

@php
    $ui = app(\App\Services\UIFrameworkService::class);
@endphp

<x-setup.layout :cms-name="$cmsName" :current-step="$currentStep" title="Installation Failed">
    <h2 class="{{ $ui->class('heading') }}">Installation could not be completed.</h2>
    <p class="{{ $ui->class('subheading') }}">Fix the issue below, then try again.</p>

    <x-ui.alert type="danger">
        {{ session('error') ?: 'Something went wrong during installation. Technical details have been logged.' }}
    </x-ui.alert>

    <div class="{{ $ui->class('alert_info') }} mb-4">
        <strong>Common fixes</strong><br>
        Database (Docker): host <code>postgres</code>, database/user <code>cms</code>, password <code>cms_secret</code>.<br>
        Admin password: 12+ characters with upper, lower, number, and symbol — e.g. <code>MySite2026!</code>
    </div>

    <div class="actions between">
        <x-ui.button href="{{ route('setup.database') }}" variant="secondary" type="button">← Database</x-ui.button>
        <x-ui.button href="{{ route('setup.administrator') }}" variant="secondary" type="button">Administrator</x-ui.button>
        <x-ui.button href="{{ route('setup.install.show') }}" variant="primary" type="button">Try Again</x-ui.button>
    </div>
</x-setup.layout>

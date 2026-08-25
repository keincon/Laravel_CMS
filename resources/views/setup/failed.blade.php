@php
    $ui = app(\App\Services\UIFrameworkService::class);
@endphp

<x-setup.layout :cms-name="$cmsName" :current-step="$currentStep" title="Installation Failed">
    <h2 class="{{ $ui->class('heading') }}">Installation could not be completed.</h2>
    <p class="{{ $ui->class('subheading') }}">
        Please check your database configuration and try again.
    </p>

    <x-ui.alert type="danger">
        Something went wrong during installation. Technical details have been logged for the site administrator.
    </x-ui.alert>

    <div class="actions between">
        <x-ui.button href="{{ route('setup.database') }}" variant="secondary" type="button">← Back</x-ui.button>
        <x-ui.button href="{{ route('setup.install.show') }}" variant="primary" type="button">Try Again</x-ui.button>
    </div>
</x-setup.layout>

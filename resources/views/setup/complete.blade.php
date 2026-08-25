@php
    $ui = app(\App\Services\UIFrameworkService::class);
@endphp

<x-setup.layout :cms-name="$cmsName" :current-step="$currentStep" title="Complete">
    <div class="text-center">
        <div style="font-size:2.5rem;margin-bottom:.5rem" aria-hidden="true">🎉</div>
        <h2 class="{{ $ui->class('heading') }}">Your website is ready!</h2>
        <p class="{{ $ui->class('subheading') }}">
            Your CMS has been successfully installed.
        </p>

        <x-ui.alert type="success">Installation completed successfully.</x-ui.alert>

        <ul class="check-list text-start mb-4">
            <li><strong>Website:</strong>&nbsp; <a href="{{ $websiteUrl }}">{{ $websiteUrl }}</a></li>
            <li><strong>Administration:</strong>&nbsp; <a href="{{ $adminUrl }}">{{ $adminUrl }}</a></li>
        </ul>

        <div class="actions" style="justify-content:center">
            <x-ui.button href="{{ $websiteUrl }}" variant="secondary" type="button">Visit Website</x-ui.button>
            <x-ui.button href="{{ $adminUrl }}" variant="primary" type="button">Go to Dashboard</x-ui.button>
        </div>
    </div>
</x-setup.layout>

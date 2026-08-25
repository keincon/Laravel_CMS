@php
    $ui = app(\App\Services\UIFrameworkService::class);
@endphp

<x-setup.layout :cms-name="$cmsName" :current-step="$currentStep" title="Welcome">
    <div class="text-center">
        <h2 class="{{ $ui->class('heading') }}">Welcome to {{ $cmsName }}</h2>
        <p class="{{ $ui->class('subheading') }}">
            Let's set up your website.<br>
            This will only take a few minutes.
        </p>

        <div class="actions end" style="justify-content:center">
            <x-ui.button href="{{ route('setup.requirements') }}" variant="primary">
                Start Installation
            </x-ui.button>
        </div>
    </div>
</x-setup.layout>

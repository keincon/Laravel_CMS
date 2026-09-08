@php
    $ui = app(\App\Services\UIFrameworkService::class);
@endphp

<x-setup.layout :cms-name="$cmsName" :current-step="$currentStep" :title="__('setup.welcome.title')">
    <div class="text-center">
        <h2 class="{{ $ui->class('heading') }}">{{ __('setup.welcome.heading', ['name' => $cmsName]) }}</h2>
        <p class="{{ $ui->class('subheading') }}">
            {!! __('setup.welcome.body') !!}
        </p>

        <div class="actions end" style="justify-content:center">
            <x-ui.button href="{{ route('setup.requirements') }}" variant="primary">
                {{ __('setup.welcome.start') }}
            </x-ui.button>
        </div>
    </div>
</x-setup.layout>

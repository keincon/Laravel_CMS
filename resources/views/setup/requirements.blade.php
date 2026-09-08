@php
    $ui = app(\App\Services\UIFrameworkService::class);
@endphp

<x-setup.layout :cms-name="$cmsName" :current-step="$currentStep" :title="__('setup.requirements.title')">
    <h2 class="{{ $ui->class('heading') }}">{{ __('setup.requirements.heading') }}</h2>
    <p class="{{ $ui->class('subheading') }}">{{ __('setup.requirements.subheading') }}</p>

    <h3 class="h6 text-uppercase {{ $ui->class('text_muted') }} mb-2" style="letter-spacing:.04em;font-size:.75rem">{{ __('setup.requirements.section_php') }}</h3>
    <ul class="check-list mb-4">
        <li>
            <span class="{{ $checks['php']['passed'] ? $ui->class('check_ok') : $ui->class('check_fail') }}">
                {{ $checks['php']['passed'] ? '✓' : '✗' }}
            </span>
            <span>{{ $checks['php']['label'] }}</span>
        </li>
    </ul>

    <h3 class="h6 text-uppercase {{ $ui->class('text_muted') }} mb-2" style="letter-spacing:.04em;font-size:.75rem">{{ __('setup.requirements.section_extensions') }}</h3>
    <ul class="check-list mb-4">
        @foreach ($checks['extensions'] as $ext)
            <li>
                <span class="{{ $ext['passed'] ? $ui->class('check_ok') : $ui->class('check_fail') }}">
                    {{ $ext['passed'] ? '✓' : '✗' }}
                </span>
                <span>{{ $ext['label'] }}</span>
            </li>
        @endforeach
    </ul>

    <h3 class="h6 text-uppercase {{ $ui->class('text_muted') }} mb-2" style="letter-spacing:.04em;font-size:.75rem">{{ __('setup.requirements.section_storage') }}</h3>
    <ul class="check-list mb-4">
        @foreach ($checks['permissions'] as $perm)
            <li>
                <span class="{{ $perm['passed'] ? $ui->class('check_ok') : $ui->class('check_fail') }}">
                    {{ $perm['passed'] ? '✓' : '✗' }}
                </span>
                <span>{{ $perm['label'] }}</span>
            </li>
        @endforeach
    </ul>

    <h3 class="h6 text-uppercase {{ $ui->class('text_muted') }} mb-2" style="letter-spacing:.04em;font-size:.75rem">{{ __('setup.requirements.section_database') }}</h3>
    <ul class="check-list mb-2">
        <li>
            <span class="{{ $checks['database']['passed'] ? $ui->class('check_ok') : $ui->class('check_fail') }}">
                {{ $checks['database']['passed'] ? '✓' : '✗' }}
            </span>
            <span>{{ $checks['database']['label'] }}</span>
        </li>
        @foreach (($checks['database']['drivers'] ?? []) as $driverKey => $driver)
            <li class="ps-4">
                <span class="{{ $driver['available'] ? $ui->class('check_ok') : $ui->class('check_fail') }}">
                    {{ $driver['available'] ? '✓' : '✗' }}
                </span>
                <span>{{ $driver['label'] }}{{ $driver['available'] ? '' : ' ' . __('setup.requirements.driver_missing') }}</span>
            </li>
        @endforeach
    </ul>

    @unless ($checks['passed'])
        <x-ui.alert type="danger">
            {{ __('setup.requirements.failed') }}
        </x-ui.alert>
    @endunless

    <div class="actions between">
        <x-ui.button href="{{ route('setup.welcome') }}" variant="secondary" type="button">{{ __('setup.requirements.back') }}</x-ui.button>
        <div class="actions" style="margin:0">
            <x-ui.button href="{{ route('setup.requirements') }}" variant="secondary" type="button">{{ __('setup.requirements.refresh') }}</x-ui.button>
            @if ($checks['passed'])
                <x-ui.button href="{{ route('setup.database') }}" variant="primary" type="button">{{ __('setup.requirements.continue') }}</x-ui.button>
            @endif
        </div>
    </div>
</x-setup.layout>

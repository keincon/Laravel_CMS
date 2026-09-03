@php
    $ui = app(\App\Services\UIFrameworkService::class);
@endphp

<x-setup.layout :cms-name="$cmsName" :current-step="$currentStep" title="Requirements">
    <h2 class="{{ $ui->class('heading') }}">System Requirements</h2>
    <p class="{{ $ui->class('subheading') }}">We check your server before continuing.</p>

    <h3 class="h6 text-uppercase {{ $ui->class('text_muted') }} mb-2" style="letter-spacing:.04em;font-size:.75rem">PHP</h3>
    <ul class="check-list mb-4">
        <li>
            <span class="{{ $checks['php']['passed'] ? $ui->class('check_ok') : $ui->class('check_fail') }}">
                {{ $checks['php']['passed'] ? '✓' : '✗' }}
            </span>
            <span>{{ $checks['php']['label'] }}</span>
        </li>
    </ul>

    <h3 class="h6 text-uppercase {{ $ui->class('text_muted') }} mb-2" style="letter-spacing:.04em;font-size:.75rem">Extensions</h3>
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

    <h3 class="h6 text-uppercase {{ $ui->class('text_muted') }} mb-2" style="letter-spacing:.04em;font-size:.75rem">Storage</h3>
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

    <h3 class="h6 text-uppercase {{ $ui->class('text_muted') }} mb-2" style="letter-spacing:.04em;font-size:.75rem">Database</h3>
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
                <span>{{ $driver['label'] }}{{ $driver['available'] ? '' : ' driver missing' }}</span>
            </li>
        @endforeach
    </ul>

    @unless ($checks['passed'])
        <x-ui.alert type="danger">
            Some requirements failed. Please fix them before continuing.
        </x-ui.alert>
    @endunless

    <div class="actions between">
        <x-ui.button href="{{ route('setup.welcome') }}" variant="secondary" type="button">← Back</x-ui.button>
        <div class="actions" style="margin:0">
            <x-ui.button href="{{ route('setup.requirements') }}" variant="secondary" type="button">Check Again</x-ui.button>
            @if ($checks['passed'])
                <x-ui.button href="{{ route('setup.database') }}" variant="primary" type="button">Continue</x-ui.button>
            @endif
        </div>
    </div>
</x-setup.layout>

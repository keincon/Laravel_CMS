@props([
    'type' => 'info',
])

@php
    $ui = app(\App\Services\UIFrameworkService::class);
    $class = match ($type) {
        'success' => $ui->class('alert_success'),
        'danger', 'error' => $ui->class('alert_danger'),
        default => $ui->class('alert_info'),
    };
@endphp

<div {{ $attributes->merge(['class' => $class.' mb-4']) }} role="alert">
    {{ $slot }}
</div>

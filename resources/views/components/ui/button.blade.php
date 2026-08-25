@props([
    'variant' => 'primary',
    'type' => 'submit',
    'block' => false,
    'href' => null,
])

@php
    $ui = app(\App\Services\UIFrameworkService::class);
    $variantClass = match ($variant) {
        'secondary' => $ui->class('btn_secondary'),
        'success' => $ui->class('btn_success'),
        default => $ui->class('btn_primary'),
    };
    $classes = trim($variantClass.' '.($block ? $ui->class('btn_block') : '').' '.($attributes->get('class') ?? ''));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->except('class')->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->except('class')->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif

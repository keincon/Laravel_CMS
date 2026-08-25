@props([])

@php
    $ui = app(\App\Services\UIFrameworkService::class);
@endphp

<nav {{ $attributes->merge(['class' => 'site-nav']) }}>
    {{ $slot }}
</nav>

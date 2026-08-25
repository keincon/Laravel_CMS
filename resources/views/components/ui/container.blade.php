@props(['fluid' => false])

@php
    $class = $fluid ? 'w-100' : 'site-container';
@endphp

<div {{ $attributes->merge(['class' => $class]) }}>
    {{ $slot }}
</div>

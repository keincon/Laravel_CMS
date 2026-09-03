@props(['variant' => 'chip'])

@php
    $class = match ($variant) {
        'button' => 'theme-toggle theme-toggle-btn',
        'icon' => 'theme-toggle theme-toggle-icon',
        default => 'theme-toggle theme-toggle-chip',
    };
@endphp

<button
    type="button"
    {{ $attributes->merge(['class' => $class]) }}
    data-theme-toggle
    onclick="window.cmsTheme && window.cmsTheme.toggle()"
    aria-label="Toggle color mode"
>
    <span class="theme-toggle-sun" aria-hidden="true">☀</span>
    <span class="theme-toggle-moon" aria-hidden="true">☾</span>
    <span data-theme-label>Theme</span>
</button>

@props([
    'title' => null,
])

@php
    $ui = app(\App\Services\UIFrameworkService::class);
@endphp

<div {{ $attributes->merge(['class' => $ui->class('card')]) }}>
    <div class="{{ $ui->class('card_body') }}">
        @if ($title)
            <h2 class="{{ $ui->class('heading') }} mb-2">{{ $title }}</h2>
        @endif
        {{ $slot }}
    </div>
</div>

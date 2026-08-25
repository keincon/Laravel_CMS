@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => '',
    'required' => false,
    'help' => null,
])

@php
    $ui = app(\App\Services\UIFrameworkService::class);
    $id = $attributes->get('id', $name);
@endphp

<div class="mb-4">
    @if ($label)
        <label for="{{ $id }}" class="{{ $ui->class('label') }}">{{ $label }}@if($required) <span class="text-danger">*</span>@endif</label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $id }}"
        value="{{ old($name, $value) }}"
        @if($required) required @endif
        {{ $attributes->except(['id', 'class'])->merge(['class' => $ui->class('input').' '.($attributes->get('class') ?? '')]) }}
    >

    @if ($help)
        <p class="mt-1 text-sm {{ $ui->class('text_muted') }}">{{ $help }}</p>
    @endif

    @error($name)
        <p class="mt-1 text-sm {{ $ui->class('check_fail') }}">{{ $message }}</p>
    @enderror
</div>

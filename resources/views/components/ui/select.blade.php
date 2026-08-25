@props([
    'name',
    'label' => null,
    'value' => '',
    'required' => false,
    'options' => [],
])

@php
    $ui = app(\App\Services\UIFrameworkService::class);
    $id = $attributes->get('id', $name);
    $selected = old($name, $value);
@endphp

<div class="mb-4">
    @if ($label)
        <label for="{{ $id }}" class="{{ $ui->class('label') }}">{{ $label }}@if($required) <span class="text-danger">*</span>@endif</label>
    @endif

    <select
        name="{{ $name }}"
        id="{{ $id }}"
        @if($required) required @endif
        {{ $attributes->except(['id', 'class'])->merge(['class' => $ui->class('select').' '.($attributes->get('class') ?? '')]) }}
    >
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>

    @error($name)
        <p class="mt-1 text-sm {{ $ui->class('check_fail') }}">{{ $message }}</p>
    @enderror
</div>

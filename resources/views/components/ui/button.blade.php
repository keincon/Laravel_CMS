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
    $fallback = match ($variant) {
        'secondary' => 'cms-btn cms-btn-secondary',
        'success' => 'cms-btn cms-btn-success',
        default => 'cms-btn cms-btn-primary',
    };
    $classes = trim($fallback.' '.$variantClass.' '.($block ? $ui->class('btn_block') : '').' '.($attributes->get('class') ?? ''));
@endphp

@once
    <style>
        .cms-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            padding: .7rem 1.35rem;
            border-radius: .65rem;
            font-weight: 600;
            font-size: .95rem;
            line-height: 1.25;
            text-decoration: none !important;
            border: 1px solid transparent;
            cursor: pointer;
            transition: background .15s ease, border-color .15s ease, box-shadow .15s ease, color .15s ease;
        }
        .cms-btn-primary, a.cms-btn-primary, button.cms-btn-primary {
            background: #2563eb !important;
            color: #fff !important;
            border-color: #2563eb !important;
            box-shadow: 0 8px 20px rgba(37, 99, 235, .28);
        }
        .cms-btn-primary:hover, a.cms-btn-primary:hover {
            background: #1d4ed8 !important;
            border-color: #1d4ed8 !important;
            color: #fff !important;
        }
        .cms-btn-secondary, a.cms-btn-secondary, button.cms-btn-secondary {
            background: #fff !important;
            color: #334155 !important;
            border-color: #cbd5e1 !important;
            box-shadow: none;
        }
        .cms-btn-secondary:hover { background: #f8fafc !important; }
        .cms-btn-success, a.cms-btn-success, button.cms-btn-success {
            background: #16a34a !important;
            color: #fff !important;
            border-color: #16a34a !important;
        }
        .cms-btn-success:hover { background: #15803d !important; color: #fff !important; }
    </style>
@endonce

@if ($href)
    <a href="{{ $href }}" {{ $attributes->except('class')->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->except('class')->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif

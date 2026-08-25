@props(['links' => []])

@php
    $defaults = $links ?: [
        ['label' => 'X', 'url' => '#'],
        ['label' => 'LinkedIn', 'url' => '#'],
    ];
@endphp

<div style="display:flex;gap:.75rem;flex-wrap:wrap">
    @foreach ($defaults as $link)
        <a href="{{ $link['url'] ?? '#' }}" style="color:inherit;text-decoration:none;opacity:.85">{{ $link['label'] ?? 'Link' }}</a>
    @endforeach
</div>

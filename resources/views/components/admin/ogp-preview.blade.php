@props([
    'title' => '',
    'description' => '',
    'url' => '',
    'image' => null,
    'variant' => 'facebook',
])

@php
    $host = strtoupper(parse_url($url ?: url('/'), PHP_URL_HOST) ?: 'EXAMPLE.COM');
@endphp

<div class="ogp-preview overflow-hidden rounded border bg-white" style="max-width:480px;border-color:#e2e8f0">
    <div style="aspect-ratio:1.91/1;background:#e2e8f0;display:grid;place-items:center;overflow:hidden">
        @if ($image)
            <img src="{{ $image }}" alt="" style="width:100%;height:100%;object-fit:cover">
        @else
            <span class="text-muted small">OGP IMAGE</span>
        @endif
    </div>
    <div class="p-3" style="{{ $variant === 'twitter' ? 'border-top:1px solid #e2e8f0' : 'background:#f0f2f5' }}">
        <div class="small text-uppercase text-muted mb-1" style="letter-spacing:.04em">{{ $host }}</div>
        <div class="fw-semibold mb-1">{{ \Illuminate\Support\Str::limit($title ?: 'Title', 70) }}</div>
        <div class="small text-muted">{{ \Illuminate\Support\Str::limit($description ?: 'Description', 120) }}</div>
        @if ($variant === 'linkedin')
            <div class="small text-muted mt-2">LinkedIn-style card</div>
        @elseif ($variant === 'twitter')
            <div class="small text-muted mt-2">X / Twitter-style card</div>
        @endif
    </div>
</div>

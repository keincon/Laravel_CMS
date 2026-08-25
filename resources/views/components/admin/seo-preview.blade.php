@props([
    'title' => '',
    'description' => '',
    'url' => '',
])

@php
    $host = parse_url($url ?: url('/'), PHP_URL_HOST) ?: 'example.com';
    $displayTitle = $title !== '' ? $title : 'Page title';
    $displayDesc = $description !== '' ? $description : 'Meta description preview…';
@endphp

<div class="seo-preview border rounded p-3 bg-white" style="border-color:#e2e8f0;max-width:600px" x-data>
    <div class="text-sm mb-1" style="color:#202124">{{ $host }}</div>
    <div class="fw-semibold mb-1" style="color:#1a0dab;font-size:1.15rem;line-height:1.3">{{ \Illuminate\Support\Str::limit($displayTitle, 60) }}</div>
    <div class="text-sm" style="color:#4d5156">{{ \Illuminate\Support\Str::limit($displayDesc, 160) }}</div>
</div>

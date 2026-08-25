@props(['page' => null, 'post' => null, 'path' => null, 'meta' => null])

@php
    $content = $page ?? $post;
    $meta = $meta ?? app(\App\Services\SeoService::class)->resolve($content, $path);
@endphp

<title>{{ $meta['title'] }}</title>
<meta name="description" content="{{ $meta['description'] }}">
@if (! empty($meta['keywords']))
    <meta name="keywords" content="{{ $meta['keywords'] }}">
@endif
<meta name="robots" content="{{ $meta['robots'] }}">
<link rel="canonical" href="{{ $meta['canonical'] }}">

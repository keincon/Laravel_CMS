@props(['page' => null, 'post' => null, 'path' => null, 'meta' => null])

@php
    $content = $page ?? $post;
    $meta = $meta ?? app(\App\Services\SeoService::class)->resolve($content, $path);
@endphp

<meta property="og:title" content="{{ $meta['og_title'] }}">
<meta property="og:description" content="{{ $meta['og_description'] }}">
<meta property="og:type" content="{{ $meta['og_type'] }}">
<meta property="og:url" content="{{ $meta['og_url'] }}">
@if (! empty($meta['og_image']))
    <meta property="og:image" content="{{ $meta['og_image'] }}">
@endif
<meta property="og:site_name" content="{{ $meta['og_site_name'] }}">
<meta property="og:locale" content="{{ $meta['og_locale'] }}">

<meta name="twitter:card" content="{{ $meta['twitter_card'] }}">
<meta name="twitter:title" content="{{ $meta['twitter_title'] }}">
<meta name="twitter:description" content="{{ $meta['twitter_description'] }}">
@if (! empty($meta['twitter_image']))
    <meta name="twitter:image" content="{{ $meta['twitter_image'] }}">
@endif

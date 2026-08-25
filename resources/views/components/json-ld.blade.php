@props(['page' => null, 'post' => null])

@php
    $content = $page ?? $post;
    $blocks = app(\App\Services\SeoService::class)->jsonLd($content);
@endphp

@foreach ($blocks as $block)
    <script type="application/ld+json">{!! json_encode($block, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) !!}</script>
@endforeach

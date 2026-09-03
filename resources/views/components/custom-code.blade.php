@props([
    'position' => 'head', // head | body_open | body_close | footer
    'page' => null,
    'post' => null,
])

@php
    $code = app(\App\Services\CustomCodeService::class);
    $entry = $page ?? $post;
@endphp

@if ($position === 'head')
    {!! $code->customHtmlHead() !!}
    @if ($css = $code->additionalCss())
        <style id="cms-additional-css">{!! $css !!}</style>
    @endif
    @if ($entryCss = $code->entryCss($entry))
        <style id="cms-entry-css">{!! $entryCss !!}</style>
    @endif
    {!! $code->headerScripts() !!}
@elseif ($position === 'body_open')
    {!! $code->customHtmlBodyOpen() !!}
@elseif ($position === 'body_close')
    {!! $code->customHtmlBodyClose() !!}
    {!! $code->footerScripts() !!}
    @if ($entryJs = $code->entryJs($entry))
        @if (str_contains($entryJs, '<script'))
            {!! $entryJs !!}
        @else
            <script id="cms-entry-js">{!! $entryJs !!}</script>
        @endif
    @endif
@endif

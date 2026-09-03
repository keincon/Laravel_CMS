@props(['post'])

@php
    $permalinks = app(\App\Services\PermalinkService::class);
    $url = $permalinks->postUrl($post);
    $body = $post->content ?? $post->body ?? '';
    $excerpt = $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $body), 160);
    $authorName = $post->author
        ? (method_exists($post->author, 'publicName') ? $post->author->publicName() : $post->author->name)
        : null;
@endphp

<article class="post-card">
    <h2><a href="{{ $url }}">{{ $post->title }}@if(!empty($post->is_sticky)) <span style="font-size:.75rem;opacity:.7">· Sticky</span>@endif</a></h2>
    <p style="opacity:.7;font-size:.9rem;margin:.25rem 0">
        {{ optional($post->published_at)->toFormattedDateString() }}
        @if ($authorName)
            · <a href="{{ url('/author/'.($post->author->username ?? '')) }}">{{ $authorName }}</a>
        @endif
    </p>
    <p>{{ $excerpt }}</p>
</article>

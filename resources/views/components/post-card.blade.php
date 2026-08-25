@props(['post'])

@php
    $url = app(\App\Services\PermalinkService::class)->postUrl($post);
    $excerpt = $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $post->content), 160);
@endphp

<article class="post-card">
    <h2><a href="{{ $url }}">{{ $post->title }}</a></h2>
    <p style="opacity:.7;font-size:.9rem;margin:.25rem 0">
        {{ optional($post->published_at)->toFormattedDateString() }}
        @if ($post->author)
            · <a href="{{ url('/author/'.$post->author->username) }}">{{ $post->author->name }}</a>
        @endif
    </p>
    <p>{{ $excerpt }}</p>
</article>

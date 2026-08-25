<x-layout.master :post="$post" context="post" :seo-path="$seoPath ?? null">
    <article>
        <h1>{{ $post->title }}</h1>
        <p style="opacity:.7;font-size:.9rem">{{ optional($post->published_at)->toFormattedDateString() }} · {{ $post->author?->name }}</p>
        <div>{!! $post->content !!}</div>
    </article>
</x-layout.master>

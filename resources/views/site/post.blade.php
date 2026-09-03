<x-layout.master :post="$post" context="post" :seo-path="$seoPath ?? null">
    <article>
        @if ($post->featuredImage)
            <img src="{{ $post->featuredImage->url() }}" alt="{{ $post->featuredImage->alt ?: $post->title }}"
                 style="width:100%;max-height:420px;object-fit:cover;border-radius:.75rem;margin-bottom:1.25rem">
        @endif
        <h1>{{ $post->title }}</h1>
        <p style="opacity:.7;font-size:.9rem">{{ optional($post->published_at)->toFormattedDateString() }} · {{ $post->author?->name }}</p>
        <div>{!! $post->content !!}</div>
        @if ($post->custom_html)
            <div class="cms-custom-html" style="margin-top:1.5rem">{!! $post->custom_html !!}</div>
        @endif
    </article>
    <x-comments :post="$post" :comments="$comments ?? collect()" />
</x-layout.master>

<x-layout.master
    :post="$post"
    :dynamic-config="$dynamicConfig ?? null"
    context="post"
    :seo-path="$seoPath ?? null"
    :seo-meta="$seoMeta ?? null"
    :breadcrumbs="$breadcrumbs ?? []"
>
    <span class="badge-type">Dynamic · Single Post</span>
    <article style="margin-top:.5rem">
        @if ($post->featuredImage)
            <img src="{{ $post->featuredImage->url() }}" alt="{{ $post->featuredImage->alt ?: $post->title }}"
                 style="width:100%;max-height:420px;object-fit:cover;border-radius:.75rem;margin-bottom:1.25rem">
        @endif
        <h1>{{ $post->title }}</h1>
        <p style="opacity:.7;font-size:.9rem">
            {{ optional($post->published_at)->toFormattedDateString() }}
            @if ($post->author)
                · <a href="{{ url('/author/'.$post->author->username) }}">{{ $post->author->name }}</a>
            @endif
        </p>
        <div class="cms-content-html">{!! $post->content !!}</div>
        @if ($post->custom_html)
            <div class="cms-custom-html" style="margin-top:1.5rem">{!! $post->custom_html !!}</div>
        @endif
    </article>

    <x-comments :post="$post" :comments="$comments ?? collect()" />

    @if (($relatedPosts ?? collect())->isNotEmpty())
        <section style="margin-top:3rem">
            <h2 style="font-size:1.15rem">Related Posts</h2>
            <x-post-grid :posts="$relatedPosts" layout="list" />
        </section>
    @endif
</x-layout.master>

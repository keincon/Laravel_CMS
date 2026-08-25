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
        <h1>{{ $post->title }}</h1>
        <p style="opacity:.7;font-size:.9rem">
            {{ optional($post->published_at)->toFormattedDateString() }}
            @if ($post->author)
                · <a href="{{ url('/author/'.$post->author->username) }}">{{ $post->author->name }}</a>
            @endif
        </p>
        <div>{!! $post->content !!}</div>
    </article>

    @if (($relatedPosts ?? collect())->isNotEmpty())
        <section style="margin-top:3rem">
            <h2 style="font-size:1.15rem">Related Posts</h2>
            <x-post-grid :posts="$relatedPosts" layout="list" />
        </section>
    @endif
</x-layout.master>

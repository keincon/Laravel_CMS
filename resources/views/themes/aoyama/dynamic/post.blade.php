<x-layout.aoyama
    :post="$post ?? null"
    :dynamic-config="$dynamicConfig ?? null"
    context="post"
    :seo-path="$seoPath ?? null"
    :seo-meta="$seoMeta ?? null"
    :breadcrumbs="$breadcrumbs ?? []"
>
    <article class="ao-article">
        @php
            $featured = $post->featuredImage ?? $post->featuredMedia ?? null;
            $featuredUrl = null;
            if ($featured) {
                $featuredUrl = method_exists($featured, 'url') ? $featured->url() : ($featured->url ?? null);
            }
        @endphp

        @if ($featuredUrl)
            <img
                src="{{ $featuredUrl }}"
                alt="{{ $featured->alt ?? $post->title }}"
                style="width:100%;max-height:420px;object-fit:cover;margin-bottom:1.25rem"
            >
        @endif

        <h1>{{ $post->title }}</h1>
        <p class="ao-meta">
            {{ optional($post->published_at)->format('Y年n月j日') }}
            @if ($post->author)
                · {{ $post->author->name }}
            @endif
        </p>

        <div class="ao-content-html">{!! $post->content !!}</div>

        @if (! empty($post->custom_html))
            <div class="cms-custom-html" style="margin-top:1.5rem">{!! $post->custom_html !!}</div>
        @endif
    </article>

    @if (isset($comments) || ($post ?? null))
        <div style="margin-top:2.5rem">
            <x-comments :post="$post" :comments="$comments ?? collect()" />
        </div>
    @endif

    @if (($relatedPosts ?? collect())->isNotEmpty())
        @php
            $permalinks = app(\App\Services\PermalinkService::class);
        @endphp
        <section style="margin-top:3rem">
            <h2 class="ao-section-title">関連のお知らせ</h2>
            <ul class="ao-news-list" style="margin-top:1rem">
                @foreach ($relatedPosts as $related)
                    <li>
                        <a href="{{ $related instanceof \App\Models\Content ? $permalinks->contentUrl($related) : $permalinks->postUrl($related) }}">
                            <span class="ao-news-date">
                                {{ optional($related->published_at)->format('y.m.d') ?? '—' }}
                            </span>
                            <span class="ao-news-title">{{ $related->title }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layout.aoyama>

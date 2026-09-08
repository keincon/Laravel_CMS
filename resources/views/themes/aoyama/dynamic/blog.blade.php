<x-layout.aoyama
    :dynamic-config="$dynamicConfig ?? null"
    context="blog"
    :seo-path="$seoPath ?? null"
    :seo-meta="$seoMeta ?? null"
    :breadcrumbs="$breadcrumbs ?? []"
>
    <header class="ao-page-header">
        <h1>{{ $dynamicConfig->title ?? 'お知らせ' }}</h1>
        @if (! empty($dynamicConfig?->description))
            <p class="ao-excerpt">{{ $dynamicConfig->description }}</p>
        @endif
    </header>

    @php
        $permalinks = app(\App\Services\PermalinkService::class);
    @endphp

    @if (($posts ?? collect())->isEmpty())
        <p class="ao-empty">現在お知らせはありません。</p>
    @else
        <ul class="ao-news-list">
            @foreach ($posts as $item)
                <li>
                    <a href="{{ $item instanceof \App\Models\Content ? $permalinks->contentUrl($item) : $permalinks->postUrl($item) }}">
                        <span class="ao-news-date">
                            {{ optional($item->published_at)->format('y.m.d') ?? '—' }}
                        </span>
                        <span class="ao-news-title">{{ $item->title }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        <x-pagination :paginator="$posts" />
    @endif
</x-layout.aoyama>

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
        $archiveMonths = $archiveMonths ?? [];
        $newestId = ($posts ?? collect())->isNotEmpty() ? $posts->first()->id : null;
    @endphp

    <div class="ao-news-layout">
        <div class="ao-news-main">
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
                                <span class="ao-news-title">
                                    @if ($newestId && $item->id === $newestId)
                                        <span class="ao-news-new" aria-label="新着">NEW</span>
                                    @endif
                                    {{ $item->title }}
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <x-pagination :paginator="$posts" />
            @endif
        </div>

        @if (! empty($archiveMonths))
            <aside class="ao-news-aside" aria-label="バックナンバー">
                <h2 class="ao-news-aside-title">バックナンバー</h2>
                <ul class="ao-backnumber">
                    @foreach ($archiveMonths as $month)
                        <li>
                            <a href="{{ $month['url'] }}">{{ $month['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </aside>
        @endif
    </div>
</x-layout.aoyama>

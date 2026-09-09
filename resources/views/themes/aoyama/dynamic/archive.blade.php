<x-layout.aoyama
    :dynamic-config="$dynamicConfig ?? null"
    context="archive"
    :seo-path="$seoPath ?? null"
    :seo-meta="$seoMeta ?? null"
    :breadcrumbs="$breadcrumbs ?? []"
>
    <header class="ao-page-header">
        <h1>{{ $archiveLabel ?? ($dynamicConfig->title ?? 'バックナンバー') }}</h1>
        <p class="ao-excerpt">
            <a href="{{ url('/news') }}">お知らせ一覧へ戻る</a>
        </p>
    </header>

    @php
        $permalinks = app(\App\Services\PermalinkService::class);
    @endphp

    @if (($posts ?? collect())->isEmpty())
        <p class="ao-empty">この期間のお知らせはありません。</p>
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

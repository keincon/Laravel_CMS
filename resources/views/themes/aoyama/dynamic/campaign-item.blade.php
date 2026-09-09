<x-layout.aoyama
    :dynamic-config="$dynamicConfig ?? null"
    context="campaign"
    :seo-path="$seoPath ?? null"
    :seo-meta="$seoMeta ?? null"
    :breadcrumbs="$breadcrumbs ?? []"
>
    @php
        $item = $campaign ?? $post;
        $permalinks = app(\App\Services\PermalinkService::class);
        $meta = $item->relationLoaded('meta') ? $item->meta->keyBy('key') : collect();
        $periodLabel = $meta->get('period_label')?->decodedValue() ?: 'キャンペーン期間';
        $period = $meta->get('period')?->decodedValue();
        $targetLabel = $meta->get('target_label')?->decodedValue() ?: '対象';
        $target = $meta->get('target')?->decodedValue();
        $note = $meta->get('note')?->decodedValue();
    @endphp

    <article class="ao-article ao-campaign-detail">
        <header class="ao-page-header">
            <h1>{{ $item->title }}</h1>
            @if ($item->excerpt)
                <p class="ao-excerpt">{{ $item->excerpt }}</p>
            @endif
        </header>

        @if ($note)
            <p class="ao-campaign-card-note">{{ $note }}</p>
        @endif

        @if ($period || $target)
            <dl class="ao-campaign-facts is-detail">
                @if ($period)
                    <div>
                        <dt>{{ $periodLabel }}</dt>
                        <dd>{{ $period }}</dd>
                    </div>
                @endif
                @if ($target)
                    <div>
                        <dt>{{ $targetLabel }}</dt>
                        <dd>{{ $target }}</dd>
                    </div>
                @endif
            </dl>
        @endif

        <div class="ao-content-html">{!! $item->content !!}</div>
    </article>

    <p style="margin-top:1.5rem">
        <a href="{{ url('/campaign') }}">← キャンペーン一覧へ戻る</a>
    </p>

    @if (($relatedCampaigns ?? collect())->isNotEmpty())
        <section style="margin-top:3rem">
            <h2 class="ao-section-title">その他のキャンペーン</h2>
            <ul class="ao-news-list" style="margin-top:1rem">
                @foreach ($relatedCampaigns as $related)
                    <li>
                        <a href="{{ $permalinks->contentUrl($related) }}">
                            <span class="ao-news-title">{{ $related->title }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layout.aoyama>

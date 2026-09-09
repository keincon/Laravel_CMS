<x-layout.aoyama
    :dynamic-config="$dynamicConfig ?? null"
    context="campaign"
    :seo-path="$seoPath ?? null"
    :seo-meta="$seoMeta ?? null"
    :breadcrumbs="$breadcrumbs ?? []"
>
    <header class="ao-page-header">
        <h1>{{ $dynamicConfig->title ?? 'キャンペーン' }}</h1>
        @if (! empty($dynamicConfig?->description))
            <p class="ao-excerpt">{{ $dynamicConfig->description }}</p>
        @endif
    </header>

    @php
        $permalinks = app(\App\Services\PermalinkService::class);
        $items = $campaigns ?? $posts ?? collect();
    @endphp

    @if ($items->isEmpty())
        <p class="ao-empty">現在開催中のキャンペーンはありません。</p>
    @else
        <div class="ao-campaign-list">
            @foreach ($items as $item)
                @php
                    $meta = $item->relationLoaded('meta')
                        ? $item->meta->keyBy('key')
                        : collect();
                    $periodLabel = $meta->get('period_label')?->decodedValue() ?: 'キャンペーン期間';
                    $period = $meta->get('period')?->decodedValue();
                    $targetLabel = $meta->get('target_label')?->decodedValue() ?: '対象';
                    $target = $meta->get('target')?->decodedValue();
                    $note = $meta->get('note')?->decodedValue();
                    $url = $permalinks->contentUrl($item);
                @endphp
                <article class="ao-campaign-card">
                    <h2 class="ao-campaign-card-title">
                        <a href="{{ $url }}">{{ $item->title }}</a>
                    </h2>
                    @if ($item->excerpt)
                        <p class="ao-campaign-card-lead">{{ $item->excerpt }}</p>
                    @endif
                    @if ($note)
                        <p class="ao-campaign-card-note">{{ $note }}</p>
                    @endif
                    <p class="ao-campaign-card-more">
                        キャンペーン詳細は<a href="{{ $url }}">こちらから</a>
                    </p>
                    @if ($period || $target)
                        <dl class="ao-campaign-facts">
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
                    <p class="ao-campaign-card-cta">
                        <a class="ao-btn is-outline" href="{{ $url }}">詳細を見る</a>
                    </p>
                </article>
            @endforeach
        </div>

        <x-pagination :paginator="$items" />
    @endif

    <aside class="ao-hurry" style="margin-top:2.5rem">
        <div>
            <h2>キャッシングについてお急ぎの方</h2>
            <p>カードをお持ちであれば、ATMでなくても電話やインターネット経由でキャッシングをお申込みいただけます。</p>
        </div>
        <a class="ao-btn" href="{{ url('/cashing-hurry') }}">お急ぎの方はこちら</a>
    </aside>
</x-layout.aoyama>

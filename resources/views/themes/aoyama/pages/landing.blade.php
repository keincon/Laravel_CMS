<x-layout.aoyama
    :page="$page ?? null"
    context="{{ $context ?? 'home' }}"
    :seo-path="$seoPath ?? null"
    :seo-meta="$seoMeta ?? null"
    :full-bleed="true"
>
    @include('themes.aoyama.partials.hero')

    <div class="ao-alert-bar">
        <div class="ao-container">
            <span>自然災害により被害を受けられたお客さまへ</span>
            <a href="{{ url('/news') }}">お知らせを見る</a>
        </div>
    </div>

    @if (! empty($page?->content) || ! empty($page?->custom_html))
        <section class="ao-section">
            <div class="ao-container">
                <div class="ao-page-body ao-content-html">
                    {!! $page->content !!}
                    @if (! empty($page->custom_html))
                        <div class="cms-custom-html" style="margin-top:1.5rem">{!! $page->custom_html !!}</div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    @include('themes.aoyama.partials.image-helper')

    <section class="ao-section is-surface">
        <div class="ao-container">
            <header class="ao-section-head">
                <h2 class="ao-section-title">ライフスタイルに合わせたピッタリの1枚を</h2>
                <p class="ao-section-lead">「洋服の青山」などでのお買い物が毎回お得に！ポイントもダブルで貯まります。</p>
            </header>

            <div class="ao-card-grid">
                <div class="ao-issuer">
                    <p class="ao-issuer-label">ライフカード発行カード</p>
                    <div class="ao-product">
                        <img src="{{ aoyama_theme_image('card-life.png') }}" alt="AOYAMAライフマスターカード" width="88" height="56" loading="lazy">
                        <div>
                            <h3>AOYAMAライフマスターカード</h3>
                            <p>Wポイント：AOYAMAポイント + サンクスポイント</p>
                            <a class="ao-product-link" href="{{ url('/card-aoyama-life') }}">カードを申し込む</a>
                        </div>
                    </div>
                    <div class="ao-product">
                        <img src="{{ aoyama_theme_image('card-bluerose-life.png') }}" alt="BLUE ROSE CARD" width="88" height="56" loading="lazy">
                        <div>
                            <h3>BLUE ROSE CARD</h3>
                            <p>Wポイント：ROSEポイント + サンクスポイント</p>
                            <a class="ao-product-link" href="{{ url('/card-bluerose-life') }}">カードを申し込む</a>
                        </div>
                    </div>
                </div>

                <div class="ao-issuer">
                    <p class="ao-issuer-label">三井住友カード発行カード</p>
                    <div class="ao-product">
                        <img src="{{ aoyama_theme_image('card-visa.png') }}" alt="AOYAMA VISAカード" width="88" height="56" loading="lazy">
                        <div>
                            <h3>AOYAMA VISAカード</h3>
                            <p>Wポイント：AOYAMAポイント + Vポイント</p>
                            <a class="ao-product-link" href="{{ url('/card-aoyama-visa') }}">カードを申し込む</a>
                        </div>
                    </div>
                    <div class="ao-product">
                        <img src="{{ aoyama_theme_image('card-bluerose-life.png') }}" alt="BLUE ROSE CARD" width="88" height="56" loading="lazy">
                        <div>
                            <h3>BLUE ROSE CARD</h3>
                            <p>Wポイント：ROSEポイント + Vポイント</p>
                            <a class="ao-product-link" href="{{ url('/card-bluerose-visa') }}">カードを申し込む</a>
                        </div>
                    </div>
                </div>

                <div class="ao-issuer">
                    <p class="ao-issuer-label">青山キャピタル発行カード</p>
                    <div class="ao-product">
                        <img src="{{ aoyama_theme_image('card-capital.png') }}" alt="AOYAMAカード" width="88" height="56" loading="lazy">
                        <div>
                            <h3>AOYAMAカード</h3>
                            <p>Wポイント：AOYAMAポイント + UCポイント</p>
                            <a class="ao-product-link" href="{{ url('/card-capital') }}">カードを申し込む</a>
                        </div>
                    </div>
                    <div class="ao-product">
                        <img src="{{ aoyama_theme_image('hero-card-bluerose.png') }}" alt="BLUE ROSE CARD" width="88" height="56" loading="lazy">
                        <div>
                            <h3>BLUE ROSE CARD</h3>
                            <p>Wポイント：ROSEポイント + UCポイント</p>
                            <a class="ao-product-link" href="{{ url('/card-bluerose') }}">カードを申し込む</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ao-btn-row">
                <a class="ao-btn" href="{{ url('/card') }}">カード一覧を見る</a>
                <a class="ao-btn is-outline" href="{{ url('/biz-yuutai') }}">カード優待特典を見る</a>
            </div>
        </div>
    </section>

    @php
        $permalinks = app(\App\Services\PermalinkService::class);
        $news = \App\Models\Content::query()
            ->ofType('post')
            ->published()
            ->latest('published_at')
            ->limit(8)
            ->get();
    @endphp

    <section class="ao-section">
        <div class="ao-container">
            <header class="ao-section-head">
                <h2 class="ao-section-title">お知らせ</h2>
            </header>

            @if ($news->isEmpty())
                <p class="ao-empty">現在お知らせはありません。</p>
            @else
                <ul class="ao-news-list">
                    @foreach ($news as $item)
                        <li>
                            <a href="{{ $permalinks->contentUrl($item) }}">
                                <span class="ao-news-date">
                                    {{ optional($item->published_at)->format('y.m.d') ?? '—' }}
                                </span>
                                <span class="ao-news-title">{{ $item->title }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="ao-btn-row">
                    <a class="ao-btn is-outline" href="{{ url('/news') }}">お知らせ一覧を見る</a>
                </div>
            @endif
        </div>
    </section>
</x-layout.aoyama>

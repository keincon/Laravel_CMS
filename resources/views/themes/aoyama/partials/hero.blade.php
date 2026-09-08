@include('themes.aoyama.partials.image-helper')

@php
    $slides = [
        [
            'bg' => aoyama_theme_image('hero-bg-1.png'),
            'card' => aoyama_theme_image('hero-card-aoyama.png'),
            'kicker' => 'AOYAMAカード',
            'title' => 'AOYAMAカードは洋服の青山でのお買い物を全力サポート！',
            'lead' => 'いつでも5％OFF、ポイントもダブルで貯まってお得。オンにもオフにも使える1枚です。',
            'cta' => url('/card'),
            'cta_label' => 'カードを申し込む',
        ],
        [
            'bg' => aoyama_theme_image('hero-bg-2.png'),
            'card' => aoyama_theme_image('hero-card-bluerose.png'),
            'kicker' => 'BLUE ROSE CARD',
            'title' => 'BLUE ROSE CARDで、もっと素敵に毎日を。',
            'lead' => 'レディース向け特典とWポイントで、お気に入りのお買い物をもっとお得に。',
            'cta' => url('/card'),
            'cta_label' => '詳しく見る',
        ],
        [
            'bg' => aoyama_theme_image('hero-bg-3.png'),
            'card' => aoyama_theme_image('hero-card-aoyama.png'),
            'kicker' => 'Wポイント',
            'title' => 'Wでポイントが貯まってお得！',
            'lead' => '洋服の青山でも、その他のお店でも。毎日のショッピングでポイントがダブル。',
            'cta' => url('/used-aoyama-point'),
            'cta_label' => 'ポイントの詳細',
        ],
    ];
@endphp

<section class="ao-hero" data-ao-hero aria-roledescription="carousel" aria-label="メインビジュアル">
    <div class="ao-hero-track">
        @foreach ($slides as $i => $slide)
            <div
                class="ao-hero-slide {{ $i === 0 ? 'is-active' : '' }}"
                data-ao-slide
                style="background-image: url('{{ $slide['bg'] }}')"
                role="group"
                aria-roledescription="slide"
                aria-label="{{ $i + 1 }} / {{ count($slides) }}"
            >
                <div class="ao-container ao-hero-inner">
                    <div class="ao-hero-copy">
                        <span class="ao-hero-kicker">{{ $slide['kicker'] }}</span>
                        <h2 class="ao-hero-title">{{ $slide['title'] }}</h2>
                        <p class="ao-hero-lead">{{ $slide['lead'] }}</p>
                        <ul class="ao-pills" aria-label="主な特典">
                            <li>5%OFF</li>
                            <li>ポイント</li>
                            <li>割引券</li>
                        </ul>
                        <a class="ao-hero-cta" href="{{ $slide['cta'] }}">{{ $slide['cta_label'] }}</a>
                    </div>
                    <div class="ao-hero-visual">
                        <img
                            src="{{ $slide['card'] }}"
                            alt=""
                            width="320"
                            height="200"
                            loading="{{ $i === 0 ? 'eager' : 'lazy' }}"
                        >
                    </div>
                </div>
            </div>
        @endforeach
        <div class="ao-hero-dots" data-ao-dots role="tablist" aria-label="スライド切替"></div>
    </div>
    <p class="ao-hero-note">※一部特典には条件がございます。クリックして詳細をご確認ください。</p>
</section>

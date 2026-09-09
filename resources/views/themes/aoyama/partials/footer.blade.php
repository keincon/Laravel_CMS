@php
    $footerColumns = [
        ['slug' => 'footer-card', 'title' => 'カードをつくる'],
        ['slug' => 'footer-service', 'title' => 'サービス・特典'],
        ['slug' => 'footer-cashing', 'title' => 'キャッシング'],
        ['slug' => 'footer-member', 'title' => '会員向け'],
        ['slug' => 'footer-company', 'title' => '企業情報'],
    ];

    $footerMenus = \App\Models\Menu::query()
        ->whereIn('slug', collect($footerColumns)->pluck('slug'))
        ->with(['items' => fn ($q) => $q->whereNull('parent_id')->orderBy('sort_order')->with('page')])
        ->get()
        ->keyBy('slug');
@endphp

<footer class="ao-footer">
    <div class="ao-container">
        <div class="ao-footer-grid">
            @foreach ($footerColumns as $column)
                @php $menu = $footerMenus->get($column['slug']); @endphp
                <div>
                    <h3>{{ $column['title'] }}</h3>
                    <ul>
                        @if ($menu && $menu->items->isNotEmpty())
                            @foreach ($menu->items as $item)
                                <li><a href="{{ $item->href() }}">{{ $item->title }}</a></li>
                            @endforeach
                        @elseif ($column['slug'] === 'footer-card')
                            <li><a href="{{ url('/card') }}">カード一覧</a></li>
                            <li><a href="{{ url('/card#issuer-life') }}">ライフカード発行</a></li>
                            <li><a href="{{ url('/card#issuer-smbc') }}">三井住友カード発行</a></li>
                            <li><a href="{{ url('/card#issuer-capital') }}">青山キャピタル発行</a></li>
                        @elseif ($column['slug'] === 'footer-service')
                            <li><a href="{{ url('/used') }}">割引・ポイント</a></li>
                            <li><a href="{{ url('/biz-yuutai') }}">カード優待特典</a></li>
                            <li><a href="{{ url('/campaign') }}">キャンペーン</a></li>
                        @elseif ($column['slug'] === 'footer-cashing')
                            <li><a href="{{ url('/cashing') }}">キャッシングについて</a></li>
                            <li><a href="{{ url('/cashing-hurry') }}">お急ぎの方</a></li>
                        @elseif ($column['slug'] === 'footer-member')
                            <li><a href="{{ url('/support') }}">カード会員の方</a></li>
                            <li><a href="{{ url('/support-userguide-funshitsu-php') }}">カード紛失・盗難</a></li>
                            <li><a href="{{ url('/news') }}">お知らせ</a></li>
                        @else
                            <li><a href="{{ url('/company-about') }}">会社概要</a></li>
                            <li><a href="{{ url('/faq') }}">よくあるご質問</a></li>
                            <li><a href="{{ url('/company-privacy-php') }}">プライバシーポリシー</a></li>
                        @endif
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="ao-footer-bottom">
            <p>Copyright © {{ date('Y') }} 株式会社青山キャピタル（デモ）</p>
            <p>本テーマは aoyama-card.co.jp を参考にしたデモです。</p>
        </div>
    </div>
</footer>

<a class="ao-faq-float" href="{{ url('/faq') }}">よくあるご質問</a>

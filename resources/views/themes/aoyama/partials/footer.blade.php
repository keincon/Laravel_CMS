<footer class="ao-footer">
    <div class="ao-container">
        <div class="ao-footer-grid">
            <div>
                <h3>カードをつくる</h3>
                <ul>
                    <li><a href="{{ url('/card') }}">カード一覧</a></li>
                    <li><a href="{{ url('/card/life') }}">ライフカード発行</a></li>
                    <li><a href="{{ url('/card/visa') }}">三井住友カード発行</a></li>
                    <li><a href="{{ url('/card/capital') }}">青山キャピタル発行</a></li>
                </ul>
            </div>
            <div>
                <h3>サービス・特典</h3>
                <ul>
                    <li><a href="{{ url('/service') }}">割引・ポイント</a></li>
                    <li><a href="{{ url('/service/benefits') }}">カード優待特典</a></li>
                    <li><a href="{{ url('/campaign') }}">キャンペーン</a></li>
                </ul>
            </div>
            <div>
                <h3>キャッシング</h3>
                <ul>
                    <li><a href="{{ url('/cashless') }}">キャッシングについて</a></li>
                    <li><a href="{{ url('/cashless/urgent') }}">お急ぎの方</a></li>
                </ul>
            </div>
            <div>
                <h3>会員向け</h3>
                <ul>
                    <li><a href="{{ url('/membership') }}">カード会員の方</a></li>
                    <li><a href="{{ url('/card-lost') }}">カード紛失・盗難</a></li>
                    <li><a href="{{ url('/news') }}">お知らせ</a></li>
                </ul>
            </div>
            <div>
                <h3>企業情報</h3>
                <ul>
                    <li><a href="{{ url('/company') }}">会社概要</a></li>
                    <li><a href="{{ url('/faq') }}">よくあるご質問</a></li>
                    <li><a href="{{ url('/privacy') }}">プライバシーポリシー</a></li>
                </ul>
            </div>
        </div>

        <div class="ao-footer-bottom">
            <p>Copyright © Aoyama Capital Co., Ltd. (demo recreation)</p>
            <p>本テーマは aoyama-card.co.jp を参考にしたデモです。</p>
        </div>
    </div>
</footer>

<a class="ao-faq-float" href="{{ url('/faq') }}">よくあるご質問</a>

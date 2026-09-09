<?php

declare(strict_types=1);

namespace App\Services\Seeders;

use App\Enums\ContentStatus;
use App\Enums\MetaType;
use App\Models\Category;
use App\Models\CmsSetting;
use App\Models\Content;
use App\Models\ContentMeta;
use App\Models\ContentType;
use App\Models\DynamicPageSetting;
use App\Models\Media;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Taxonomy;
use App\Models\Term;
use App\Models\User;
use App\Services\Content\ContentService;
use App\Services\DynamicPageService;
use App\Services\LaravelPressBootstrapService;
use App\Services\RolePermissionService;
use App\Services\Themes\ThemeManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Seeds the full public IA modeled after https://www.aoyama-card.co.jp/
 * (青山キャピタル / AOYAMAカード) — hub + product + support + policy pages and news.
 */
final class AoyamaCardSiteSeeder
{
    public function __construct(
        private readonly ContentService $contents,
        private readonly LaravelPressBootstrapService $bootstrap,
        private readonly RolePermissionService $roles,
        private readonly ThemeManager $themes,
    ) {}

    /**
     * @return array{pages: int, posts: int, terms: int, menu_items: int, media: int}
     */
    public function seed(?User $author = null, bool $fresh = false): array
    {
        $this->bootstrap->seedBuiltins();
        $this->roles->syncDefaults();
        $this->themes->discover();
        if ($this->themes->has('aoyama')) {
            $this->themes->activate('aoyama');
        }

        $author ??= User::role('Administrator')->orderBy('id')->first()
            ?? User::query()->orderBy('id')->first();

        if (! $author) {
            $author = User::factory()->create([
                'username' => 'aoyama-editor',
                'name' => '青山キャピタル編集部',
            ]);
            $author->assignRole('Administrator');
        }

        if ($fresh) {
            $this->purge();
        }

        $mediaCount = $this->seedThemeImages($author);

        return DB::transaction(function () use ($author, $mediaCount): array {
            $this->seedSiteSettings();
            $this->seedDynamicNews();
            $this->seedDynamicCampaign();
            $termIds = $this->seedTerms();
            $pages = $this->seedPages($author);
            $posts = $this->seedPosts($author, $termIds);
            $campaigns = $this->seedCampaigns($author);
            $menuItems = $this->seedMenu($pages);

            return [
                'pages' => count($pages),
                'posts' => count($posts) + count($campaigns),
                'terms' => count($termIds),
                'menu_items' => $menuItems,
                'media' => $mediaCount,
            ];
        });
    }

    /**
     * Map the blog dynamic page to /news (公式お知らせ一覧) and drop any static /news page.
     */
    private function seedDynamicNews(): void
    {
        app(DynamicPageService::class)->ensureDefaults();

        DynamicPageSetting::query()->where('type', 'blog')->update([
            'label' => 'お知らせ',
            'title' => 'お知らせ',
            'description' => '青山キャピタルからのお知らせ一覧です。',
            'url_path' => '/news',
            'posts_per_page' => 50,
            'layout' => 'list',
            'is_enabled' => true,
        ]);

        DynamicPageSetting::query()->where('type', 'post')->update([
            'label' => 'お知らせ詳細',
            'title' => 'お知らせ',
            'is_enabled' => true,
        ]);

        DynamicPageSetting::query()->where('type', 'archive')->update([
            'label' => 'バックナンバー',
            'title' => 'バックナンバー',
            'is_enabled' => true,
            'posts_per_page' => 50,
        ]);

        DynamicPageSetting::forgetCache();

        Content::withTrashed()->where('slug', 'news')->forceDelete();
        Page::query()->where('slug', 'news')->delete();
    }

    /**
     * Map the campaign dynamic page to /campaign and ensure the campaign content type exists.
     */
    private function seedDynamicCampaign(): void
    {
        ContentType::query()->updateOrCreate(
            ['slug' => 'campaign'],
            [
                'name' => 'campaign',
                'singular_label' => 'キャンペーン',
                'plural_label' => 'キャンペーン',
                'supports' => [
                    'title', 'editor', 'excerpt', 'author', 'featured_image',
                    'revisions', 'custom_fields', 'archive',
                ],
                'capabilities' => [
                    'create_posts', 'edit_posts', 'edit_others_posts',
                    'publish_posts', 'delete_posts',
                ],
                'hierarchical' => false,
                'has_archive' => true,
                'public' => true,
                'show_in_rest' => true,
                'rest_base' => 'campaigns',
                'menu_icon' => 'gift',
                'menu_position' => 8,
                'is_builtin' => false,
            ]
        );

        app(DynamicPageService::class)->ensureDefaults();

        DynamicPageSetting::query()->where('type', 'campaign')->update([
            'label' => 'キャンペーン',
            'title' => 'キャンペーン',
            'description' => '開催中の入会・リボ・ポイントキャンペーンをご案内します。',
            'url_path' => '/campaign',
            'posts_per_page' => 20,
            'layout' => 'list',
            'is_enabled' => true,
        ]);

        DynamicPageSetting::query()->where('type', 'campaign_item')->update([
            'label' => 'キャンペーン詳細',
            'title' => 'キャンペーン',
            'is_enabled' => true,
        ]);

        DynamicPageSetting::forgetCache();

        Content::withTrashed()
            ->whereIn('slug', [
                'campaign',
                'campaign-u26031-php',
                'campaign-u26041-php',
            ])
            ->whereHas('type', fn ($q) => $q->where('slug', 'page'))
            ->forceDelete();
        Page::query()->whereIn('slug', [
            'campaign',
            'campaign-u26031-php',
            'campaign-u26041-php',
        ])->delete();
    }

    private function seedSiteSettings(): void
    {
        CmsSetting::setValue('site_name', '青山キャピタル');
        CmsSetting::setValue(
            'site_description',
            '「洋服の青山」などでのお買い物が毎回お得に！ポイントもダブルで貯まるクレジットカード「AOYAMAカード」を発行・ご案内する青山グループの金融サービスサイト。'
        );
        CmsSetting::setValue('site_url', rtrim((string) config('app.url'), '/').'/');
        CmsSetting::setValue('language', 'ja');
        CmsSetting::setValue('timezone', 'Asia/Tokyo');

        if (class_exists(\App\Models\SeoSetting::class)) {
            $seo = \App\Models\SeoSetting::query()->first();
            if ($seo) {
                $seo->fill([
                    'og_site_name' => '青山キャピタル',
                    'organization_name' => '株式会社青山キャピタル',
                ]);
                $seo->save();
            }
        }
    }

    /**
     * @return array<string, int>
     */
    private function seedTerms(): array
    {
        $map = [];
        $categoryTax = Taxonomy::query()->where('slug', 'category')->first();
        if (! $categoryTax) {
            return $map;
        }

        foreach ([
            ['name' => '重要なお知らせ', 'slug' => 'important'],
            ['name' => '規約・手数料改定', 'slug' => 'terms'],
            ['name' => 'サービス変更', 'slug' => 'service'],
            ['name' => 'キャンペーン', 'slug' => 'campaign'],
            ['name' => 'カード商品', 'slug' => 'card'],
            ['name' => '会員サポート', 'slug' => 'support'],
        ] as $row) {
            $term = Term::query()->updateOrCreate(
                ['taxonomy_id' => $categoryTax->id, 'slug' => $row['slug']],
                ['name' => $row['name'], 'description' => $row['name']]
            );
            $map[$row['slug']] = $term->id;

            Category::query()->firstOrCreate(
                ['slug' => $row['slug']],
                ['name' => $row['name'], 'description' => $row['name']]
            );
        }

        return $map;
    }

    /**
     * Full public page tree (paths mirror aoyama-card.co.jp).
     *
     * @return list<array{slug: string, path: string, title: string, excerpt: string, body: string}>
     */
    private function pageDefinitions(): array
    {
        $src = 'https://www.aoyama-card.co.jp';

        $p = function (string $path, string $title, string $excerpt, string $body) use ($src): array {
            if ($path === '/') {
                $slug = 'home';
            } else {
                $slug = trim(str_replace(['/', '_', '.'], '-', trim($path, '/')), '-');
                $slug = Str::slug($slug) ?: 'page';
            }

            $html = $body;
            if ($path !== '/' && trim(strip_tags($body)) !== '') {
                $html .= "<p class=\"muted\"><small>参考: <a href=\"{$src}{$path}\" rel=\"noopener\">aoyama-card.co.jp{$path}</a></small></p>";
            }

            return [
                'slug' => $slug,
                'path' => $path,
                'title' => $title,
                'excerpt' => $excerpt,
                'body' => $html,
            ];
        };

        return [
            $p('/', 'トップ', 'ライフスタイルに合わせたピッタリの1枚を。', ''),
            $p('/card/', 'カードをつくる', '発行会社別のAOYAMAカード・BLUE ROSE CARD一覧。PiTaPa・SUGOCA一体型もあります。', <<<'HTML'
<nav class="ao-issuer-jump" aria-label="発行会社">
  <a href="#issuer-life">ライフカード株式会社 発行カード</a>
  <a href="#issuer-smbc">三井住友カード株式会社 発行カード</a>
  <a href="#issuer-capital">株式会社青山キャピタル 発行カード</a>
</nav>
<p class="ao-lead">PiTaPa機能付カード、SUGOCA機能付きカードもございます。</p>

<section class="ao-movie">
  <div class="ao-movie-copy">
    <h2>AOYAMAカードをシンプルに分かりやすく解説します</h2>
    <p>※ 音量にご注意ください。</p>
    <a class="ao-btn is-outline" href="https://www.aoyama-card.co.jp/assets/movies/about_aoyama_card_movie.mp4" rel="noopener" target="_blank">動画を見る</a>
  </div>
  <a class="ao-movie-thumb" href="https://www.aoyama-card.co.jp/assets/movies/about_aoyama_card_movie.mp4" rel="noopener" target="_blank">
    <img src="/themes/aoyama/assets/images/movie-thumb.png" alt="青山カード紹介動画" width="480" height="270" loading="lazy">
  </a>
</section>

<section id="issuer-life" class="ao-issuer-block">
  <h2>ライフカード株式会社 発行カード</h2>
  <article class="ao-catalog-card">
    <img src="/themes/aoyama/assets/images/card-life.png" alt="AOYAMAライフマスターカード" width="160" height="100" loading="lazy">
    <div>
      <h3>AOYAMAライフマスターカード</h3>
      <p>「洋服の青山」でのお買い物がおトクになるスタンダードなカード</p>
      <p class="ao-wpoint"><span>Wポイント</span> AOYAMAポイント + サンクスポイント</p>
      <div class="ao-catalog-actions">
        <a class="ao-btn" href="https://www.lifecard.co.jp/Aoyama/lp/aoyamacard/?utm_source=aoyama_capital" rel="noopener" target="_blank">カードを申し込む</a>
        <a class="ao-btn is-outline" href="/card-aoyama-life">詳細を見る</a>
      </div>
    </div>
  </article>
  <article class="ao-catalog-card">
    <img src="/themes/aoyama/assets/images/card-bluerose-life.png" alt="BLUE ROSE CARD（ライフカード発行）" width="160" height="100" loading="lazy">
    <div>
      <h3>BLUE ROSE CARD（ライフカード発行）</h3>
      <p>レディース商品がさらにおトクになるカード</p>
      <p class="ao-wpoint"><span>Wポイント</span> ROSEポイント + サンクスポイント</p>
      <div class="ao-catalog-actions">
        <a class="ao-btn" href="https://www.lifecard.co.jp/Aoyama/lp/bluerose/" rel="noopener" target="_blank">カードを申し込む</a>
        <a class="ao-btn is-outline" href="/card-bluerose-life">詳細を見る</a>
      </div>
    </div>
  </article>
</section>

<section id="issuer-smbc" class="ao-issuer-block">
  <h2>三井住友カード株式会社 発行カード</h2>
  <article class="ao-catalog-card">
    <img src="/themes/aoyama/assets/images/card-visa.png" alt="AOYAMA VISAカード（IC）" width="160" height="100" loading="lazy">
    <div>
      <h3>AOYAMA VISAカード（IC）</h3>
      <p>「洋服の青山」でのお買い物がおトクになるスタンダードなカード</p>
      <p class="ao-wpoint"><span>Wポイント</span> AOYAMAポイント + Vポイント</p>
      <div class="ao-catalog-actions">
        <a class="ao-btn" href="https://www.smbc-card.com/nyukai/affiliate/aoyama/index.jsp" rel="noopener" target="_blank">カードを申し込む</a>
        <a class="ao-btn is-outline" href="/card-aoyama-visa">詳細を見る</a>
      </div>
    </div>
  </article>
  <article class="ao-catalog-card">
    <img src="/themes/aoyama/assets/images/card-pitapa.png" alt="AOYAMA PiTaPaカード" width="160" height="100" loading="lazy">
    <div>
      <h3>AOYAMA PiTaPaカード</h3>
      <p>電車やバスでの移動もお買物もこれ1枚で！ PiTaPaがついた多機能カード</p>
      <p class="ao-wpoint"><span>Wポイント</span> AOYAMAポイント + Vポイント</p>
      <div class="ao-catalog-actions">
        <a class="ao-btn" href="https://www.smbc-card.com/nyukai/affiliate/aoyama/index.jsp" rel="noopener" target="_blank">カードを申し込む</a>
        <a class="ao-btn is-outline" href="/card-aoyama-visa">詳細を見る</a>
      </div>
    </div>
  </article>
  <article class="ao-catalog-card">
    <img src="/themes/aoyama/assets/images/card-visa-bluerose.png" alt="BLUE ROSE CARD（三井住友カード発行）" width="160" height="100" loading="lazy">
    <div>
      <h3>BLUE ROSE CARD（三井住友カード発行）</h3>
      <p>レディース商品がさらにおトクになるカード</p>
      <p class="ao-wpoint"><span>Wポイント</span> ROSEポイント + Vポイント</p>
      <div class="ao-catalog-actions">
        <a class="ao-btn" href="https://www.smbc-card.com/nyukai/affiliate/aoyama_brc/index.jsp" rel="noopener" target="_blank">カードを申し込む</a>
        <a class="ao-btn is-outline" href="/card-bluerose-visa">詳細を見る</a>
      </div>
    </div>
  </article>
</section>

<section id="issuer-capital" class="ao-issuer-block">
  <h2>株式会社青山キャピタル 発行カード</h2>
  <article class="ao-catalog-card">
    <img src="/themes/aoyama/assets/images/card-capital.png" alt="AOYAMAカード" width="160" height="100" loading="lazy">
    <div>
      <h3>AOYAMAカード</h3>
      <p>「洋服の青山」でのお買い物がおトクになるスタンダードなカード</p>
      <p class="ao-wpoint"><span>Wポイント</span> AOYAMAポイント + UCポイント</p>
      <div class="ao-catalog-actions">
        <a class="ao-btn" href="/membership">カードを申し込む</a>
        <a class="ao-btn is-outline" href="/card-capital">詳細を見る</a>
      </div>
    </div>
  </article>
  <article class="ao-catalog-card">
    <img src="/themes/aoyama/assets/images/card-sugoca.png" alt="AOYAMAマスターカードSUGOCA" width="160" height="100" loading="lazy">
    <div>
      <h3>AOYAMAマスターカードSUGOCA</h3>
      <p>JR九州のICカード「SUGOCA」と「AOYAMAカード」が一体になった多機能カード</p>
      <p class="ao-wpoint"><span>Wポイント</span> AOYAMAポイント + UCポイント</p>
      <div class="ao-catalog-actions">
        <a class="ao-btn" href="/membership">カードを申し込む</a>
        <a class="ao-btn is-outline" href="/card-capital">詳細を見る</a>
      </div>
    </div>
  </article>
  <article class="ao-catalog-card">
    <img src="/themes/aoyama/assets/images/card-bluerose-capital.png" alt="BLUE ROSE CARD（青山キャピタル発行）" width="160" height="100" loading="lazy">
    <div>
      <h3>BLUE ROSE CARD（青山キャピタル発行）</h3>
      <p>レディース商品がさらにおトクになるカード</p>
      <p class="ao-wpoint"><span>Wポイント</span> ROSEポイント + UCポイント</p>
      <div class="ao-catalog-actions">
        <a class="ao-btn" href="/membership">カードを申し込む</a>
        <a class="ao-btn is-outline" href="/card-bluerose">詳細を見る</a>
      </div>
    </div>
  </article>
  <article class="ao-catalog-card">
    <img src="/themes/aoyama/assets/images/card-papas-mamas.png" alt="Papasカード Mamasカード" width="160" height="100" loading="lazy">
    <div>
      <h3>Papasカード Mamasカード</h3>
      <p>子育てパパ・ママを応援！家族みんながうれしいカード</p>
      <p class="ao-wpoint"><span>Wポイント</span> AOYAMAポイント + UCポイント</p>
      <p class="ao-catalog-note"><a href="/news/news-2023-02-21-papas-mamas">「Papasカード」「Mamasカード」のカード名称・デザイン変更のお知らせ</a></p>
    </div>
  </article>
</section>

<p class="ao-legal-note">※Apple PayはApple Inc.の商標です。 ※「PiTaPa」は株式会社スルッとKANSAIの登録商標です。 ※Android 、 Google Play 、 Google Pay 、 Google ウォレット は Google LLC の商標です。</p>

<aside class="ao-hurry">
  <div>
    <h2>キャッシングについてお急ぎの方</h2>
    <p>カードをお持ちであれば、ATMでなくても電話やインターネット経由でキャッシングをお申込みいただけます。</p>
  </div>
  <a class="ao-btn" href="/cashing-hurry">お急ぎの方はこちら</a>
</aside>
HTML),
            $p('/card/aoyama/life/', 'AOYAMAライフマスターカード', 'ライフカード発行。洋服の青山で2.0%ポイント還元。AOYAMAポイント＋サンクスポイント。', <<<'HTML'
<img class="ao-detail-card" src="/themes/aoyama/assets/images/card-life.png" alt="AOYAMAライフマスターカード" width="220" height="140" loading="lazy">
<p class="ao-lead">洋服の青山でのご利用で2.0%ポイント還元！毎日のお買い物で２つのポイントが貯まるおトクなカード（ライフカード株式会社発行）。</p>
<p>洋服の青山でのお買い物時の割引のほか、クレジット決済で「AOYAMAポイント」と「サンクスポイント」が両方貯まります。</p>
<p><strong>年会費：</strong>入会年度は無料。２年目から1,375円（税込）、家族会員440円（税込）。</p>
<div class="ao-catalog-actions">
  <a class="ao-btn" href="https://www.lifecard.co.jp/Aoyama/lp/aoyamacard/?utm_source=aoyama_capital" rel="noopener" target="_blank">お申込みはこちら</a>
  <a class="ao-btn is-outline" href="/used-preferential">洋服の青山特典</a>
</div>
<h2>Wポイント</h2>
<ul>
<li><strong>AOYAMAポイント</strong> — 洋服の青山で200円につき4ポイント、それ以外の国内ショッピング100円につき1ポイント。1ポイント＝1円で洋服の青山で利用可。</li>
<li><strong>サンクスポイント</strong> — カードショッピングご請求金額1,000円（税込）につき1.0ポイント。ギフトカード交換やキャッシュバック等に利用可。</li>
</ul>
<h2>便利な機能</h2>
<ul>
<li>「iD」「Apple Pay」対応</li>
<li>ETCカード年会費無料（ご利用分は本カード決済・Wポイント対象）</li>
<li>インターネットショッピング向け本人認証サービス（LIFE-Web Desk登録が必要）</li>
</ul>
<aside class="ao-hurry">
  <div><h2>キャッシングについてお急ぎの方</h2><p>電話やインターネット経由でキャッシングをお申込みいただけます。</p></div>
  <a class="ao-btn" href="/cashing-hurry">お急ぎの方はこちら</a>
</aside>
HTML),
            $p('/card/aoyama/visa/', 'AOYAMA VISAカード', '三井住友カード発行。AOYAMAポイント＋Vポイント。PiTaPa一体型あり。', <<<'HTML'
<img class="ao-detail-card" src="/themes/aoyama/assets/images/card-visa.png" alt="AOYAMA VISAカード（IC）" width="220" height="140" loading="lazy">
<p class="ao-lead">洋服の青山でのご利用で2.0%ポイント還元！「AOYAMAポイント」と「Vポイント」が両方貯まるカード（三井住友カード株式会社発行）。</p>
<p><strong>年会費：</strong>入会年度は無料。２年目から1,375円（税込）、家族会員440円（税込）。</p>
<div class="ao-catalog-actions">
  <a class="ao-btn" href="https://www.smbc-card.com/nyukai/affiliate/aoyama/index.jsp" rel="noopener" target="_blank">お申込みはこちら</a>
  <a class="ao-btn is-outline" href="/used-preferential">洋服の青山特典</a>
</div>
<h2>Wポイント</h2>
<ul>
<li><strong>AOYAMAポイント</strong> — 洋服の青山で200円につき4ポイント、それ以外の国内ショッピング100円につき1ポイント。</li>
<li><strong>Vポイント</strong> — ご請求合計200円（税込）ごとに1ポイント（1円相当）。チャージ・キャッシュバック・他社ポイント交換などに利用可。</li>
</ul>
<h2>ETCカード</h2>
<p>年会費550円（税込）。ご入会初年度無料。2年目からも、1年間に1回以上ETC利用のご請求があれば無料。</p>
<h2>AOYAMA PiTaPaカード</h2>
<img class="ao-detail-card" src="/themes/aoyama/assets/images/card-pitapa.png" alt="AOYAMA PiTaPaカード" width="220" height="140" loading="lazy">
<p>電車・バスやショッピングに使える多機能カード。関西地区を中心にPiTaPa交通エリア・ショッピング加盟店で利用でき、ポストペイ（後払い）です。</p>
<p>※年間1度もPiTaPaサービスをご利用がない場合、維持管理料1,100円（税込）が必要です。</p>
<p>PiTaPaショッピング加盟店では「ショップdeポイント」も貯まり、交通ご利用代金から自動差引き。「洋服の青山」でのご利用は5倍ポイント進呈。</p>
<aside class="ao-hurry">
  <div><h2>キャッシングについてお急ぎの方</h2><p>電話やインターネット経由でキャッシングをお申込みいただけます。</p></div>
  <a class="ao-btn" href="/cashing-hurry">お急ぎの方はこちら</a>
</aside>
HTML),
            $p('/card/bluerose/life/', 'BLUE ROSE CARD（ライフカード発行）', 'レディース向け。ROSEポイント＋サンクスポイント。', <<<'HTML'
<img class="ao-detail-card" src="/themes/aoyama/assets/images/card-bluerose-life.png" alt="BLUE ROSE CARD（ライフカード発行）" width="220" height="140" loading="lazy">
<p class="ao-lead">BlueRose 花言葉は「不可能を可能にする。夢叶う。」「洋服の青山」「SUIT SQUARE」のレディース商品がさらにおトクになるカードです。</p>
<p>Wポイント：「ROSEポイント」と「サンクスポイント」が両方貯まります。</p>
<p><strong>年会費：</strong>入会年度は無料。２年目から1,375円（税込）、家族会員440円（税込）。</p>
<div class="ao-catalog-actions">
  <a class="ao-btn" href="https://www.lifecard.co.jp/Aoyama/lp/bluerose/" rel="noopener" target="_blank">お申込みはこちら</a>
</div>
<h2>特典イメージ</h2>
<ul>
<li>BLUE ROSE CARDご提示でいつでも割引（対象レディース商品）</li>
<li>新規お届け時と会員一年間継続毎に特別商品割引券3,000円（税込）・特別商品優待券10%OFF</li>
<li>ETCカード年会費無料、「iD」「Apple Pay」対応</li>
</ul>
<aside class="ao-hurry">
  <div><h2>キャッシングについてお急ぎの方</h2><p>電話やインターネット経由でキャッシングをお申込みいただけます。</p></div>
  <a class="ao-btn" href="/cashing-hurry">お急ぎの方はこちら</a>
</aside>
HTML),
            $p('/card/bluerose/visa/', 'BLUE ROSE CARD（三井住友カード発行）', 'レディース向け。ROSEポイント＋Vポイント。', <<<'HTML'
<img class="ao-detail-card" src="/themes/aoyama/assets/images/card-visa-bluerose.png" alt="BLUE ROSE CARD（三井住友カード発行）" width="220" height="140" loading="lazy">
<p class="ao-lead">レディース商品がさらにおトクになるカード。Wポイント：ROSEポイント + Vポイント（三井住友カード株式会社発行）。</p>
<p><strong>年会費：</strong>入会年度は無料。２年目から1,375円（税込）、家族会員440円（税込）。</p>
<div class="ao-catalog-actions">
  <a class="ao-btn" href="https://www.smbc-card.com/nyukai/affiliate/aoyama_brc/index.jsp" rel="noopener" target="_blank">お申込みはこちら</a>
</div>
<ul>
<li>青山グループ店舗での割引・ROSEポイントサービス</li>
<li>「iD」「Apple Pay」対応</li>
<li>ETCカード（条件により年会費無料）</li>
</ul>
<aside class="ao-hurry">
  <div><h2>キャッシングについてお急ぎの方</h2><p>電話やインターネット経由でキャッシングをお申込みいただけます。</p></div>
  <a class="ao-btn" href="/cashing-hurry">お急ぎの方はこちら</a>
</aside>
HTML),
            $p('/card/capital/', 'AOYAMAカード（青山キャピタル発行）・SUGOCA', '青山キャピタル発行。AOYAMAポイント＋UCポイント。SUGOCA一体型あり。', <<<'HTML'
<img class="ao-detail-card" src="/themes/aoyama/assets/images/card-capital.png" alt="AOYAMAカード" width="220" height="140" loading="lazy">
<p class="ao-lead">洋服の青山で、毎日のお買い物で、ダブルポイントがうれしいおトクなクレジットカード（株式会社青山キャピタル発行）。</p>
<p>クレジット決済で「UCポイント」と「AOYAMAポイント」が両方貯まります。</p>
<p><strong>年会費：</strong>入会年度は無料。２年目から1,375円（税込）、家族会員440円（税込）。</p>
<div class="ao-catalog-actions">
  <a class="ao-btn" href="/membership">お申込みはこちら</a>
  <a class="ao-btn is-outline" href="/used-preferential">洋服の青山特典</a>
</div>
<h2>Wポイント</h2>
<ul>
<li><strong>UCポイント</strong> — カードショッピングご請求合計1,000円＝1ポイント（1ポイント＝5円相当）。請求額充当・ギフト交換などに利用可。</li>
<li><strong>AOYAMAポイント</strong> — 洋服の青山で200円＝4ポイント、それ以外の国内ショッピング100円＝1ポイント。</li>
</ul>
<h2>ETCカード</h2>
<p>AOYAMAカードならETCカード年会費無料。ご利用分は本カード決済でWポイント対象。</p>
<h2>AOYAMAマスターカードSUGOCA</h2>
<img class="ao-detail-card" src="/themes/aoyama/assets/images/card-sugoca.png" alt="AOYAMAマスターカードSUGOCA" width="220" height="140" loading="lazy">
<p>JR九州のICカード「SUGOCA」一体型。チャージで列車・バスや電子マネー加盟店に利用でき、オートチャージにも対応。JRキューポ加盟店ではJRキューポも貯まります（洋服の青山は対象外）。</p>
<aside class="ao-hurry">
  <div><h2>キャッシングについてお急ぎの方</h2><p>電話やインターネット経由でキャッシングをお申込みいただけます。</p></div>
  <a class="ao-btn" href="/cashing-hurry">お急ぎの方はこちら</a>
</aside>
HTML),
            $p('/card/bluerose/', 'BLUE ROSE CARD（青山キャピタル発行）', 'レディース向け。ROSEポイント＋UCポイント。', <<<'HTML'
<img class="ao-detail-card" src="/themes/aoyama/assets/images/card-bluerose-capital.png" alt="BLUE ROSE CARD（青山キャピタル発行）" width="220" height="140" loading="lazy">
<p class="ao-lead">レディース商品がさらにおトクになるカード。Wポイント：ROSEポイント + UCポイント（株式会社青山キャピタル発行）。</p>
<p><strong>年会費：</strong>入会年度は無料。２年目から1,375円（税込）、家族会員440円（税込）。</p>
<div class="ao-catalog-actions">
  <a class="ao-btn" href="/membership">お申込みはこちら</a>
</div>
<ul>
<li>青山グループ店舗での割引・ROSEポイントサービス</li>
<li>「iD」「Apple Pay」対応</li>
<li>ETCカード年会費無料（条件は公式案内に準拠）</li>
</ul>
<aside class="ao-hurry">
  <div><h2>キャッシングについてお急ぎの方</h2><p>電話やインターネット経由でキャッシングをお申込みいただけます。</p></div>
  <a class="ao-btn" href="/cashing-hurry">お急ぎの方はこちら</a>
</aside>
HTML),
            $p('/card/about_pin.html', '暗証番号について', 'カード暗証番号の確認・変更のご案内。', <<<'HTML'
<h1>暗証番号について</h1>
<p>暗証番号の照会・変更は発行会社のWebサービスまたはお電話で受付しています。詳細は<a href="/support-userguide-password">暗証番号照会・変更</a>をご確認ください。</p>
HTML),
            $p('/card/userguide/simulation_c/', 'キャッシングシミュレーション', 'キャッシングご利用時の返済シミュレーション。', <<<'HTML'
<h1>キャッシングシミュレーション</h1>
<p>キャッシングご利用時の利息・返済イメージを確認できます。実際のご利用条件は各発行会社の案内に従ってください。</p>
HTML),
            $p('/card/userguide/simulation_r/', 'リボ払いシミュレーション', 'ショッピングリボのご利用シミュレーション。', <<<'HTML'
<h1>リボ払いシミュレーション</h1>
<p>ショッピングリボ払いのご利用イメージを確認できます。手数料率は発行会社・時期により異なります。</p>
HTML),
            $p('/used/', 'サービス・特典', 'カードをより有効活用するためのかしこい使い方。', <<<'HTML'
<h1>サービス・特典</h1>
<ul>
<li><a href="/used-preferential">洋服の青山の特典について</a></li>
<li><a href="/biz-yuutai">カード優待特典</a></li>
<li><a href="/used-aoyama-point">AOYAMAポイント／ROSEポイント</a></li>
<li><a href="/used-aoyama-pay">AOYAMA Pay</a></li>
<li><a href="/used-shopping">ショッピング</a></li>
<li><a href="/used-shopping-point">ショッピングポイント</a></li>
<li><a href="/cashing">キャッシング</a></li>
<li><a href="/used-flow">ご利用代金のお支払い</a></li>
<li><a href="/used-etc">ETCカード</a></li>
<li><a href="/campaign">キャンペーン</a></li>
</ul>
HTML),
            $p('/used/preferential/', '洋服の青山の特典について', 'いつでも5%OFF、誕生日月10%OFF。割引券・優待券。', <<<'HTML'
<h1>洋服の青山の特典について</h1>
<p>カード提示でいつでも5％OFF、誕生日月は10％OFF。入会時・毎年、特別商品割引券（3,000円）と特別商品優待券（10％OFF）をプレゼント。</p>
<p>BLUE ROSE CARDは別途、割引後3,190円以上のレディース商品5％OFF特典あり。</p>
HTML),
            $p('/used/aoyama_point/', 'AOYAMAポイント／ROSEポイントについて', '洋服の青山等で利用できるポイントプログラム。', <<<'HTML'
<h1>AOYAMAポイント／ROSEポイントについて</h1>
<p>店頭決済は200円につき4ポイント。店外利用は100円につき1ポイント。1ポイント＝1円相当。有効期限は付与後最初の4月1日から3年。</p>
HTML),
            $p('/used/aoyama_pay/', 'AOYAMA Payについて', '青山アプリとAOYAMA CARDを紐付けた店頭キャッシュレス決済。', <<<'HTML'
<h1>AOYAMA Payについて</h1>
<p>洋服の青山の店頭で、「青山アプリ」と「AOYAMA CARD」を紐付けてお支払いができるキャッシュレス決済サービスです。</p>
HTML),
            $p('/used/shopping/', 'ショッピングについて', '国内・海外でのカードショッピングのご案内。', <<<'HTML'
<h1>ショッピングについて</h1>
<p>キャッシュレスで便利にお買物。日本国内はもちろん、海外でもショッピングをお楽しみください。公共料金支払いにもご利用いただけます。</p>
HTML),
            $p('/used/shopping_point/', 'ショッピングポイントについて', 'サンクス／V／UCなど発行会社別ショッピングポイント。', <<<'HTML'
<h1>ショッピングポイントについて</h1>
<p>発行会社ごとにサンクスポイント、Vポイント、UCポイントが貯まります。AOYAMAポイント／ROSEポイントと合わせたダブルポイントが特徴です。</p>
HTML),
            $p('/used/etc/', 'ETCカードについて', 'ETCカードのお申込み・年会費のご案内。', <<<'HTML'
<h1>ETCカードについて</h1>
<p>ETCカードのお申込み・年会費は発行会社により異なります。多くのAOYAMAカードでETC年会費無料です。詳細は各カードページまたは会員サポートをご確認ください。</p>
HTML),
            $p('/used/flow/', 'ご利用代金のお支払いについて', 'お支払い方法・支払日のご案内。', <<<'HTML'
<h1>ご利用代金のお支払いについて</h1>
<p>一括払い、分割払い、リボ払いなど各種お支払方法でお客様の生活を応援します。支払日は発行会社により異なります。</p>
HTML),
            $p('/used/applepay/', 'Apple Payについて', 'Apple Payでのお支払い。', <<<'HTML'
<h1>Apple Payについて</h1>
<p>対応カードをApple Payに設定すると、店頭やアプリでタッチ決済をご利用いただけます（発行会社・端末により異なります）。</p>
HTML),
            $p('/used/googlepay/', 'Google Payについて', 'Google Pay / Google ウォレットでのお支払い。', <<<'HTML'
<h1>Google Payについて</h1>
<p>対応カードをGoogleウォレットに設定すると、店頭でタッチ決済をご利用いただけます。</p>
HTML),
            $p('/biz_yuutai/', 'カード優待特典', '会員限定の旅行・フィットネス・サブスク等の優待。', <<<'HTML'
<p class="ao-lead">優待のご利用には洋服の青山「青山アプリ」への登録・ログインが必要です。アプリログイン後、「会員証」内のAOYAMAカードバナーよりご利用ください。</p>
<p class="ao-note">※マーク付きは BLUE ROSE CARD 会員様限定の優待です。</p>

<div class="ao-filter" data-ao-filter>
  <button type="button" class="ao-filter-chip is-active" data-filter="all">すべて</button>
  <button type="button" class="ao-filter-chip" data-filter="travel">トラベル</button>
  <button type="button" class="ao-filter-chip" data-filter="leisure">レジャー</button>
  <button type="button" class="ao-filter-chip" data-filter="shopping">ショッピング</button>
  <button type="button" class="ao-filter-chip" data-filter="gourmet">グルメ</button>
  <button type="button" class="ao-filter-chip" data-filter="beauty">美容・健康</button>
  <button type="button" class="ao-filter-chip" data-filter="life">暮らし</button>
  <button type="button" class="ao-filter-chip" data-filter="hobby">趣味</button>
  <button type="button" class="ao-filter-chip" data-filter="business">ビジネス</button>
  <button type="button" class="ao-filter-chip" data-filter="subsc">サブスク</button>
</div>

<div class="ao-coupon-grid">
  <article class="ao-coupon" data-cats="beauty hobby"><span class="ao-coupon-tag">美容・健康</span><h3>JOYFIT 1Dayチケット</h3><p>AOYAMAカード会員限定。同施設内なら1日何度でもご利用可能。</p></article>
  <article class="ao-coupon" data-cats="beauty hobby"><span class="ao-coupon-tag is-rose">BLUE ROSE</span><h3>JOYFIT YOGA 法人優待</h3><p>女性専用溶岩ホットヨガが月会費9,878円（税込）の法人優待価格。</p></article>
  <article class="ao-coupon" data-cats="beauty hobby"><span class="ao-coupon-tag">美容・健康</span><h3>JOYFIT / JOYFIT24</h3><p>スポーツジムが月会費6,578円（税込）の法人優待価格で。</p></article>
  <article class="ao-coupon" data-cats="hobby subsc"><span class="ao-coupon-tag">サブスク</span><h3>WOWOW視聴料 OFF</h3><p>視聴料が総額1,100円（税込）OFF。</p></article>
  <article class="ao-coupon" data-cats="travel leisure"><span class="ao-coupon-tag">トラベル</span><h3>トラベルイン スキー＆スノボ</h3><p>ツアーが500円OFF。</p></article>
  <article class="ao-coupon" data-cats="travel leisure"><span class="ao-coupon-tag">レジャー</span><h3>ラク得パック</h3><p>JR・新幹線or飛行機と宿泊セットが500円OFF。</p></article>
  <article class="ao-coupon" data-cats="travel leisure"><span class="ao-coupon-tag">トラベル</span><h3>旅っくす</h3><p>国内旅行500円OFF、海外・クルーズ1,000円OFF。</p></article>
  <article class="ao-coupon" data-cats="leisure subsc"><span class="ao-coupon-tag">サブスク</span><h3>オリックスカーリース</h3><p>新車はドラレコ、中古車はギフトカードプレゼント。</p></article>
  <article class="ao-coupon" data-cats="hobby subsc"><span class="ao-coupon-tag">趣味</span><h3>honto（ホント）</h3><p>電子書籍ストアで使える500円OFFクーポン。</p></article>
  <article class="ao-coupon" data-cats="leisure business"><span class="ao-coupon-tag">ビジネス</span><h3>オリックスレンタカー</h3><p>特別料金でご利用いただけます。</p></article>
  <article class="ao-coupon" data-cats="hobby subsc"><span class="ao-coupon-tag">サブスク</span><h3>ペトコトフーズ</h3><p>お試しBOXが86％OFF、定期便も初回30％OFF。</p></article>
  <article class="ao-coupon" data-cats="beauty life"><span class="ao-coupon-tag">美容・健康</span><h3>ヘアカラー専門店 fufu</h3><p>炭酸泉シャンプーが初回無料。</p></article>
  <article class="ao-coupon" data-cats="life business"><span class="ao-coupon-tag">暮らし</span><h3>うさちゃんクリーニング</h3><p>クリーニング料金20％OFFクーポン。</p></article>
  <article class="ao-coupon" data-cats="life business"><span class="ao-coupon-tag">暮らし</span><h3>白洋舍公式アプリ</h3><p>新規ご登録で500円割引クーポン。</p></article>
  <article class="ao-coupon" data-cats="life subsc"><span class="ao-coupon-tag">サブスク</span><h3>アクアクララ</h3><p>新規入会で12Lボトル4本分無料チケット。</p></article>
  <article class="ao-coupon" data-cats="leisure business"><span class="ao-coupon-tag">レジャー</span><h3>ルートインホテルズ</h3><p>宿泊料金10％OFF＆朝食無料サービス。</p></article>
  <article class="ao-coupon" data-cats="gourmet subsc"><span class="ao-coupon-tag">グルメ</span><h3>キリン ホームタップ</h3><p>月額基本料金が3か月無料。</p></article>
  <article class="ao-coupon" data-cats="beauty life"><span class="ao-coupon-tag">美容・健康</span><h3>アデランス</h3><p>ポイント増毛250本が1,000円で体験。</p></article>
  <article class="ao-coupon" data-cats="gourmet subsc"><span class="ao-coupon-tag">グルメ</span><h3>パンスク パン旅コース</h3><p>冷凍パン定期便の初回料金1,000円OFF。</p></article>
  <article class="ao-coupon" data-cats="life subsc"><span class="ao-coupon-tag">暮らし</span><h3>おカネレコ</h3><p>家計簿アプリ プレミアム会員版が2カ月無料。</p></article>
  <article class="ao-coupon" data-cats="shopping hobby"><span class="ao-coupon-tag">ショッピング</span><h3>古本市場 / ふるいち</h3><p>中古商品が200円OFF。</p></article>
  <article class="ao-coupon" data-cats="shopping gourmet"><span class="ao-coupon-tag">グルメ</span><h3>喫茶室ルノアール</h3><p>ご飲食代が10％OFF。</p></article>
  <article class="ao-coupon" data-cats="shopping life"><span class="ao-coupon-tag">ショッピング</span><h3>ブリヂストン タイヤ</h3><p>オンラインストアでタイヤ購入5％OFF。</p></article>
  <article class="ao-coupon" data-cats="hobby subsc"><span class="ao-coupon-tag">サブスク</span><h3>U-NEXT</h3><p>31日間無料トライアル＆1,200Pプレゼント。</p></article>
</div>
<p class="ao-empty ao-filter-empty" hidden>条件に合う特典が見つかりませんでした。条件を調整して再度お試しください。</p>
HTML),
            $p('/cashing/', 'キャッシング', 'ATM・電話・インターネットでお申込み。', <<<'HTML'
<h1>キャッシング</h1>
<p>困った時の強い味方。カードと暗証番号で全国の提携ATMをご利用いただけます。電話・インターネット申込も可能です（事前に利用枠設定が必要）。</p>
<ul>
<li><a href="/cashing-outline">キャッシングをご利用される方へ</a></li>
<li><a href="/cashing-hurry">インターネットや電話でのお申し込み</a></li>
<li><a href="/cashing-repayment">お支払日について</a></li>
<li><a href="/cashing-used">利用枠設定・増枠</a></li>
<li><a href="/cashing-cdatm-guide">CD・ATMの操作方法</a></li>
</ul>
HTML),
            $p('/cashing/outline/', 'キャッシングをご利用される方へ', '利用方法の概要。', <<<'HTML'
<h1>キャッシングをご利用される方へ</h1>
<p>キャッシングご利用方法を分かりやすくご案内します。利用枠が設定されている方が対象です。</p>
HTML),
            $p('/cashing/hurry/', 'インターネットや電話でのお申し込み', 'ATM以外でのキャッシング申込。', <<<'HTML'
<h1>インターネットや電話でのお申し込み</h1>
<p>カードをお持ちの方はお電話やインターネットよりキャッシングをお申込みいただけます。</p>
HTML),
            $p('/cashing/repayment/', 'お支払日について（キャッシング）', '各カードのお支払日。', <<<'HTML'
<h1>お支払日について</h1>
<p>各発行会社・カードごとのお支払日をご案内します。詳細は会員向けWebまたはご案内書面をご確認ください。</p>
HTML),
            $p('/cashing/used/', 'キャッシングの利用枠設定・増枠', '利用枠の確認・設定・増枠。', <<<'HTML'
<h1>キャッシングの利用枠設定・増枠</h1>
<p>ご利用可能枠の確認、利用枠設定・増枠のお申し込み方法をご案内します。審査が必要な場合があります。</p>
HTML),
            $p('/cashing/cdatm_guide/', 'CD・ATMのご利用について', '提携ATMの操作方法。', <<<'HTML'
<h1>CD・ATMのご利用について</h1>
<p>キャッシングがご利用いただけるCD・ATMの操作方法をご案内します。</p>
HTML),
            $p('/support/', 'カード会員の方', '発行会社別のWeb・電話窓口・各種手続き。', <<<'HTML'
<h1>カード会員の方</h1>
<p>カード番号の先頭4桁で発行会社が異なります。</p>
<ul>
<li>5452 — ライフカード（LIFE-Web Desk）</li>
<li>4980 — 三井住友カード（Vpass）</li>
<li>5283 — 青山キャピタル（アットユーネット）</li>
</ul>
<p>関連：<a href="/support-changes">各種変更</a> ／ <a href="/support-confirmation">利用枠照会</a> ／ <a href="/support-revolving">リボ変更</a> ／ <a href="/support-userguide-funshitsu">紛失・盗難</a> ／ <a href="/charge-details">ご利用明細</a></p>
HTML),
            $p('/support/changes/', '住所変更・支払口座等各種変更', '登録内容の変更手続き。', <<<'HTML'
<h1>住所・支払口座等 登録内容の各種変更</h1>
<p>Webまたはお電話で手続きいただけます。発行会社の会員サービス（LIFE-Web Desk / Vpass / アットユーネット）をご利用ください。</p>
HTML),
            $p('/support/confirmation/', 'ご利用可能枠の確認・照会', '利用枠の照会・設定・増枠。', <<<'HTML'
<h1>ご利用可能枠の確認・照会</h1>
<p>Webまたはお電話にてご利用可能枠の確認、利用枠設定や増枠申し込みを受付します。</p>
HTML),
            $p('/support/revolving/', 'リボ払い変更（ショッピング）', 'ショッピングリボへの変更。', <<<'HTML'
<h1>リボ払い変更（ショッピング）</h1>
<p>リボ払いとは？ご利用方法・変更手続きをご案内します。</p>
HTML),
            $p('/support/inquiry/', 'お問い合わせ', 'カード種類不明時・ポイント等のお問い合わせ窓口。', <<<'HTML'
<h1>お問い合わせ</h1>
<p>AOYAMAカードの種類がご不明、またはカードのお受け取り前のお客様は青山キャピタル 0570-000-033（ナビダイヤル）へ。受付 9:30～18:00（1/1～1/3除く）。</p>
HTML),
            $p('/support/userguide/funshitsu.php', 'カードの紛失・盗難', '24時間受付の紛失・盗難窓口。', <<<'HTML'
<h1>カードの紛失・盗難</h1>
<ul>
<li>ライフカード：03-4363-2203</li>
<li>三井住友カード：0120-919-456</li>
<li>青山キャピタル：0570-070-505（時間外はUCカード紛失係）</li>
</ul>
<p>保障は届出前後60日（通算121日）が基本です（条件あり）。</p>
HTML),
            $p('/support/userguide/password.php', '暗証番号照会・変更', '暗証番号の照会・変更手続き。', <<<'HTML'
<h1>暗証番号照会・変更</h1>
<p>Webまたはお電話にて受付いたします。発行会社の会員向けサービスをご利用ください。</p>
HTML),
            $p('/charge_details/', 'ご利用明細確認・Web明細サービス', '明細照会とWeb明細。', <<<'HTML'
<h1>ご利用明細確認・Web明細サービス</h1>
<p>Webでご利用明細照会やお支払い方法変更等が行えます。紙明細書には発行手数料がかかる場合があります。</p>
HTML),
            $p('/registration/', 'Webサービス新規登録', '会員向けWebサービスの新規登録。', <<<'HTML'
<h1>Webサービス新規登録</h1>
<p>LIFE-Web Desk / Vpass / アットユーネットへの新規登録方法をご案内します。</p>
HTML),
            $p('/membership/', '青山キャピタル発行カードの紹介', '青山キャピタル発行カードのご案内。', <<<'HTML'
<h1>青山キャピタル発行カードの紹介</h1>
<p>株式会社青山キャピタルが発行するAOYAMAカード・BLUE ROSE CARD・SUGOCA一体型などのご案内です。</p>
HTML),
            $p('/company/about/', '企業情報', '株式会社青山キャピタルの会社概要。', <<<'HTML'
<h1>企業情報</h1>
<p>経営理念：持続的な成長をもとに、生活者への小売・サービスを通じてさらなる社会への貢献を目指す。</p>
<p>企業理念：AOYAMAカードで人々を笑顔に。</p>
<table>
<tr><th>社名</th><td>株式会社青山キャピタル</td></tr>
<tr><th>本社</th><td>広島県福山市船町8-14</td></tr>
<tr><th>創立</th><td>平成11年8月18日</td></tr>
<tr><th>払込資本</th><td>50億円</td></tr>
<tr><th>有効カード会員数</th><td>約379万名（令和8年2月28日現在）</td></tr>
</table>
HTML),
            $p('/company/creditpolicy/', '各種ポリシー', 'クレジットポリシー等。', <<<'HTML'
<h1>各種ポリシー</h1>
<p>包括信用購入あっせん業・貸金業等に関する方針・ポリシーをご案内します。</p>
HTML),
            $p('/company/privacy.php', '個人情報保護方針', '個人情報の保護に関する方針。', <<<'HTML'
<h1>個人情報の保護に関する方針</h1>
<p>株式会社青山キャピタルの個人情報保護方針です。詳細は公式サイトのプライバシーポリシーをご確認ください。</p>
HTML),
            $p('/company/kameiten.html', '加盟店情報の共同利用について', '加盟店情報の共同利用。', <<<'HTML'
<h1>加盟店情報の共同利用について</h1>
<p>加盟店情報の共同利用に関するご案内です。</p>
HTML),
            $p('/faq/', 'よくあるご質問', '解約・割引券・3Dセキュア・ポイントなどFAQ。', <<<'HTML'
<h1>よくあるご質問</h1>
<details><summary>年会費はいくらですか？</summary><p>多くのカードで初年度無料、2年目から1,375円（税込）です。</p></details>
<details><summary>洋服の青山での割引は？</summary><p>カード提示でいつでも5％OFF、誕生日月は10％OFFです（条件あり）。</p></details>
<details><summary>カードを紛失したら？</summary><p><a href="/support-userguide-funshitsu-php">紛失・盗難</a>の各社窓口へすぐにご連絡ください。</p></details>
HTML),
            $p('/kiyaku/', '会員規約', '各発行会社の会員規約。', <<<'HTML'
<h1>会員規約</h1>
<p>ライフカード／三井住友カード／青山キャピタル発行カードそれぞれの会員規約をご確認ください。改定のお知らせは<a href="/news">お知らせ</a>にも掲載しています。</p>
HTML),
            $p('/sitepolicy.html', 'サイトポリシー', '本ウェブサイトのご利用条件。', <<<'HTML'
<h1>サイトポリシー</h1>
<p>本ウェブサイトのご利用にあたっての注意事項・免責等です。</p>
HTML),
            $p('/service/qcm.php', '本人認証サービス（3Dセキュア）', 'ネットショッピングの本人認証。', <<<'HTML'
<h1>本人認証サービス</h1>
<p>ネットショッピングに必要な3Dセキュアのご案内です。発行会社の会員サービスから設定してください。</p>
HTML),
            $p('/student.html', '学生の方向けご案内', '学生の方向けカード案内。', <<<'HTML'
<h1>学生の方向けご案内</h1>
<p>お申し込み条件・必要書類は各カードの入会ページをご確認ください。</p>
HTML),
            $p('/compare/', 'くらべてわかる、カードのお得', 'クレジットと現金ポイントカードの比較。', <<<'HTML'
<h1>くらべてわかる、カードのお得</h1>
<table>
<thead><tr><th>項目</th><th>AOYAMAカード／BLUE ROSE</th><th>AOYAMAポイントカード（現金）</th></tr></thead>
<tbody>
<tr><td>入会金</td><td>無料</td><td>ー</td></tr>
<tr><td>年会費</td><td>初年度無料／2年目1,375円</td><td>ー</td></tr>
<tr><td>洋服の青山割引</td><td>いつでも5％OFF／誕生日月10％OFF</td><td>ー</td></tr>
<tr><td>ポイント</td><td>最大合計2.5％還元（店頭）</td><td>現金払いで0.5％</td></tr>
</tbody>
</table>
HTML),
        ];
    }

    /**
     * @return array<string, Content>
     */
    private function seedPages(User $author): array
    {
        $pages = [];
        foreach ($this->pageDefinitions() as $def) {
            $existing = Content::withTrashed()
                ->whereHas('type', fn ($q) => $q->where('slug', 'page'))
                ->where('slug', $def['slug'])
                ->first();

            if ($existing) {
                if (method_exists($existing, 'trashed') && $existing->trashed()) {
                    $existing->restore();
                }
                $this->contents->update($existing, [
                    'title' => $def['title'],
                    'excerpt' => $def['excerpt'],
                    'body' => $def['body'],
                    'template' => $def['slug'] === 'home' ? 'landing' : ($existing->template ?: 'default'),
                ]);
                $pages[$def['slug']] = $existing->fresh();
                continue;
            }

            $pages[$def['slug']] = $this->contents->create('page', [
                'title' => $def['title'],
                'slug' => $def['slug'],
                'excerpt' => $def['excerpt'],
                'body' => $def['body'],
                'status' => ContentStatus::Published->value,
                'published_at' => now(),
                'template' => $def['slug'] === 'home' ? 'landing' : 'default',
            ], $author);
        }

        $home = $pages['home'] ?? null;
        if ($home) {
            $legacy = Page::query()->updateOrCreate(
                ['slug' => 'home'],
                [
                    'title' => $home->title,
                    'content' => $home->body,
                    'excerpt' => $home->excerpt,
                    'status' => 'publish',
                    'author_id' => $author->id,
                    'published_at' => now(),
                    'template' => 'landing',
                ]
            );
            CmsSetting::setValue('homepage', (string) $legacy->id);
        }

        return $pages;
    }

    /**
     * Download official demo assets into the aoyama theme + media library.
     */
    private function seedThemeImages(User $author): int
    {
        $dir = resource_path('views/themes/aoyama/assets/images');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $map = [
            'logo.png' => 'https://www.aoyama-card.co.jp/assets/images/top/com-logo.png',
            'hero-bg-1.png' => 'https://www.aoyama-card.co.jp/assets/images/top/slide_1/pc_top_kv_bg1.png',
            'hero-bg-2.png' => 'https://www.aoyama-card.co.jp/assets/images/top/slide_2/pc_top_kv_bg2.png',
            'hero-bg-3.png' => 'https://www.aoyama-card.co.jp/assets/images/top/slide_3/pc_top_kv_bg3.png',
            'hero-card-aoyama.png' => 'https://www.aoyama-card.co.jp/assets/images/top/slide_1/ac-aoyama-card.png',
            'hero-card-bluerose.png' => 'https://www.aoyama-card.co.jp/assets/images/top/slide_2/ac-blue-rose.png',
            'card-life.png' => 'https://www.aoyama-card.co.jp/assets/images/card/life_aoyama_card.png',
            'card-bluerose-life.png' => 'https://www.aoyama-card.co.jp/assets/images/card/life_blue_rose_card.png',
            'card-visa.png' => 'https://www.aoyama-card.co.jp/assets/images/card/visa_ic_aoyama_card.png',
            'card-pitapa.png' => 'https://www.aoyama-card.co.jp/assets/images/card/aoyama_pitapa_card.png',
            'card-visa-bluerose.png' => 'https://www.aoyama-card.co.jp/assets/images/card/visa_blue_rose_card.png',
            'card-capital.png' => 'https://www.aoyama-card.co.jp/assets/images/card/ac_aoyama_card.png',
            'card-sugoca.png' => 'https://www.aoyama-card.co.jp/assets/images/card/sugoca_life_aoyama_card.png',
            'card-bluerose-capital.png' => 'https://www.aoyama-card.co.jp/assets/images/card/ac_blue_rose_card.png',
            'card-papas-mamas.png' => 'https://www.aoyama-card.co.jp/assets/images/card/papas_mamas_card.png',
            'movie-thumb.png' => 'https://www.aoyama-card.co.jp/assets/images/etc/movie_thumbnail.png',
        ];

        // Refresh assets whose source URL changed (avoid keeping stale PNGs).
        foreach (['card-capital.png'] as $stale) {
            $stalePath = $dir.DIRECTORY_SEPARATOR.$stale;
            if (is_file($stalePath)) {
                @unlink($stalePath);
            }
        }

        $count = 0;
        foreach ($map as $filename => $url) {
            $dest = $dir.DIRECTORY_SEPARATOR.$filename;
            if (! is_file($dest) || filesize($dest) < 1024) {
                try {
                    $response = Http::timeout(60)
                        ->withHeaders(['User-Agent' => 'LaravelPressAoyamaSeeder/1.0'])
                        ->get($url);
                    if ($response->successful() && strlen($response->body()) > 500) {
                        $bytes = $response->body();
                        // Reject HTML error pages saved as .png
                        if (str_starts_with($bytes, "\x89PNG") || str_starts_with($bytes, "\xFF\xD8\xFF") || str_starts_with($bytes, '<svg')) {
                            file_put_contents($dest, $bytes);
                        }
                    }
                } catch (\Throwable) {
                    // Keep placeholder SVG if download fails.
                }
            }

            if (! is_file($dest)) {
                continue;
            }

            $diskPath = 'aoyama/'.$filename;
            Storage::disk('public')->put($diskPath, file_get_contents($dest));
            Media::query()->updateOrCreate(
                ['path' => $diskPath, 'disk' => 'public'],
                [
                    'filename' => $filename,
                    'mime_type' => 'image/png',
                    'size' => filesize($dest) ?: 0,
                    'alt' => pathinfo($filename, PATHINFO_FILENAME),
                    'uploaded_by' => $author->id,
                ]
            );
            $count++;
        }

        // Mirror into public/ so Docker php -S serves images/CSS statically.
        $this->themes->discover();
        $this->themes->publishAssets('aoyama');

        return $count;
    }

    /**
     * @param  array<string, int>  $termIds
     * @return list<Content>
     */
    private function seedPosts(User $author, array $termIds): array
    {
        $definitions = [
            ['title' => '【青山キャピタル発行カード】ご利用代金のお引き落とし印字名称変更のご案内 ※一部金融機関をお引き落とし口座に設定されているお客様', 'slug' => 'news-2026-09-03-debit-name', 'cat' => 'service', 'date' => '2026-09-03'],
            ['title' => '【青山キャピタル発行カード】クレジットカードによる海外金融取引などの利用停止に関するご案内', 'slug' => 'news-2026-08-25-overseas', 'cat' => 'important', 'date' => '2026-08-25'],
            ['title' => '自然災害により被害を受けられたお客さまへ', 'slug' => 'news-2026-07-30-disaster', 'cat' => 'important', 'date' => '2026-07-30'],
            ['title' => 'ホームページメンテナンスのお知らせ', 'slug' => 'news-2026-07-16-maintenance', 'cat' => 'service', 'date' => '2026-07-16'],
            ['title' => '【ライフカード発行】インフォメーションセンター受付時間変更のお知らせ', 'slug' => 'news-2026-06-01-life-hours', 'cat' => 'service', 'date' => '2026-06-01'],
            ['title' => '【青山キャピタル発行カード】会員規約改定のお知らせ', 'slug' => 'news-2025-10-31-terms', 'cat' => 'terms', 'date' => '2025-10-31'],
            ['title' => '【青山キャピタル発行カード】更新カード郵送方法変更のお知らせ', 'slug' => 'news-2025-08-27-renewal', 'cat' => 'service', 'date' => '2025-08-27'],
            ['title' => '【青山キャピタル発行カード】「ご利用明細書発行手数料」の明細書発送について', 'slug' => 'news-2025-07-31-statement', 'cat' => 'terms', 'date' => '2025-07-31'],
            ['title' => '【ライフカード発行】会員規約改定のお知らせ', 'slug' => 'news-2025-07-18-life-terms', 'cat' => 'terms', 'date' => '2025-07-18'],
            ['title' => '【青山キャピタル発行カード】ショッピングリボ手数料率およびカード会員規約改定のご案内', 'slug' => 'news-2025-07-08-ribo', 'cat' => 'terms', 'date' => '2025-07-08'],
            ['title' => '【青山キャピタル発行カード】会員規約改定のお知らせ', 'slug' => 'news-2025-06-03-terms', 'cat' => 'terms', 'date' => '2025-06-03'],
            ['title' => '【青山キャピタル発行カード】分割払い手数料率改定のご案内', 'slug' => 'news-2025-04-01-installment', 'cat' => 'terms', 'date' => '2025-04-01'],
            ['title' => '洋服の青山オンラインストアのサービス再開について', 'slug' => 'news-2025-03-13-store-resume', 'cat' => 'service', 'date' => '2025-03-13'],
            ['title' => 'いつでもキャッシュインサービスのご案内', 'slug' => 'news-2025-03-13-cash-in', 'cat' => 'service', 'date' => '2025-03-13'],
            ['title' => 'SMS（ショートメッセージサービス）の送信元電話番号の変更について', 'slug' => 'news-2025-01-31-sms', 'cat' => 'service', 'date' => '2025-01-31'],
            ['title' => '洋服の青山オンラインストアのサービス変更について', 'slug' => 'news-2025-01-24-store-change', 'cat' => 'service', 'date' => '2025-01-24'],
            ['title' => '【三井住友カード】分割払い手数料率改定のご案内', 'slug' => 'news-2025-01-15-smbc', 'cat' => 'terms', 'date' => '2025-01-15'],
            ['title' => '【青山キャピタル発行カード】回収事務手数料の請求について', 'slug' => 'news-2024-12-19-collection-fee', 'cat' => 'terms', 'date' => '2024-12-19'],
            ['title' => '【青山キャピタル発行カード】会員規約改定のお知らせ', 'slug' => 'news-2024-12-05-terms', 'cat' => 'terms', 'date' => '2024-12-05'],
            ['title' => 'ＵＣポイント対象サービスの最少交換ポイント数変更のお知らせ', 'slug' => 'news-2024-12-02-uc-point', 'cat' => 'service', 'date' => '2024-12-02'],
            ['title' => '青山キャピタル発行カードの海外ショッピングご利用時の事務処理経費の改定', 'slug' => 'news-2024-10-28-overseas-fee', 'cat' => 'terms', 'date' => '2024-10-28'],
            ['title' => '自動音声応答システムによるご連絡とSMSの配信について', 'slug' => 'news-2024-10-01-ivr-sms', 'cat' => 'service', 'date' => '2024-10-01'],
            ['title' => '【青山キャピタル発行カード】現行の自動音声応答システムによるご連絡終了について', 'slug' => 'news-2024-09-23-ivr-end', 'cat' => 'service', 'date' => '2024-09-23'],
            ['title' => '【青山キャピタル発行カード】紙のご利用代金明細書発行手数料の改定について', 'slug' => 'news-2024-09-05-paper-fee', 'cat' => 'terms', 'date' => '2024-09-05'],
            ['title' => '【重要】本人認証サービスご設定のお願い（青山キャピタル発行カード会員様）', 'slug' => 'news-2024-08-16-3ds', 'cat' => 'important', 'date' => '2024-08-16'],
            ['title' => 'ＳＭＳ（ショートメッセージサービス）の配信について', 'slug' => 'news-2024-06-26-sms', 'cat' => 'service', 'date' => '2024-06-26'],
            ['title' => '【ライフカード発行】更新カード郵送方法変更のお知らせ', 'slug' => 'news-2023-12-14-life-renewal', 'cat' => 'service', 'date' => '2023-12-14'],
            ['title' => 'インボイス制度開始における当社対応についてのお知らせ', 'slug' => 'news-2023-09-29-invoice', 'cat' => 'service', 'date' => '2023-09-29'],
            ['title' => '【青山キャピタル発行カード】カード付帯保険 補償終了のご案内', 'slug' => 'news-2023-08-02-insurance', 'cat' => 'important', 'date' => '2023-08-02'],
            ['title' => '【ご注意】金融商品取引に当たってのクレジットカード利用について', 'slug' => 'news-2023-07-14-finance', 'cat' => 'important', 'date' => '2023-07-14'],
            ['title' => 'AOYAMAポイント／ROSEポイント交換終了のお知らせ', 'slug' => 'news-2023-07-05-point-exchange', 'cat' => 'service', 'date' => '2023-07-05'],
            ['title' => '「AOYAMAカード」のデザイン変更のお知らせ', 'slug' => 'news-2023-04-01-design', 'cat' => 'card', 'date' => '2023-04-01'],
            ['title' => '「Papasカード」「Mamasカード」のカード名称・デザイン変更のお知らせ', 'slug' => 'news-2023-02-21-papas-mamas', 'cat' => 'card', 'date' => '2023-02-21'],
            ['title' => 'オンラインカジノを利用した賭博は犯罪です！警察庁Webサイト', 'slug' => 'news-2022-12-20-casino', 'cat' => 'important', 'date' => '2022-12-20'],
            ['title' => '便利なリボ払いのご案内♪', 'slug' => 'news-2022-08-25-ribo', 'cat' => 'campaign', 'date' => '2022-08-25'],
            ['title' => '消費者庁ウェブサイト「18歳から大人」特設ページ', 'slug' => 'news-2021-06-28-18adult', 'cat' => 'important', 'date' => '2021-06-28'],
            ['title' => '【重要】ログイン後の不審な画面にご注意ください！', 'slug' => 'news-2014-07-28-phishing', 'cat' => 'important', 'date' => '2014-07-28'],
        ];

        $posts = [];
        foreach ($definitions as $def) {
            $termId = $termIds[$def['cat']] ?? null;
            $existing = Content::withTrashed()
                ->whereHas('type', fn ($q) => $q->where('slug', 'post'))
                ->where('slug', $def['slug'])
                ->first();

            $excerpt = $def['title'];
            $body = '<p>'.$def['title'].'</p><p>本コンテンツはデモ用に <a href="https://www.aoyama-card.co.jp/news/" rel="noopener">aoyama-card.co.jp/news/</a> の掲載内容を参考に作成しています。正式な内容は公式サイトをご確認ください。</p>';

            if ($existing) {
                if (method_exists($existing, 'trashed') && $existing->trashed()) {
                    $existing->restore();
                }
                $this->contents->update($existing, [
                    'title' => $def['title'],
                    'excerpt' => $excerpt,
                    'body' => $body,
                    'published_at' => $def['date'],
                    'term_ids' => $termId ? [$termId] : [],
                ]);
                $posts[] = $existing->fresh();
                continue;
            }

            $posts[] = $this->contents->create('post', [
                'title' => $def['title'],
                'slug' => $def['slug'],
                'excerpt' => $excerpt,
                'body' => $body,
                'status' => ContentStatus::Published->value,
                'published_at' => $def['date'],
                'term_ids' => $termId ? [$termId] : [],
                'comment_status' => 'closed',
            ], $author);
        }

        return $posts;
    }

    /**
     * @return list<Content>
     */
    private function seedCampaigns(User $author): array
    {
        $definitions = [
            [
                'title' => '【三井住友カード発行】新規ご入会＆ご利用＆ご登録でもれなく最大5,000円キャッシュバック！',
                'slug' => 'smbc-new-member-cashback',
                'excerpt' => 'ご入会＆ご入会の翌月末までに合計15,000円(税込)のお買い物で2,000円キャッシュバック♪さらに3,000円キャッシュバックとなる、お申込み時の「マイ・ペイすリボ」の条件は詳細ページをご確認ください。',
                'period_label' => '入会申込期間',
                'period' => '2026年4月27日（月）～',
                'target_label' => '対象カード',
                'target' => 'AOYAMA VISAカード／AOYAMA VISA PiTaPaカード／BLUE ROSE CARD(三井住友カード発行)',
                'note' => '※上記のマイ・ペイすリボプランは2026年9月30日(水)をもって終了いたします。2026年10月1日(木)より、5,000円キャッシュバックに変更予定です。',
                'date' => '2026-04-27',
                'order' => 1,
                'body' => <<<'HTML'
<p>三井住友カード発行の対象カードへ新規ご入会のうえ、条件を満たすとキャッシュバックを進呈するキャンペーンです。</p>
<ul>
<li>ご入会＆ご入会の翌月末までに合計15,000円（税込）以上のお買い物で2,000円キャッシュバック</li>
<li>お申込み時の「マイ・ペイすリボ」条件達成でさらに3,000円（合計最大5,000円）</li>
</ul>
<p>本コンテンツはデモ用に <a href="https://www.aoyama-card.co.jp/campaign/" rel="noopener">aoyama-card.co.jp/campaign/</a> を参考に作成しています。正式な条件は公式サイトをご確認ください。</p>
HTML,
            ],
            [
                'title' => '【最大2万円キャッシュバック】リボ宣言登録＆利用キャンペーン！',
                'slug' => 'ribo-declaration-cashback',
                'excerpt' => '「リボ宣言にご登録」かつ「ショッピングリボのご利用」で、条件を満たした方に、最大2万円キャッシュバック！特典内容はご利用金額に応じて異なります。',
                'period_label' => 'キャンペーン期間',
                'period' => '2026年4月1日（水）～2026年6月30日（火）',
                'target_label' => '対象カード',
                'target' => '青山キャピタル発行のAOYAMAカード／BLUE ROSE CARD／AOYAMAマスターカードSUGOCA',
                'note' => null,
                'date' => '2026-04-01',
                'order' => 2,
                'body' => <<<'HTML'
<p>リボ宣言へのご登録とショッピングリボのご利用で、ご利用金額に応じたキャッシュバックを進呈します（最大2万円）。</p>
<p>本コンテンツはデモ用に <a href="https://www.aoyama-card.co.jp/campaign/U26041.php" rel="noopener">公式キャンペーン詳細</a> を参考に作成しています。</p>
HTML,
            ],
            [
                'title' => '【最大3万円相当のUCポイントプレゼント】新規入会キャンペーン！',
                'slug' => 'uc-point-new-member',
                'excerpt' => '新規入会＆ご利用で、最大3万円相当のUCポイントプレゼント♪利用対象期間中のショッピング利用において、3回以上の決済、かつ一定額以上の利用が必要です。利用対象期間は、入会申込月の翌々月末日まで！ご利用金額に応じて、特典内容が異なります！',
                'period_label' => '入会申込期間',
                'period' => '2026年3月1日（日）～2026年9月30日（水）',
                'target_label' => '対象',
                'target' => '入会申込期間中に青山キャピタル発行のAOYAMAカード／BLUE ROSE CARD／AOYAMAマスターカードSUGOCAへお申込みいただいた方',
                'note' => null,
                'date' => '2026-03-01',
                'order' => 3,
                'body' => <<<'HTML'
<p>青山キャピタル発行カードへの新規入会＆ご利用で、最大3万円相当のUCポイントをプレゼントします。</p>
<ul>
<li>利用対象期間中に3回以上の決済</li>
<li>一定額以上のご利用（金額に応じて特典が異なります）</li>
<li>利用対象期間は入会申込月の翌々月末日まで</li>
</ul>
<p>本コンテンツはデモ用に <a href="https://www.aoyama-card.co.jp/campaign/U26031.php" rel="noopener">公式キャンペーン詳細</a> を参考に作成しています。</p>
HTML,
            ],
            [
                'title' => '【ライフカード発行】もれなく1,000円分がもらえる新規ご入会キャンペーン！',
                'slug' => 'life-new-member-1000',
                'excerpt' => 'もれなく1,000円分のVプリカギフトプレゼント！対象期間中に新規ご入会のうえ、3回以上＆合計10,000円(税込)以上のお買い物で対象になります！利用対象期間は、入会申込月の翌々月末日まで！',
                'period_label' => '入会申込期間',
                'period' => '2026年4月1日（水）～2026年9月30日（水）',
                'target_label' => '対象',
                'target' => '入会申込期間中にライフカード発行のAOYAMAカード／BLUE ROSE CARDへお申込みいただいた方',
                'note' => null,
                'date' => '2026-04-01',
                'order' => 4,
                'body' => <<<'HTML'
<p>ライフカード発行の対象カードへ新規ご入会のうえ、条件達成でもれなく1,000円分のVプリカギフトをプレゼントします。</p>
<ul>
<li>3回以上のお買い物</li>
<li>合計10,000円（税込）以上のご利用</li>
<li>利用対象期間は入会申込月の翌々月末日まで</li>
</ul>
<p>本コンテンツはデモ用に <a href="https://www.aoyama-card.co.jp/campaign/" rel="noopener">aoyama-card.co.jp/campaign/</a> を参考に作成しています。</p>
HTML,
            ],
            [
                'title' => '大切なひと時を演出するMastercardプロモーションのご案内',
                'slug' => 'mastercard-promotion',
                'excerpt' => 'Mastercard主催のキャンペーン・プロモーションをご案内します。対象カードでご利用のうえ、各プロモーションの条件をご確認ください。',
                'period_label' => null,
                'period' => null,
                'target_label' => '対象カード',
                'target' => '青山キャピタル発行のAOYAMAカード／BLUE ROSE CARD／AOYAMAマスターカードSUGOCA',
                'note' => null,
                'date' => '2026-01-01',
                'order' => 5,
                'body' => <<<'HTML'
<p>Mastercard主催の各種プロモーション情報です。最新の内容・応募条件はMastercardおよび公式サイトのご案内をご確認ください。</p>
<p>本コンテンツはデモ用に <a href="https://www.aoyama-card.co.jp/campaign/" rel="noopener">aoyama-card.co.jp/campaign/</a> を参考に作成しています。</p>
HTML,
            ],
        ];

        $items = [];
        foreach ($definitions as $def) {
            $existing = Content::withTrashed()
                ->whereHas('type', fn ($q) => $q->where('slug', 'campaign'))
                ->where('slug', $def['slug'])
                ->first();

            $payload = [
                'title' => $def['title'],
                'excerpt' => $def['excerpt'],
                'body' => $def['body'],
                'published_at' => $def['date'],
                'menu_order' => $def['order'],
                'comment_status' => 'closed',
            ];

            if ($existing) {
                if (method_exists($existing, 'trashed') && $existing->trashed()) {
                    $existing->restore();
                }
                $this->contents->update($existing, $payload);
                $content = $existing->fresh(['meta']);
            } else {
                $content = $this->contents->create('campaign', array_merge($payload, [
                    'slug' => $def['slug'],
                    'status' => ContentStatus::Published->value,
                ]), $author);
            }

            $this->syncCampaignMeta($content, $def);
            $items[] = $content->fresh(['meta']);
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $def
     */
    private function syncCampaignMeta(Content $content, array $def): void
    {
        $pairs = [
            'period_label' => $def['period_label'] ?? null,
            'period' => $def['period'] ?? null,
            'target_label' => $def['target_label'] ?? null,
            'target' => $def['target'] ?? null,
            'note' => $def['note'] ?? null,
        ];

        foreach ($pairs as $key => $value) {
            if ($value === null || $value === '') {
                ContentMeta::query()
                    ->where('content_id', $content->id)
                    ->where('key', $key)
                    ->delete();
                continue;
            }

            ContentMeta::query()->updateOrCreate(
                ['content_id' => $content->id, 'key' => $key],
                ['type' => MetaType::Text, 'value' => (string) $value]
            );
        }
    }

    /**
     * @param  array<string, Content>  $pages
     */
    private function seedMenu(array $pages): int
    {
        $menu = Menu::query()->updateOrCreate(
            ['slug' => 'primary'],
            ['name' => 'メインメニュー', 'location' => 'primary']
        );

        MenuItem::query()->where('menu_id', $menu->id)->delete();

        $top = [
            ['title' => 'カードをつくる', 'slug' => 'card', 'path' => '/card'],
            ['title' => 'サービス・特典', 'slug' => 'used', 'path' => '/used'],
            ['title' => 'カード優待特典', 'slug' => 'biz-yuutai', 'path' => '/biz-yuutai'],
            ['title' => 'キャッシング', 'slug' => 'cashing', 'path' => '/cashing'],
            ['title' => 'キャンペーン', 'slug' => 'campaign', 'path' => '/campaign'],
            ['title' => 'カード会員の方', 'slug' => 'support', 'path' => '/support'],
        ];

        $count = 0;
        foreach ($top as $order => $item) {
            MenuItem::query()->create([
                'menu_id' => $menu->id,
                'title' => $item['title'],
                'url' => $item['path'],
                'sort_order' => $order,
            ]);
            $count++;
        }

        $cardParent = MenuItem::query()->where('menu_id', $menu->id)->where('title', 'カードをつくる')->first();
        if ($cardParent) {
            foreach ([
                ['title' => 'AOYAMAライフマスターカード', 'path' => '/card-aoyama-life'],
                ['title' => 'AOYAMA VISA / PiTaPa', 'path' => '/card-aoyama-visa'],
                ['title' => 'BLUE ROSE（ライフ）', 'path' => '/card-bluerose-life'],
                ['title' => 'BLUE ROSE（三井住友）', 'path' => '/card-bluerose-visa'],
                ['title' => '青山キャピタル発行', 'path' => '/card-capital'],
                ['title' => 'BLUE ROSE（キャピタル）', 'path' => '/card-bluerose'],
                ['title' => 'くらべてわかる、カードのお得', 'path' => '/compare'],
            ] as $i => $child) {
                MenuItem::query()->create([
                    'menu_id' => $menu->id,
                    'parent_id' => $cardParent->id,
                    'title' => $child['title'],
                    'url' => $child['path'],
                    'sort_order' => $i,
                ]);
                $count++;
            }
        }

        $usedParent = MenuItem::query()->where('menu_id', $menu->id)->where('title', 'サービス・特典')->first();
        if ($usedParent) {
            foreach ([
                ['title' => '洋服の青山の特典', 'path' => '/used-preferential'],
                ['title' => 'AOYAMAポイント', 'path' => '/used-aoyama-point'],
                ['title' => 'AOYAMA Pay', 'path' => '/used-aoyama-pay'],
                ['title' => 'ショッピング', 'path' => '/used-shopping'],
                ['title' => 'ご利用代金のお支払い', 'path' => '/used-flow'],
                ['title' => 'ETCカード', 'path' => '/used-etc'],
            ] as $i => $child) {
                MenuItem::query()->create([
                    'menu_id' => $menu->id,
                    'parent_id' => $usedParent->id,
                    'title' => $child['title'],
                    'url' => $child['path'],
                    'sort_order' => $i,
                ]);
                $count++;
            }
        }

        return $count;
    }

    private function purge(): void
    {
        $pageSlugs = collect($this->pageDefinitions())
            ->pluck('slug')
            ->push('home')
            ->unique()
            ->all();

        $postSlugs = [
            'news-2026-09-03-debit-name', 'news-2026-08-25-overseas', 'news-2026-07-30-disaster',
            'news-2026-07-16-maintenance', 'news-2026-06-01-life-hours', 'news-2025-10-31-terms',
            'news-2025-08-27-renewal', 'news-2025-07-31-statement', 'news-2025-07-18-life-terms',
            'news-2025-07-08-ribo', 'news-2025-06-03-terms', 'news-2025-04-01-installment',
            'news-2025-03-13-store-resume', 'news-2025-03-13-cash-in', 'news-2025-01-31-sms',
            'news-2025-01-24-store-change', 'news-2025-01-15-smbc', 'news-2024-12-19-collection-fee',
            'news-2024-12-05-terms', 'news-2024-12-02-uc-point', 'news-2024-10-28-overseas-fee',
            'news-2024-10-01-ivr-sms', 'news-2024-09-23-ivr-end', 'news-2024-09-05-paper-fee',
            'news-2024-08-16-3ds', 'news-2024-06-26-sms', 'news-2023-12-14-life-renewal',
            'news-2023-09-29-invoice', 'news-2023-08-02-insurance', 'news-2023-07-14-finance',
            'news-2023-07-05-point-exchange', 'news-2023-04-01-design', 'news-2023-02-21-papas-mamas',
            'news-2022-12-20-casino', 'news-2022-08-25-ribo', 'news-2021-06-28-18adult',
            'news-2014-07-28-phishing',
            // legacy short list from first seeder pass
            '2026-09-03-debit-name-change', '2026-08-25-overseas-finance-stop',
            '2026-07-30-disaster-support', '2026-07-16-maintenance', '2026-06-01-life-hours',
            '2025-10-31-terms-revision', '2025-08-27-renewal-mail', '2025-07-31-statement-fee',
            '2025-07-18-life-terms', '2025-07-08-ribo-rate', '18-from-adult', 'online-casino-warning',
            'home', 'card', 'card-capital', 'card-bluerose', 'used', 'used-preferential',
            'used-aoyama-point', 'biz-yuutai', 'cashing', 'campaign', 'support', 'lost-card',
            'company', 'faq', 'compare', 'news',
            'campaign', 'campaign-u26031-php', 'campaign-u26041-php',
            'smbc-new-member-cashback', 'ribo-declaration-cashback', 'uc-point-new-member',
            'life-new-member-1000', 'mastercard-promotion',
        ];

        Content::withTrashed()->whereIn('slug', array_merge($pageSlugs, $postSlugs))->forceDelete();
        Page::query()->whereIn('slug', array_merge($pageSlugs, ['home']))->delete();
    }
}

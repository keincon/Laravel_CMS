# LaravelPress

Laravel 製の WordPress 互換 CMS です（Laravel 13 · PHP 8.3+ · PostgreSQL · Redis）。

固定ページ・お知らせ（投稿）・キャンペーンなどのコンテンツ、メニュー、テーマ、メディア、SEO／OGP、プラグイン／モジュールを、管理画面 `/admin` と公開サイトで扱います。デモ用に **青山カード（Aoyama Card）** テーマとシーダー（`laravelpress:seed-aoyama`）を同梱しています。

詳細仕様は [`docs/Specifications/`](docs/Specifications/) および [`docs/Specifications/index.html`](docs/Specifications/index.html) を参照してください。日本語での初回セットアップは [`docs/guides/setup-japanese.md`](docs/guides/setup-japanese.md) と管理画面の **セットアップガイド**（`/admin/help/setup-guide`）があります。

---

## 目次

1. [技術スタック](#技術スタック)
2. [Docker で起動する](#docker-で起動する)
3. [システムの全体像](#システムの全体像)
4. [コンテンツの仕組み](#コンテンツの仕組み)
5. [公開 URL と動的ページ](#公開-url-と動的ページ)
6. [テーマ・外観](#テーマ外観)
7. [メニュー・ヘッダー／フッター](#メニューヘッダーフッター)
8. [メディア・SEO・カスタムコード](#メディアseoカスタムコード)
9. [権限・API・プラグイン](#権限apiプラグイン)
10. [主要ディレクトリ](#主要ディレクトリ)
11. [よく使う artisan コマンド](#よく使う-artisan-コマンド)
12. [本番・テストのメモ](#本番テストのメモ)

---

## 技術スタック

| 層 | 内容 |
|----|------|
| フレームワーク | Laravel ^13.17 |
| PHP | ^8.3（Docker イメージは PHP 8.4） |
| DB | PostgreSQL 16（セットアップでは MySQL も選択可） |
| キャッシュ／セッション／キュー | Redis 7 |
| 権限 | `spatie/laravel-permission` |
| API 認証 | Laravel Sanctum |
| フロントビルド | Vite 8 · Tailwind CSS 4 |
| 管理画面 JS | `resources/js/admin/` のバニラモジュール |
| UI 言語 | `en` / `ja`（サイト言語は `en` / `ja` / `my` など） |

Compose サービス: **app** · **postgres** · **redis** · **queue** · **scheduler**  
（任意）`docker compose --profile wordpress` で埋め込み WordPress（ホスト `:8080`）。

---

## Docker で起動する

```bash
docker compose up -d --build
docker compose port app 8000
```

表示されたホストポートで開きます（例: `http://127.0.0.1:32772`）。  
`APP_URL` の Compose 既定は `http://localhost:32772` です。実際のマップ先と違う場合はホスト側 `.env` の `APP_URL` を合わせてください（メディア URL などがずれます）。

### 未インストール時

`storage/app/cms/installed.json` が無いと `/setup` ウィザードが開きます。インストール後はセットアップ URL は 404 になります。

**Docker 用 DB（ウィザード入力）**

| 項目 | 値 |
|------|-----|
| Host | `postgres` |
| Port | `5432` |
| Database | `cms` |
| Username | `cms` |
| Password | `cms_secret` |

管理者パスワードは **12文字以上**、大文字・小文字・数字・記号を含めてください。

サイト言語で **Japanese (`ja`)**、タイムゾーン例 `Asia/Tokyo` を選ぶと、管理 UI・初期ロケールが日本語寄りになります。

インストールが失敗し、ログに `Environment modified. Restarting server...` と出る場合は、PHP 組み込みサーバ用にイメージを作り直してください。

```bash
docker compose up -d --build app
```

### 既存環境に組み込みを入れる

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan laravelpress:install --fresh-caps
```

---

## システムの全体像

LaravelPress は次の面に分かれます。

```
ブラウザ
  ├─ 公開サイト（テーマ Blade）     ← SiteController / PageRenderer / ThemeManager
  ├─ 管理画面 /admin/*              ← 認証 + Spatie 権限
  ├─ 初回 /setup/*                  ← インストール完了後は閉鎖
  └─ API /api/v1/* と WP 互換 /api/wp/v2/*
```

| 面 | 役割 |
|----|------|
| **公開サイト** | テーマの Blade（`pages/`・`dynamic/`）で HTML を出す。メニュー・ウィジェット・カスタムコード・SEO タグを合成 |
| **管理画面** | コンテンツ・メディア・メニュー・外観・設定・ユーザーなどを編集 |
| **コンテンツ基盤** | `content_types` → `contents`（＋メタ・リビジョン・ターム紐づけ） |
| **レガシー互換** | 旧 `posts` / `pages` テーブルは残存。既定では `CMS_LEGACY_RETIRED=true` で管理は `/admin/contents` 中心。公開は `contents` 優先、必要ならレガシーへフォールバック |

リクエストの流れ（公開）のイメージ:

1. ルートがスラッグ／アーカイブ種別を決める  
2. `contents`（またはレガシー）から本文を取得  
3. 動的ページ設定で「どのテーマ画面を使うか」を解決  
4. アクティブテーマの Blade を描画し、ヘッダー／フッター／メニュー／CSS 変数／カスタムコードを差し込む  

管理画面の各画面には、次に何をすべきかを示す **ヘルプ＆次のステップ**（`<x-admin.help-next>`）があります。全体手順は `/admin/help/setup-guide` です。

---

## コンテンツの仕組み

### コンテンツタイプ

| スラッグ | 用途 | 公開 URL の典型 |
|----------|------|-----------------|
| `page` | 固定ページ（階層・テンプレート可） | `/{slug}` |
| `post` | お知らせ／ブログ投稿 | `/blog/{slug}` や `/news/{slug}` など（パーマリンク設定依存） |
| `campaign` | キャンペーン（Aoyama シーダーで追加） | `/campaign/{slug}` |

管理は主に **`/admin/contents?type=page|post|campaign`** です。ステータスは下書き → 承認待ち／予約 → 公開、ゴミ箱など。ブロックエディタで編集し、保存時にメニューへ追加することもできます。

### タクソノミー

- **カテゴリー**（階層・投稿向け）  
- **タグ**（非階層・投稿向け）  
- 管理: `/admin/categories` · `/admin/tags` · `/admin/taxonomies`  
- 紐づけ: `terms` ↔ `content_term` ↔ `contents`

### コメント

サイト設定のコメント有効化と、各投稿の `comment_status` で制御します。モデレーションは `/admin/comments`。

### レガシー移行（破壊を抑えた移行）

旧 UI 用の `posts` / `pages` などは残しつつ、保存時に `contents` へ二重書き込みできます。公開は `contents` 優先です。一括移行・パック入出力・WXR インポート用コマンドは後述の artisan 一覧を参照してください。

---

## 公開 URL と動的ページ

「一覧や検索そのもの」はコンテンツ行ではなく **動的ページ設定**（`DynamicPageSetting`）です。管理: **`/admin/appearance/dynamic-pages`**。

設定できる種別の例（`config/cms.php`）:

`blog` · `post` · `category` · `tag` · `author` · `search` · `archive` · `campaign` · `campaign_item` · `404`

ここでタイトルやスラッグ（例: お知らせ一覧を `/news` にする）を変え、テーマの `dynamic/*.blade.php` と対応づけます。

その他の公開ルート例:

- `/` ホームページ（表示設定で固定ページ or 最新投稿）  
- `/search` · `/archive/{y}/{m}/{d?}`  
- `/category/{slug}` · `/tag/{slug}` · `/author/{username}`  
- `/sitemap.xml` · `/robots.txt`  
- テーマ資産: `/themes/{theme}/assets/{path}`

固定ページのキャッチオール `/{slug}` は、予約スラッグ（`config/cms.php`）と衝突しないものだけがページになります。

---

## テーマ・外観

### テーマの実体

ディスク上のフォルダがテーマです。

```
resources/views/themes/{slug}/
  theme.json          # 名前・色・stylesheets[]・scripts[]
  pages/*.blade.php   # 固定ページ用テンプレート
  dynamic/*.blade.php # 一覧・投稿・検索・404 など
  partials/           # 部分テンプレート
  assets/             # theme.css / theme.js / 画像など
  layout.blade.php    # （任意）レイアウト
```

- 有効化は DB の `themes.is_active`（管理: `/admin/appearance/themes`）  
- 足りない画面は `default` テーマへフォールバック  
- ZIP **パック**（`storage/app/theme-packs/`）でエクスポート／インポート可能  
- 同梱例: `aoyama` · `default` · `aurora` · `meadow` · `ink` · `paper` · `harbor`

有効化時、テーマの色パレットを `theme_settings` にコピーできます。

### 外観メニュー（サイト全体）

| 画面 | パス | 内容 |
|------|------|------|
| マスターレイアウト | `/admin/appearance/layout` | 既定ヘッダー／フッター、幅、サイドバー |
| テーマカラー | `/admin/appearance/colors` | CSS 変数（`--color-*` / Bootstrap 系）。NPM 再ビルド不要 |
| カラーモード | `/admin/appearance/mode` | light / dark / system |
| カスタムコード | `/admin/appearance/custom-code` | サイト全体の追加 CSS／JS／HTML |
| ウィジェット | `/admin/appearance/widgets` | メインサイドバー等 |
| 動的ページ | `/admin/appearance/dynamic-pages` | 一覧・単体の URL／画面 |

**テーマ資産**（フォルダ内 CSS/JS）と **カスタムコード**（設定に保存する注入）は別物です。テーマに閉じた見た目はテーマ側、サイト横断の一時的な上書きや計測タグはカスタムコード向きです。ページ／投稿単位のコードは各編集画面にもあります。

---

## メニュー・ヘッダー／フッター

- **メニュー** `/admin/menus` … 項目はタイトル・URL・固定ページ紐づけ・親子・並び順。ロケーション例: `primary` / `footer` / `secondary`  
- 編集画面の **リンク先ピッカー** で、サイト／アーカイブ・ページ・投稿・キャンペーン・タームから選べます（カスタム URL も可）  
- 公開側は `MenuItem::href()` でリンク解決（URL 優先、なければ紐づくページ）  
- **ヘッダー／フッタービルダー** `/admin/headers` · `/admin/footers` … マスターレイアウトの「既定」として割り当て  

固定ページ保存時にスラッグが変わると、一致するメニュー URL の更新も行われます。

---

## メディア・SEO・カスタムコード

### メディア

`/admin/media` でアップロード。公開 URL はリクエスト／`APP_URL` を意識したパスになります（Docker のポートずれで読み込みが止まるのを避けるため）。ファビコン・サイトロゴは設定（一般／UI）からメディア ID で指定できます。

### SEO / OGP

- 全体: `/admin/settings/seo` · `/admin/settings/ogp` · テンプレート · パーマリンク  
- エントリごと: 編集画面の SEO／OGP フィールド  
- テンプレート変数例: `{site_name}` · `{post_title}` など（`config/cms.php`）  
- モジュール `modules/SEO/` も利用可能  

### メール（サーバー設定・テンプレート）

管理: **設定 → メール**（`/admin/settings/mail`）と **メールテンプレート**（`/admin/settings/mail/templates`）。

| 項目 | 内容 |
|------|------|
| メーラー | `smtp` · `log`（開発） · `sendmail` · `array`（テスト） |
| SMTP | ホスト・ポート・暗号化（なし／TLS／SSL）・ユーザー・パスワード |
| 差出人 | From アドレス／表示名、任意の Reply-To |
| 保存先 | `cms_settings`（キー `mail`）。SMTP パスワードは暗号化保存。起動時に `config('mail')` へ反映 |
| テスト送信 | 画面下部から。テンプレート `test_email` を使用 |

テンプレート（件名＋HTML本文、`{placeholder}` 置換）:

| キー | 用途 |
|------|------|
| `test_email` | テスト送信 |
| `comment_notification` | 新規コメント時に差出人アドレスへ通知（有効時） |
| `user_welcome` | 新規ユーザー歓迎（コード／フックから利用可能） |

開発中はメーラーを **ログ** にすると `storage/logs` に本文が書き出され、SMTP なしで確認できます。`.env` の `MAIL_*` は未設定時の初期値として使われ、管理画面の保存値が優先されます。

### 表示・一般設定

ホームページの種類（固定／最新投稿）は **設定 → 表示**（`/admin/settings/reading`）。サイト名・ロゴ・コメントなどは一般設定など。

---

## 権限・API・プラグイン

### ロール（インストール時の既定）

Administrator · Editor · Author · Contributor · Subscriber  

ケイパビリティは WordPress 風（`manage_posts` · `manage_pages` · `manage_themes` · `manage_plugins` など）と LaravelPress 用を組み合わせています。管理: `/admin/users` · `/admin/users/roles`。ログインは `/login`（2FA 可）。Sanctum トークンは `/admin/users/tokens`。

### API

- `/api/v1/*` … CMS API（レート制限 `CMS_API_RATE_LIMIT`、既定 120/分）  
- `/api/wp/v2/*` … WordPress 互換サブセット  

### プラグインとモジュール

| 種類 | 置き場所 | 管理 |
|------|----------|------|
| モジュール | `modules/{Name}/` | `/admin/modules` |
| プラグイン | `plugins/{slug}/` | `/admin/plugins` |

サンプル: `plugins/hello-laravelpress`。CLI: `laravelpress:plugin` · `laravelpress:theme`。

---

## 主要ディレクトリ

```
app/                      コントローラ・モデル・サービス・ポリシー・Job・Hooks
config/cms.php            CMS 名・インストール判定・動的ページ・SEO・レガシー設定
database/migrations/      レガシー + LaravelPress コア
database/seeders/         デモ／Aoyama など
resources/views/admin/    管理画面 Blade
resources/views/themes/   公開テーマ
resources/js/admin/       管理画面 JS
routes/web.php, api.php
plugins/ · modules/       拡張
lang/en · lang/ja         管理画面・セットアップの翻訳
docs/                     仕様・ガイド・フック一覧
public/                   公開ルート・エディタ資産
```

---

## よく使う artisan コマンド

いずれもコンテナ内で実行します。

```bash
docker compose exec app php artisan …
```

| コマンド | 用途 |
|----------|------|
| `migrate --force` | マイグレーション |
| `laravelpress:install --fresh-caps` | 組み込みとロール／権限の同期 |
| `laravelpress:demo-seed [--fresh]` | ダミーコンテンツ |
| `laravelpress:seed-aoyama [--fresh]` | 青山カード風デモ（テーマ有効化・`/news`・キャンペーン等） |
| `laravelpress:theme …` | テーマのスキャフォールド／パック操作 |
| `laravelpress:plugin …` | プラグイン操作 |
| `laravelpress:content migrate-legacy` | レガシー → contents 移行 |
| `laravelpress:content export` / `import` | コンテンツパック |
| `laravelpress:content import-wxr --path=…` | WordPress WXR インポート（`--dry-run` 可） |
| `laravelpress:legacy retire\|restore` | レガシー管理 UI の退役／復帰 |
| `test` | PHPUnit |

デモは管理画面のセットアップガイド／一般設定からも投入できます（`manage_settings` が必要）。

---

## 本番・テストのメモ

- 本番で `APP_ENV=production` のとき `ForceHttps`  
- リバースプロキシは `TRUSTED_PROXIES`（[`docs/Specifications/12-Security.md`](docs/Specifications/12-Security.md)）  
- 検索: `CMS_SEARCH_DRIVER=database|meilisearch` + `MEILISEARCH_HOST`  
- テスト: `docker compose exec app php artisan test`  
- E2E: Playwright（リポジトリ内設定を参照）

### 管理画面 JS

`resources/js/admin/` に `api` · `toast` · `modal` · `form` · `media` · `editor` · `posts` などのバニラモジュールがあります。

### 関連ドキュメント

| パス | 内容 |
|------|------|
| [docs/guides/setup-japanese.md](docs/guides/setup-japanese.md) | 日本語セットアップ手順 |
| [docs/guides/README.md](docs/guides/README.md) | ガイド一覧 |
| [docs/Specifications/](docs/Specifications/) | アーキテクチャ〜REST・セキュリティ等の仕様 |
| [docs/hooks/README.md](docs/hooks/README.md) | アクション／フィルタ一覧 |
| `/admin/help/setup-guide` | アプリ内セットアップガイド |

---

**まとめ:** 編集は管理画面の **コンテンツ／メニュー／外観／設定**、見た目の骨格は **テーマフォルダ**、一覧 URL は **動的ページ**、色のトークンは **テーマカラー**、サイト横断の注入は **カスタムコード**、通知メールは **設定 → メール／テンプレート**、という分担になっています。迷ったら `/admin/help/setup-guide` から進めてください。

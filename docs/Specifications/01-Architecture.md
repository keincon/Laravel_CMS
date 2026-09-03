# LaravelPress Architecture

## Purpose / 目的

English: Define the layered architecture for LaravelPress — a Laravel-native, WordPress-compatible CMS platform.

日本語: LaravelPress（Laravelネイティブで WordPress 互換の CMS）のレイヤー構成を定義します。

## Requirements / 要件

- Thin controllers, Form Requests, Policies/Gates
- Business logic in Services / Actions
- Generic Content + ContentType model (posts/pages are types)
- Extensible Hooks (actions/filters), modules, themes
- Existing posts/pages admin continues to work during migration

## Architecture diagram

```
Browser
  |
  v
Controller (thin)
  |
  v
Form Request + Policy
  |
  v
Application Service
  |
  v
Domain Model / Repository
  |
  v
PostgreSQL (+ Redis cache/queue)
```

## Conflict note (least destructive)

English: The legacy CMS uses separate `posts` and `pages` tables. LaravelPress adds `content_types` + `contents` without removing legacy tables. New platform features target `contents`; Post/Page models remain until a data migration ships.

日本語: 既存CMSは `posts` / `pages` を分離しています。LaravelPress は互換のため既存テーブルを残しつつ、汎用の `content_types` / `contents` を追加します。

## Request flow

1. HTTP hits route under `/admin` or `/api/v1`
2. Middleware: auth, installation check, rate limit
3. Form Request validates
4. Policy authorizes
5. Service mutates models inside a DB transaction
6. Domain events / Hooks fire
7. Cache invalidation listeners run
8. API Resource or Blade response returned

## Built-in hooks (initial)

| Hook | Type | When |
|------|------|------|
| `content.created` | action | After content insert |
| `content.updated` | action | After content update |
| `content.published` | action | After publish |
| `content.status.transitioning` | action | Before status change |
| `content.status.transitioned` | action | After status change |
| `content.rendered` | filter | When HTML is rendered |
| `laravelpress.builtins.seeded` | action | After builtin seed |

## Pseudocode

```
function publish(content):
  assert policy.publish
  begin transaction
    transition status draft|scheduled -> published
    set published_at
    fire content.published
  commit
```

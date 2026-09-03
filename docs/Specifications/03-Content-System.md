# Content System

## Purpose / 目的

English: Generic content types (`post`, `page`, custom) with centralized status transitions.

日本語: 汎用コンテンツタイプと、集中管理されたステータス遷移を定義します。

## Statuses

`draft` → `pending` → `published`  
`draft` → `scheduled` → `published`  
`published` → `draft` | `trash`  
`trash` → `draft`

Invalid transitions throw `InvalidContentStatusTransition`.

## Services

- `ContentService` — create/update/publish/schedule/trash/restore/duplicate
- `ContentStatusTransitionService` — validates transitions
- `MetadataService` — typed meta for content/user/term/media

## Hierarchy (pages)

`parent_id` with cycle detection via `Content::isHierarchicalCycle()`.

## API examples

```http
POST /api/v1/contents
{
  "type": "post",
  "title": "Hello",
  "status": "draft"
}
```

```json
{
  "data": {
    "id": 1,
    "uuid": "...",
    "slug": "hello",
    "status": "draft"
  }
}
```

## ASCII flow

```
Admin Editor
    |
    v
ContentController
    |
    v
ContentService::update()
    |
    +--> ContentStatusTransitionService
    |
    v
contents + content_meta
    |
    v
Hooks::action('content.updated')
```

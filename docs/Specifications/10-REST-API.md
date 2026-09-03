# REST API

## LaravelPress API (`/api/v1`)

Envelope:

```json
{ "data": {} }
```

Collection meta: `current_page`, `last_page`, `per_page`, `total`  
Errors: `message` + `errors` map. No stack traces in production.

Public: posts, pages, categories, tags, authors, menus, settings, theme, types, contents  
Authenticated (Sanctum): content CRUD + admin post/page CRUD

Rate limit: `throttle:api` (`CMS_API_RATE_LIMIT`, default 120/min).

## WordPress-compatible API (`/api/wp/v2`)

| Method | Path | Auth | Notes |
|--------|------|------|-------|
| GET | `/types` | no | Content types |
| GET | `/statuses` | no | Status map |
| GET | `/posts`, `/pages` | no | Published listing |
| GET | `/categories`, `/tags`, `/taxonomies` | no | Taxonomy surface |
| GET | `/media` | no | Media library (safe fields) |
| GET | `/users` | no | Public profile fields only |
| GET | `/comments` | no | Approved comments (`?post=` filter) |
| GET | `/settings` | no | Public site settings subset |
| GET | `/search` | no | Content search (`?search=` / `?q=`) |
| POST/PUT/DELETE | `/posts`, `/pages` | Sanctum | Write subset |

Write responses use the LaravelPress `ContentResource` envelope (`data`).

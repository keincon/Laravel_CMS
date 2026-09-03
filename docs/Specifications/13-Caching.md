# Caching & Search

Redis for settings, menus, taxonomy trees, content queries, rendered pages.

Invalidate via event listeners (`InvalidateCmsCaches`) — never scatter manual forget calls in controllers.

## Search drivers

| Driver | Config | Behavior |
|--------|--------|----------|
| `database` (default) | `CMS_SEARCH_DRIVER=database` | LIKE queries across content, media, users, terms |
| `meilisearch` | `CMS_SEARCH_DRIVER=meilisearch` + `MEILISEARCH_HOST` | HTTP search against Meilisearch index |

On `ContentUpdated` / `ContentPublished`, `SearchIndexer` upserts documents when the Meilisearch driver is active.

# Import / Export

## Status

Implemented foundation:

- `ContentPackService` — JSON pack format `laravelpress.content_pack.v1`
- `WxrImportService` — WordPress WXR (XML export) → LaravelPress
  - Authors (`wp:author`) → users
  - Channel categories/tags + item categories → terms
  - Attachments → media (HTTP download when possible; remote stub fallback)
  - Posts/pages with author + featured image (`_thumbnail_id`) mapping
- Artisan: `php artisan laravelpress:content export|import|import-wxr|migrate-legacy`
- Dry-run import support for JSON packs and WXR
- `LegacyContentMigrator` backfills `posts`/`pages` → `contents` (with term dual-write)

## Examples

```bash
docker compose exec app php artisan laravelpress:content export
docker compose exec app php artisan laravelpress:content import --dry-run
docker compose exec app php artisan laravelpress:content import-wxr --path=imports/wordpress.xml --dry-run
docker compose exec app php artisan laravelpress:content migrate-legacy
```

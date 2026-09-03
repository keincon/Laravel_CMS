# LaravelPress

Laravel-native WordPress-compatible CMS (Laravel 13 + PostgreSQL + Redis).

## Docker

```bash
docker compose up -d --build
docker compose port app 8000
```

Open `http://127.0.0.1:<mapped-port>`.

Services: **app**, **postgres**, **redis**, **queue**, **scheduler**.

| Service | Notes |
|---------|--------|
| postgres | `cms` / `cms` / `cms_secret` (host `postgres` on compose network) |
| redis | cache, session, queue |

### Existing install — seed LaravelPress builtins

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan laravelpress:install --fresh-caps
```

### Tests

```bash
docker compose exec app php artisan test
```

## Architecture (least-destructive migration)

Legacy `posts` / `pages` / `categories` / `tags` remain for the current admin UI.

LaravelPress adds generic tables and dual-writes Post/Page saves into `contents`. The public site dual-reads `contents` first (posts, pages, categories/tags via terms, authors), then falls back to legacy tables.

### Commands

```bash
docker compose exec app php artisan laravelpress:install --fresh-caps
docker compose exec app php artisan laravelpress:content migrate-legacy
docker compose exec app php artisan laravelpress:content export
docker compose exec app php artisan laravelpress:content import --dry-run
docker compose exec app php artisan laravelpress:content import-wxr --path=imports/wordpress.xml --dry-run
```

### Production notes

- `ForceHttps` when `APP_ENV=production`
- `TRUSTED_PROXIES` for reverse proxies (see `docs/Specifications/12-Security.md`)
- API rate limit: `CMS_API_RATE_LIMIT` (default 120/min)
- Search: `CMS_SEARCH_DRIVER=database|meilisearch` + `MEILISEARCH_HOST`

See `docs/Specifications/` and `docs/Specifications/index.html`.

## Admin JS

Vanilla modules under `resources/js/admin/` (`api`, `toast`, `modal`, `form`, `media`, `editor`, `posts`, …).

## First-run setup

Fresh installs use `/setup`. If `storage/app/cms/installed.json` exists, setup returns 404.

**Docker database defaults (use these in the wizard):**

| Field | Value |
|--------|--------|
| Host | `postgres` |
| Port | `5432` |
| Database | `cms` |
| Username | `cms` |
| Password | `cms_secret` |

Admin password must be ≥12 chars with upper, lower, number, and symbol.

If Install fails with a generic error and the app log shows `Environment modified. Restarting server...`, rebuild the app image so it uses PHP’s built-in server (not `artisan serve`):

```bash
docker compose up -d --build app
```

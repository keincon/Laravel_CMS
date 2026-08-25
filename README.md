# Laravel CMS

WordPress-style CMS built with Laravel 13 and PostgreSQL.

## Phase 1 — First-run setup wizard

Fresh installs open a guided wizard at `/setup`. The site and admin area stay locked until installation finishes.

## Docker (recommended)

```bash
docker compose up -d --build
```

The app publishes container port `8000` to a **random free host port**.

```bash
# Show the mapped URL port
docker compose port app 8000
```

Example: if that prints `0.0.0.0:32782`, open:

```text
http://127.0.0.1:32782
```

You will be redirected to `/setup`.

PostgreSQL credentials for the setup wizard (compose network):

| Field | Value |
|-------|-------|
| Host | `postgres` |
| Port | `5432` |
| Database | `cms` |
| Username | `cms` |
| Password | `cms_secret` |

Stop:

```bash
docker compose down
```

### Quick start (without Docker)

```bash
composer install --no-dev
cp .env.example .env
php artisan key:generate
php artisan serve
```

## SEO, OGP, Theme & API

After installation, sign in at `/login` and open `/admin`.

| Area | Path |
|------|------|
| SEO settings | `/admin/settings/seo` |
| Social / OGP | `/admin/settings/ogp` |
| Permalinks | `/admin/settings/permalinks` |
| Theme colors | `/admin/appearance/colors` |
| Color mode | `/admin/appearance/mode` |
| API docs | `/admin/settings/api` |
| API tokens | `/admin/users/tokens` |

Public endpoints (no auth):

- `GET /api/v1/posts`
- `GET /api/v1/pages`
- `GET /api/v1/theme`
- `GET /sitemap.xml`
- `GET /robots.txt`

## Master Header / Footer / Pages / Posts

After installation:

| Area | Path |
|------|------|
| Header builder | `/admin/headers` |
| Footer builder | `/admin/footers` |
| Master layout | `/admin/appearance/layout` |
| Reading (homepage) | `/admin/settings/reading` |
| Pages | `/admin/pages` |
| Posts | `/admin/posts` |

Public pages/posts render through `<x-layout.master>` which injects the published Master Header/Footer, SEO/OGP, theme colors, and optional sidebar. Landing templates can disable header/footer per page.

### Setup flow

1. Welcome  
2. System requirements (PHP 8.3+, extensions, writable paths, PostgreSQL driver)  
3. Database configuration (test connection)  
4. Website settings (name, URL, timezone, language, date format)  
5. Administrator account (strong password, Administrator role)  
6. UI framework (Tailwind CSS or Bootstrap 5)  
7. Install (migrations, roles, permissions, defaults, mark installed)  
8. Complete → website + `/admin`

### Installation detection

- Marker file: `storage/app/cms/installed.json`
- Database record: `installations` table + `cms_settings`

`CheckInstallation` middleware redirects uninstalled traffic to `/setup`, and returns 404 for `/setup` after install.

### Languages (initial)

- English (`en`)
- Japanese (`ja`)
- Myanmar (`my`)

Configured in `config/cms.php` for easy extension.

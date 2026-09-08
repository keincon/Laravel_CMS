# WordPress Embed (real plugins)

LaravelPress does **not** emulate the WordPress Plugin API in PHP. Instead it runs stock WordPress in Docker and talks to it over a token-authenticated REST bridge.

## Architecture

- LaravelPress (Postgres) — CMS admin, native plugins
- WordPress (MySQL) — `docker compose --profile wordpress`
- Mu-plugin bridge mounted at `wp-content/mu-plugins/laravelpress-bridge.php`
- Admin → **Plugins → WordPress** calls the bridge to list / activate / deactivate / install ZIPs

## Start

```bash
# From project root
docker compose --profile wordpress up -d

# Finish WP install in the browser
open http://localhost:8080

# Laravel .env
WP_EMBED_ENABLED=true
WP_EMBED_URL=http://localhost:8080
WP_EMBED_ADMIN_URL=http://localhost:8080/wp-admin
WP_BRIDGE_TOKEN=laravelpress-dev-bridge-token
```

The compose service injects the same default `WP_BRIDGE_TOKEN` into the WordPress container.

```bash
php artisan laravelpress:wp status
php artisan laravelpress:wp hint
```

## Bridge endpoints

Base: `{WP_EMBED_URL}/wp-json/laravelpress/v1`

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/status` | Health + WP version |
| GET | `/plugins` | List installed plugins |
| POST | `/plugins/activate` | `{ "plugin": "dir/file.php" }` |
| POST | `/plugins/deactivate` | same |
| POST | `/plugins/install` | multipart ZIP + optional `activate=1` |

Auth header: `X-LaravelPress-Token: {WP_BRIDGE_TOKEN}`

## Safety

- WP plugin PHP never `include`s into the Laravel process
- Bridge requires a shared secret
- Only admins with `manage_plugins` can use the WordPress tab

## Limits

Any plugin that works on stock WordPress should work in the embed. Page builders / WooCommerce / etc. may need WP themes, permalinks, and HTTPS configured inside WordPress itself — that is outside LaravelPress.

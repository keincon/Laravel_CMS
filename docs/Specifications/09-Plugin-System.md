# Plugin / Module System

LaravelPress separates **Modules** (developer extensions) from **Plugins** (installable packages). WordPress plugins run in an embedded WordPress runtime.

## Modules (keep)

Path: `modules/{Name}/` with `module.json` + optional ServiceProvider.

Admin: **Settings → Modules** (`/admin/modules`, `manage_settings`).

Enabled providers register at boot via `ModuleManager`.

## Plugins (native LaravelPress)

Path: `plugins/{slug}/`

```
plugins/{slug}/
  plugin.json
  hooks.php                 # optional lightweight hooks
  src/PluginServiceProvider.php
  routes/web.php            # optional
  assets/
```

Activation state is stored in the `plugins` database table (`is_active`). PHP runs only when active.

Admin: **Plugins** (`/admin/plugins`, permission `manage_plugins`) — LaravelPress tab.

```bash
php artisan laravelpress:plugin list
php artisan laravelpress:plugin activate hello-laravelpress
php artisan laravelpress:plugin scaffold my-plugin --name="My Plugin" --activate
php artisan laravelpress:plugin export my-plugin
php artisan laravelpress:plugin import --path=/path/to/plugin.zip --activate
php artisan laravelpress:plugin pack
php artisan laravelpress:plugin sync
```

Sample plugin: `plugins/hello-laravelpress/` (appends an HTML comment via `content.rendered` when active).

### Rules

- Never execute uploaded plugin PHP until an administrator activates the plugin
- Reject `.phar` / `.exe` / shell scripts in ZIP packages
- Modules and Plugins coexist; do not replace Modules with Plugins

## WordPress plugins (embedded WP)

Real WordPress plugins are **not** loaded in Laravel. They run inside Docker WordPress:

```bash
docker compose --profile wordpress up -d
php artisan laravelpress:wp hint
```

Then set `.env`:

```
WP_EMBED_ENABLED=true
WP_EMBED_URL=http://localhost:8080
WP_BRIDGE_TOKEN=laravelpress-dev-bridge-token
```

Complete the WP installer at the URL, then use **Plugins → WordPress** tab to list/activate/upload plugins.

Bridge mu-plugin: [`wordpress-bridge/mu-plugins/laravelpress-bridge.php`](../wordpress-bridge/mu-plugins/laravelpress-bridge.php)  
Docs: [`docs/Specifications/18-WordPress-Embed.md`](18-WordPress-Embed.md)

# Theme System

## Theme vs pack

| | **Theme** | **Pack** |
|---|-----------|----------|
| What | Live install on disk | ZIP file of a theme |
| Where | `resources/views/themes/{slug}/` | `storage/app/theme-packs/{slug}.zip` |
| Contains | Screens (Blade), CSS, JS, images, `theme.json` | Same files, compressed for download/import |
| Use | Activate to render the front end | Share, backup, or import onto another site |

**Export theme → pack.** **Import pack → theme.** They are the same content in different forms.

## Theme folder layout

```
resources/views/themes/{slug}/
  theme.json
  pages/*.blade.php      # page screens / templates
  dynamic/*.blade.php    # blog, post, archive, category, tag, search, 404…
  partials/              # @include('themes.{slug}.partials.name')
  assets/
    theme.css            # (and any extra *.css)
    theme.js             # (and any extra *.js)
    images/… fonts/…
```

`theme.json` can list:

```json
{
  "stylesheets": ["theme.css"],
  "scripts": ["theme.js"],
  "colors": { "...": "..." },
  "color_mode": "light"
}
```

If omitted, the manager auto-loads `assets/theme.css` / `assets/theme.js` (or all `*.css` / `*.js` in `assets/`).

Missing screens fall back to `themes.default.*`.

## Bundled themes

| Slug | Look |
|------|------|
| `aoyama` | Aoyama Card (navy JP demo, responsive) |
| `default` | Full screen set (fallback) |
| `aurora` | Cool teal / sky |
| `meadow` | Botanical greens |
| `ink` | Charcoal + coral |
| `paper` | Warm parchment |
| `harbor` | Deep navy + sand |

## Creating your own (screens + CSS + JS)

Admin: **Appearance → Themes → Scaffold theme**

```bash
php artisan laravelpress:theme scaffold my-store --name="My Store" --activate
```

Then edit Blade under `pages/` / `dynamic/`, and assets under `assets/`. Front-end loads active theme CSS/JS via `/themes/{slug}/assets/...`.

## Packages (download / import)

ZIP must include `theme.json`. May include Blade screens and `assets/*`.  
**Allowed:** `.blade.php`, CSS, JS, images, fonts, JSON.  
**Rejected:** raw `.php`, `.phtml`, `.phar`, `.exe`, `.sh`, `.bat`, `.cmd`.

```bash
php artisan laravelpress:theme list
php artisan laravelpress:theme activate aurora
php artisan laravelpress:theme export aurora          # theme → pack ZIP
php artisan laravelpress:theme import --path=aurora.zip --activate
php artisan laravelpress:theme pack                  # rebuild all packs
php artisan laravelpress:theme sync
php artisan laravelpress:theme scaffold my-store
```

## Runtime

- Active theme = `themes.is_active` (`ThemeManager::activeSlug()`), else `CMS_THEME`.
- Activating can apply `colors` + `color_mode` into `theme_settings`.
- Layout: `body.theme-{slug}` + all theme stylesheets/scripts.

## Dummy / demo content

```bash
php artisan laravelpress:demo-seed
# or Admin → Settings → General → Install Dummy Data
```

## Template resolution

`home → single → page → archive → category → tag → search → 404`  
Active theme screens → `default` → legacy `site.*`.

# Hooks Catalog

## Purpose / 目的

Document built-in LaravelPress action and filter hooks.

組み込みフック一覧です。

## Actions

| Hook | Args | When |
|------|------|------|
| `content.created` | Content | After create |
| `content.updated` | Content | After update |
| `content.published` | Content | After publish |
| `content.status.transitioning` | from, to | Before status change |
| `content.status.transitioned` | from, to | After status change |
| `content.revision.restored` | Content, Revision | After restore |
| `settings.updated` | key, value | After SettingsService::set |
| `media.variants.generated` | Media | After variant job |
| `laravelpress.builtins.seeded` | — | After builtin seed |
| `laravelpress.module.seo.booted` | — | SEO module boot |

## Filters

| Hook | Value | When |
|------|-------|------|
| `content.rendered` | HTML string | Before content HTML is returned to the theme |

## Usage

```php
use App\Support\Hooks\Hooks;

Hooks::addAction('content.published', function ($content) {
    // ...
});

$html = Hooks::filter('content.rendered', $html);
```

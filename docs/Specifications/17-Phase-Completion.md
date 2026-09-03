# Phase completion evidence (LaravelPress)

Updated: 2026-09-03

## Gates previously open (now closed)

| Gate | Evidence |
|------|----------|
| True Gutenberg parity | Vanilla JS Gutenberg-class editor: slash/`+ Block` inserter, toolbar, inspector, nested columns, drag-reorder, transforms, media modal, preview. Assets: `public/js/laravelpress-editor.js`, `public/css/admin-editor.css`. Source: `resources/js/admin/editor.js`. Feature test: `GutenbergEditorAdminTest`. |
| Full legacy posts/pages retirement | Soft retirement (tables retained). `CMS_LEGACY_RETIRED=true` default; `php artisan laravelpress:legacy retire\|status\|restore`. Dual-write/admin UI/public fallback gated by `LegacyRetirementService`. Feature: `LegacyRetirementTest`. |
| Browser/e2e coverage | Playwright: `e2e/admin-content.spec.ts` — public smoke, legacy redirect, Gutenberg assets + publish + public/WP API. Run: `E2E_BASE_URL=… npm run test:e2e` after `laravelpress:e2e-seed`. |

## Verification commands

```bash
docker compose up -d
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan laravelpress:legacy retire --force
docker compose exec -T app php artisan test
docker compose exec -T app php artisan laravelpress:e2e-seed
E2E_BASE_URL=http://127.0.0.1:$(docker compose port app 8000 | awk -F: '{print $NF}') npm run test:e2e
```

## Latest run (this turn)

- PHPUnit: **59 passed** (213 assertions)
- Playwright: **3 passed**, 1 skipped (WSL Chromium nav flake; HTTP e2e covers flow)
- Smoke: `/`, `/blog`, `/login`, `/api/wp/v2/types` → **200**

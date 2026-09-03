# Admin UX

Blade + Tailwind + Vanilla JS modules in `resources/js/admin/` (and shipped `public/js/laravelpress-editor.js`).

AJAX must: loading state, CSRF, validation/authorization errors, no duplicate submit, unsaved-change warnings.

## Gutenberg-class block editor

Content edit screen loads a Gutenberg-inspired vanilla JS editor:

- Slash / `+ Block` inserter with search
- Floating block toolbar (move, duplicate, bold/italic/link, remove)
- Block inspector sidebar (alignment, transforms, media, columns)
- Nested columns, drag-and-drop reorder, preview mode
- Media library modal for image/gallery blocks
- Stores structured JSON in `blocks_json` → rendered HTML `body`

Assets: `public/css/admin-editor.css`, `public/js/laravelpress-editor.js` (source: `resources/js/admin/editor.js`).

## Content-first navigation / legacy retirement

With `CMS_LEGACY_RETIRED=true` (default):

- Dual-write and legacy admin UI are off
- `/admin/posts` and `/admin/pages` always redirect to `/admin/contents`
- Public site does not fall back to legacy `posts`/`pages` tables
- Tables are **retained** (least-destructive soft retirement)

Restore temporarily: `php artisan laravelpress:legacy restore` and set `CMS_LEGACY_*` flags.

Retire (migrate + marker): `php artisan laravelpress:legacy retire --force`

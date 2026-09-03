# Database Design

## Purpose / 目的

English: Document LaravelPress core tables, indexes, and relationships.

日本語: LaravelPress の主要テーブル・インデックス・関係を文書化します。

## Core entities

```
content_types 1---* contents 1---* content_meta
                     | 1
                     * 
              content_revisions

taxonomies 1---* terms *---* contents (content_term)
                   |
                   * term_meta

media 1---* media_variants
      1---* media_meta

users 1---* user_meta
      *---* roles *---* permissions

redirects
audit_logs
```

## Important indexes

- `contents (content_type_id, slug)` unique
- `contents.status`, `contents.published_at`, `contents.author_id`
- `terms (taxonomy_id, slug)` unique
- `comments.content_id` / `comments.status` (legacy)
- `media.mime_type`, `media.created_at`

## Migration strategy

1. Keep legacy `posts` / `pages` / `categories` / `tags` tables (least-destructive)
2. Add LaravelPress tables via `2026_09_03_101000_create_laravelpress_core_tables`
3. Expand `users` profile + 2FA-ready columns
4. ETL: `php artisan laravelpress:content migrate-legacy` (posts/pages → contents)
5. Soft-retire legacy: `php artisan laravelpress:legacy retire --force`  
   (`CMS_LEGACY_RETIRED=true` default — dual-write/admin/public fallback off; tables retained)

## Business rules

- Numeric PKs + UUID for public identifiers where useful
- Soft deletes on `contents`
- Foreign keys with cascade/nullOnDelete as appropriate
- Nullable only when justified (excerpt, parent, featured media)

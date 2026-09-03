# Taxonomy System

## Purpose / 目的

English: Generic taxonomies and terms (category/tag are builtins, not hardcoded into Content).

日本語: カテゴリ/タグを組み込みとして持つ汎用タクソノミー設計です。

## Tables

`taxonomies` → `terms` → `content_term` ← `contents`

## Builtins

| Slug | Hierarchical | Content types |
|------|--------------|---------------|
| category | yes | post |
| post_tag | no | post |

## Rules

- Term slugs unique per taxonomy
- Hierarchical terms may have `parent_id`
- Permissions via taxonomy `capabilities` JSON + Gates

# Revision System

## Purpose / 目的

Store and restore content snapshots transactionally.

## Operations

list, preview, compare, restore, delete

## Restore flow

```
select revision
  |
  v
BEGIN
  copy title/body/excerpt/blocks/metadata → content
  write content.updated + revision.restored
COMMIT
```
